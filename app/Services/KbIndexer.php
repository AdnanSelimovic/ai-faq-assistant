<?php

namespace App\Services;

use App\Models\KbChunk;
use App\Models\KbDocument;
use App\Services\Embeddings\EmbeddingGeneratorInterface;
use Illuminate\Support\Facades\DB;

class KbIndexer
{
    public function __construct(
        private TextChunker $chunker,
        private EmbeddingGeneratorInterface $embeddings,
    )
    {
    }

    public function index(KbDocument $document): ?string
    {
        $meta = $document->meta ?? [];
        $rawText = $meta['raw_text'] ?? null;

        if (!is_string($rawText) || $rawText === '') {
            $meta['error'] = 'Missing raw_text in document meta.';
            $document->update([
                'status' => 'error',
                'meta' => $meta,
            ]);

            return 'Document is missing raw text.';
        }

        try {
            DB::transaction(function () use ($document, $rawText, &$meta) {
                $document->chunks()->delete();

                $chunks = $this->chunker->chunk($rawText, 1000, 120);
                $batchSize = max(1, (int) config('ask.embedding_batch_size', 50));
                $vectors = [];
                foreach (array_chunk($chunks, $batchSize) as $batch) {
                    $batchVectors = $this->embeddings->embedMany($batch);
                    if (count($batchVectors) !== count($batch)) {
                        throw new \RuntimeException('Embedding generator returned an unexpected number of vectors.');
                    }

                    $vectors = array_merge($vectors, $batchVectors);
                }

                if (count($vectors) !== count($chunks)) {
                    throw new \RuntimeException('Embedding generator returned an unexpected number of vectors.');
                }

                foreach ($chunks as $index => $content) {
                    KbChunk::create([
                        'document_id' => $document->id,
                        'chunk_index' => $index,
                        'content' => $content,
                        'content_hash' => hash('sha256', $content),
                        'embedding' => $vectors[$index] ?? null,
                        'token_count' => null,
                    ]);
                }

                unset($meta['error']);
                $meta['embedding_driver'] = config('ask.embedding_driver', 'local');
                $meta['embedding_model'] = config('ask.embedding_model', 'text-embedding-3-small');
                $document->update([
                    'status' => 'indexed',
                    'meta' => $meta,
                ]);
            });
        } catch (\Throwable $e) {
            $meta['error'] = $e->getMessage();
            $document->update([
                'status' => 'error',
                'meta' => $meta,
            ]);

            return 'Indexing failed.';
        }

        return null;
    }
}
