<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KbChunk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KbSearchApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:2000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = $validated['query'];
        $limit = $validated['limit'] ?? 5;
        $chunks = collect();

        try {
            $chunks = KbChunk::query()
                ->selectRaw('kb_chunks.*, MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) AS score', [$query])
                ->whereFullText('content', $query)
                ->orderByDesc('score')
                ->limit($limit)
                ->with('document:id,title')
                ->get();
        } catch (\Throwable $e) {
            $chunks = collect();
        }

        if ($chunks->isEmpty()) {
            $terms = preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $query)));
            $terms = array_values(array_filter($terms, fn ($term) => strlen($term) > 2));

            $chunks = KbChunk::query()
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
                ->with('document:id,title')
                ->get();
        }

        $results = $chunks->map(function (KbChunk $chunk) {
            return [
                'id' => $chunk->id,
                'snippet' => substr($chunk->content, 0, 200),
                'document_id' => $chunk->document_id,
                'document_title' => $chunk->document?->title,
            ];
        });

        return response()->json([
            'data' => $results,
        ]);
    }
}
