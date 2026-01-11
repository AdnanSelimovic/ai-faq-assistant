<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\KbChunk;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = Conversation::latest()->first();
        if (!$conversation) {
            $conversation = Conversation::create([
                'title' => 'Default conversation',
            ]);
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['question'],
        ]);

        $query = $validated['question'];
        $chunks = collect();

        try {
            $chunks = KbChunk::query()
                ->selectRaw('kb_chunks.*, MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) AS score', [$query])
                ->whereFullText('content', $query)
                ->orderByDesc('score')
                ->limit(5)
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
                ->limit(5)
                ->get();
        }

        $chunkIds = $chunks->pluck('id')->all();
        $chunkSnippets = $chunks->map(function (KbChunk $chunk) {
            $snippet = substr($chunk->content, 0, 200);

            return [
                'id' => $chunk->id,
                'snippet' => $snippet,
            ];
        })->all();

        $answer = sprintf(
            'Retrieval working. Found %d relevant chunks. Ask again once embeddings are enabled.',
            count($chunkIds)
        );

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $answer,
            'retrieved_chunk_ids' => $chunkIds,
            'from_cache' => false,
        ]);

        return response()->json([
            'answer' => $answer,
            'chunks' => $chunkSnippets,
        ]);
    }
}
