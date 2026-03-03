<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KbChunk;
use App\Services\RagRetriever;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KbSearchApiController extends Controller
{
    public function search(Request $request, RagRetriever $retriever): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:2000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = $validated['query'];
        $limit = $validated['limit'] ?? 5;
        $retrieval = $retriever->retrieve($query, $limit);
        $chunks = $retrieval['chunks'];

        $results = $chunks->map(function (KbChunk $chunk) {
            return [
                'id' => $chunk->id,
                'snippet' => substr($chunk->content, 0, 200),
                'document_id' => $chunk->document_id,
                'document_title' => $chunk->document?->title,
                'score' => $chunk->getAttribute('retrieval_score'),
                'relevance_share_pct' => $chunk->getAttribute('relevance_share_pct'),
            ];
        });

        return response()->json([
            'data' => $results,
            'retrieval' => [
                'method' => $retrieval['method'],
                'count' => $results->count(),
            ],
        ]);
    }
}
