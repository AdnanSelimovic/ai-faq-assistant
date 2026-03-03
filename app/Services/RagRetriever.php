<?php

namespace App\Services;

use App\Models\KbChunk;
use App\Services\Embeddings\EmbeddingGeneratorInterface;
use Illuminate\Support\Collection;

class RagRetriever
{
    public function __construct(private EmbeddingGeneratorInterface $embeddings)
    {
    }

    /**
     * @return array{chunks: Collection<int, KbChunk>, method: string}
     */
    public function retrieve(string $query, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $mode = (string) config('ask.retrieval_mode', 'hybrid');
        $vectorChunks = $this->retrieveVector($query, $limit);
        $lexicalChunks = $this->retrieveLexical($query, max($limit, 12));

        if ($mode === 'vector') {
            if ($vectorChunks->isNotEmpty()) {
                return [
                    'chunks' => $this->attachRelevanceShares($vectorChunks),
                    'method' => 'vector',
                ];
            }

            return [
                'chunks' => $this->attachRelevanceShares($lexicalChunks->take($limit)->values()),
                'method' => 'lexical_fallback',
            ];
        }

        $hybridChunks = $this->mergeHybrid($vectorChunks, $lexicalChunks, $limit);
        if ($hybridChunks->isNotEmpty()) {
            return [
                'chunks' => $this->attachRelevanceShares($hybridChunks),
                'method' => 'hybrid',
            ];
        }

        return [
            'chunks' => $this->attachRelevanceShares($lexicalChunks->take($limit)->values()),
            'method' => 'lexical_fallback',
        ];
    }

    /**
     * @return Collection<int, KbChunk>
     */
    private function retrieveVector(string $query, int $limit): Collection
    {
        try {
            $queryVector = $this->embeddings->embedText($query);
        } catch (\Throwable) {
            return collect();
        }

        if (empty($queryVector)) {
            return collect();
        }

        $candidateLimit = max($limit, (int) config('ask.vector_candidate_limit', 300));
        $candidates = $this->buildVectorCandidates($query, $candidateLimit);
        if ($candidates->isEmpty()) {
            return collect();
        }

        $scored = $candidates->map(function (KbChunk $chunk) use ($queryVector) {
            $score = $this->cosineSimilarity($queryVector, $chunk->embedding);
            if ($score === null) {
                return null;
            }

            $chunk->setAttribute('retrieval_score', round($score, 6));
            return $chunk;
        })->filter();

        $ranked = $scored
            ->sortByDesc(fn (KbChunk $chunk) => (float) ($chunk->getAttribute('retrieval_score') ?? 0.0))
            ->values();

        $minScore = (float) config('ask.vector_min_score', 0.08);
        $bestScore = (float) ($ranked->first()?->getAttribute('retrieval_score') ?? 0.0);
        if ($bestScore < $minScore) {
            return collect();
        }

        return $ranked
            ->filter(fn (KbChunk $chunk) => (float) ($chunk->getAttribute('retrieval_score') ?? 0.0) >= $minScore)
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, KbChunk>
     */
    private function retrieveLexical(string $query, int $limit): Collection
    {
        try {
            $chunks = KbChunk::query()
                ->with('document:id,title')
                ->selectRaw('kb_chunks.*, MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) AS score', [$query])
                ->whereFullText('content', $query)
                ->orderByDesc('score')
                ->limit($limit)
                ->get();
        } catch (\Throwable) {
            $chunks = collect();
        }

        if ($chunks->isNotEmpty()) {
            return $chunks->values()->map(function (KbChunk $chunk) {
                $chunk->setAttribute('retrieval_lexical_score', (float) ($chunk->getAttribute('score') ?? 0.0));
                return $chunk;
            });
        }

        $terms = preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $query) ?? ''));
        $terms = array_values(array_filter($terms, static fn ($term) => strlen($term) > 2));

        $fallback = KbChunk::query()
            ->with('document:id,title')
            ->when($terms, function ($builder) use ($terms) {
                $builder->where(function ($subQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $subQuery->orWhere('content', 'like', '%' . $term . '%');
                    }
                });
            }, function ($builder) use ($query) {
                $builder->where('content', 'like', '%' . $query . '%');
            })
            ->limit($limit)
            ->get();

        return $fallback->values()->map(function (KbChunk $chunk, int $index) {
            $chunk->setAttribute('retrieval_lexical_score', 1.0 / ($index + 1));
            return $chunk;
        });
    }

    /**
     * @return Collection<int, KbChunk>
     */
    private function buildVectorCandidates(string $query, int $candidateLimit): Collection
    {
        $seedLimit = max(10, min($candidateLimit, (int) floor($candidateLimit * 0.5)));
        $seedIds = $this->retrieveLexical($query, $seedLimit)
            ->pluck('id')
            ->filter()
            ->values();

        $remaining = max(0, $candidateLimit - $seedIds->count());
        $randomIds = collect();

        if ($remaining > 0) {
            $randomIds = KbChunk::query()
                ->whereNotNull('embedding')
                ->when($seedIds->isNotEmpty(), fn ($builder) => $builder->whereNotIn('id', $seedIds->all()))
                ->inRandomOrder()
                ->limit($remaining)
                ->pluck('id');
        }

        $candidateIds = $seedIds
            ->merge($randomIds)
            ->unique()
            ->values();

        if ($candidateIds->isEmpty()) {
            return collect();
        }

        return KbChunk::query()
            ->with('document:id,title')
            ->whereIn('id', $candidateIds->all())
            ->get();
    }

    /**
     * @return Collection<int, KbChunk>
     */
    private function mergeHybrid(Collection $vectorChunks, Collection $lexicalChunks, int $limit): Collection
    {
        $vectorWeight = max(0.0, min(1.0, (float) config('ask.hybrid_vector_weight', 0.7)));
        $lexicalWeight = 1.0 - $vectorWeight;

        $map = [];

        foreach ($vectorChunks as $chunk) {
            $id = (int) $chunk->id;
            $vectorScore = (float) ($chunk->getAttribute('retrieval_score') ?? 0.0);
            $normalizedVector = ($vectorScore + 1.0) / 2.0;

            $chunk->setAttribute('retrieval_hybrid_score', $normalizedVector * $vectorWeight);
            $map[$id] = $chunk;
        }

        foreach ($lexicalChunks as $index => $chunk) {
            $id = (int) $chunk->id;
            $rawLexical = (float) ($chunk->getAttribute('retrieval_lexical_score') ?? (1.0 / ($index + 1)));
            $normalizedLexical = $rawLexical > 0 ? min(1.0, log10(1.0 + $rawLexical)) : 0.0;

            if (!isset($map[$id])) {
                $chunk->setAttribute('retrieval_score', null);
                $chunk->setAttribute('retrieval_hybrid_score', 0.0);
                $map[$id] = $chunk;
            }

            $current = (float) ($map[$id]->getAttribute('retrieval_hybrid_score') ?? 0.0);
            $map[$id]->setAttribute('retrieval_hybrid_score', $current + ($normalizedLexical * $lexicalWeight));
        }

        return collect(array_values($map))
            ->sortByDesc(fn (KbChunk $chunk) => (float) ($chunk->getAttribute('retrieval_hybrid_score') ?? 0.0))
            ->take($limit)
            ->values();
    }

    /**
     * @param Collection<int, KbChunk> $chunks
     * @return Collection<int, KbChunk>
     */
    private function attachRelevanceShares(Collection $chunks): Collection
    {
        if ($chunks->isEmpty()) {
            return $chunks;
        }

        $weights = [];
        $sum = 0.0;

        foreach ($chunks as $index => $chunk) {
            $weight = $this->resolveRelevanceWeight($chunk, (int) $index);
            $weights[] = $weight;
            $sum += $weight;
        }

        if ($sum <= 0) {
            $sum = (float) max(1, count($weights));
            $weights = array_fill(0, count($weights), 1.0);
        }

        return $chunks->values()->map(function (KbChunk $chunk, int $index) use ($weights, $sum) {
            $share = ($weights[$index] / $sum) * 100;
            $chunk->setAttribute('relevance_share_pct', round($share, 1));
            return $chunk;
        });
    }

    private function resolveRelevanceWeight(KbChunk $chunk, int $index): float
    {
        $hybrid = $chunk->getAttribute('retrieval_hybrid_score');
        if ($hybrid !== null) {
            return max(0.0, (float) $hybrid);
        }

        $vector = $chunk->getAttribute('retrieval_score');
        if ($vector !== null) {
            return max(0.0, ((float) $vector + 1.0) / 2.0);
        }

        $lexical = $chunk->getAttribute('retrieval_lexical_score');
        if ($lexical !== null) {
            return max(0.0, (float) $lexical);
        }

        return 1.0 / ($index + 1);
    }

    /**
     * @param array<int, float>|null $a
     * @param array<int, float>|null $b
     */
    private function cosineSimilarity(?array $a, ?array $b): ?float
    {
        if (!$a || !$b || count($a) !== count($b)) {
            return null;
        }

        $dot = 0.0;
        $aNorm = 0.0;
        $bNorm = 0.0;
        $count = count($a);

        for ($i = 0; $i < $count; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];
            $dot += $x * $y;
            $aNorm += $x * $x;
            $bNorm += $y * $y;
        }

        if ($aNorm <= 0 || $bNorm <= 0) {
            return null;
        }

        return $dot / (sqrt($aNorm) * sqrt($bNorm));
    }
}
