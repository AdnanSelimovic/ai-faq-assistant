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
                ->selectRaw('kb_chunks.*, MATCH(content) AGAINST(? IN BOOLEAN MODE) AS score', [$query])
                ->whereRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE)', [$query])
                ->orderByDesc('score')
                ->limit(5)
                ->get();
        } catch (\Throwable $e) {
            $chunks = KbChunk::query()
                ->where('content', 'like', '%' . $query . '%')
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
