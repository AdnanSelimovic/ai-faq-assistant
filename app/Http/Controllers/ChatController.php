<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\KbChunk;
use App\Models\Message;
use App\Services\AskModeResolver;
use App\Services\RagRetriever;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function ask(
        Request $request,
        AskModeResolver $modeResolver,
        RagRetriever $retriever,
        ?Conversation $conversation = null
    ): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = $this->resolveConversation($request, $conversation);
        $conversationHadMessages = $conversation->messages()->exists();

        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['question'],
        ]);

        if (!$conversationHadMessages && in_array((string) $conversation->title, ['', 'New chat', 'Default conversation'], true)) {
            $conversation->update([
                'title' => Str::limit($validated['question'], 60),
            ]);
        }

        $query = $validated['question'];
        $retrievalQuery = $this->buildRetrievalQuery($query, $conversation, $userMessage->id);
        $retrieval = $retriever->retrieve($retrievalQuery, 5);
        $chunks = $retrieval['chunks'];
        $retrievalMethod = $retrieval['method'];

        $chunkIds = $chunks->pluck('id')->all();
        $chunkSnippets = $chunks->map(function (KbChunk $chunk) {
            $snippet = substr($chunk->content, 0, 200);
            $documentId = $chunk->document_id;

            return [
                'id' => $chunk->id,
                'snippet' => $snippet,
                'document_id' => $documentId,
                'document_title' => $chunk->document?->title,
                'document_url' => $documentId ? route('kb.documents.show', $documentId) . '#chunk-' . $chunk->id : null,
                'score' => $chunk->getAttribute('retrieval_score'),
                'relevance_share_pct' => $chunk->getAttribute('relevance_share_pct'),
            ];
        })->all();

        $mode = $modeResolver->resolve($request);
        $answer = $this->buildExtractiveAnswer($query, $chunks);
        $model = null;
        $latencyMs = null;
        $fallbackReason = null;

        if ($mode === AskModeResolver::MODE_LLM) {
            $llmResult = $this->buildLlmAnswer($query, $chunks, $conversation, $userMessage->id);
            if ($llmResult && empty($llmResult['error'])) {
                $answer = $llmResult['answer'];
                $model = $llmResult['model'];
                $latencyMs = $llmResult['latency_ms'];
            } else {
                $fallbackReason = $llmResult['error'] ?? 'LLM request failed.';
            }
        }

        if ($fallbackReason) {
            $answer = "LLM mode failed for this request. Falling back to extractive answer.\nReason: {$fallbackReason}\n\n{$answer}";
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $answer,
            'retrieved_chunk_ids' => $chunkIds,
            'from_cache' => false,
            'model' => $model,
            'latency_ms' => $latencyMs,
        ]);

        return response()->json([
            'answer' => $answer,
            'chunks' => $chunkSnippets,
            'retrieval' => [
                'method' => $retrievalMethod,
                'count' => count($chunkSnippets),
                'query_used' => $retrievalQuery,
            ],
        ]);
    }

    private function resolveConversation(Request $request, ?Conversation $conversation = null): Conversation
    {
        if ($conversation) {
            return $conversation;
        }

        $conversationId = $request->integer('conversation_id');
        if ($conversationId) {
            return Conversation::findOrFail($conversationId);
        }

        return Conversation::latest()->first() ?? Conversation::create([
            'title' => 'Default conversation',
        ]);
    }

    private function buildExtractiveAnswer(string $question, \Illuminate\Support\Collection $chunks): string
    {
        if ($chunks->isEmpty()) {
            return "I don't have enough information in the knowledge base to answer that. What specific detail should I look for?";
        }

        $bullets = $this->buildBullets($chunks);
        $quotes = $this->buildQuotes($chunks);

        $answer = implode("\n", array_map(fn ($bullet) => '- ' . $bullet, $bullets));
        $answer .= "\n\nQuotes:\n";
        $answer .= implode("\n", array_map(fn ($quote) => '"' . $quote . '"', $quotes));

        return $answer;
    }

    /**
     * @return array<int, string>
     */
    private function buildBullets(\Illuminate\Support\Collection $chunks): array
    {
        $bullets = [];

        foreach ($chunks as $chunk) {
            $text = $this->cleanSnippet($chunk->content, 160);
            if ($text !== '') {
                $bullets[] = $text;
            }
            if (count($bullets) >= 6) {
                break;
            }
        }

        if (count($bullets) < 3 && $chunks->isNotEmpty()) {
            $seed = $this->cleanSnippet($chunks->first()->content, 420);
            $segments = $seed !== '' ? str_split($seed, 140) : [];
            foreach ($segments as $segment) {
                if (count($bullets) >= 3) {
                    break;
                }
                $segment = trim($segment);
                if ($segment !== '' && !in_array($segment, $bullets, true)) {
                    $bullets[] = $segment;
                }
            }
        }

        return array_slice($bullets, 0, min(6, max(3, count($bullets))));
    }

    /**
     * @return array<int, string>
     */
    private function buildQuotes(\Illuminate\Support\Collection $chunks): array
    {
        $quotes = [];

        foreach ($chunks->take(2) as $chunk) {
            $text = $this->cleanSnippet($chunk->content, 200);
            if ($text !== '') {
                $quotes[] = $text;
            }
        }

        return $quotes;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildLlmAnswer(
        string $question,
        \Illuminate\Support\Collection $chunks,
        Conversation $conversation,
        int $currentUserMessageId
    ): ?array
    {
        $apiKey = config('ask.openai_api_key');
        if (!$apiKey) {
            Log::warning('OpenAI API key is missing, falling back to extractive mode.');
            return [
                'error' => 'OpenAI API key is missing.',
            ];
        }

        $historyBlocks = $this->buildConversationHistoryBlocks($conversation, $currentUserMessageId);
        $contextBlocks = $this->buildContextBlocks($chunks);
        $history = empty($historyBlocks) ? '(none)' : implode("\n", $historyBlocks);
        $context = empty($contextBlocks) ? '(none)' : implode("\n\n", $contextBlocks);
        $input = "CHAT HISTORY:\n{$history}\n\nQUESTION:\n{$question}\n\nCONTEXT:\n{$context}";
        $instructions = 'Use CHAT HISTORY for conversational continuity (for example, follow-up references to prior turns). Use CONTEXT for knowledge-base facts. If a factual answer is not present in CONTEXT, say you don\'t have enough information in the KB.';

        $start = microtime(true);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('ask.openai_model'),
                    'instructions' => $instructions,
                    'input' => $input,
                    'max_output_tokens' => config('ask.openai_max_output_tokens'),
                    'store' => (bool) config('ask.openai_store'),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('OpenAI request failed', ['exception' => $exception->getMessage()]);
            return [
                'error' => 'OpenAI request failed: ' . $exception->getMessage(),
            ];
        }

        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        if (!$response->successful()) {
            Log::warning('OpenAI response failed', ['status' => $response->status()]);
            $detail = $response->json('error.message');
            $reason = $detail ? "{$response->status()} {$detail}" : (string) $response->status();
            return [
                'error' => "OpenAI response error: {$reason}",
            ];
        }

        $payload = $response->json();
        $answer = $this->parseOpenAiResponse($payload);

        if ($answer === '') {
            Log::warning('OpenAI response missing output text');
            return [
                'error' => 'OpenAI response missing output text.',
            ];
        }

        $answer = $this->stripSourcesMentions($answer);

        return [
            'answer' => $answer,
            'model' => config('ask.openai_model'),
            'latency_ms' => $latencyMs,
        ];
    }

    private function stripSourcesMentions(string $answer): string
    {
        $answerWithoutSources = preg_replace('/\s*Sources:\s*[^\r\n]*/i', '', $answer) ?? $answer;
        return trim($answerWithoutSources);
    }

    private function buildRetrievalQuery(string $question, Conversation $conversation, int $currentUserMessageId): string
    {
        $question = trim($question);
        if ($question === '') {
            return $question;
        }

        $assistantMessages = $conversation->messages()
            ->where('id', '<', $currentUserMessageId)
            ->where('role', 'assistant')
            ->latest('id')
            ->limit(8)
            ->get();

        if ($assistantMessages->isNotEmpty()) {
            foreach ($assistantMessages as $assistantMessage) {
                $resolved = $this->resolveNumberedFollowUp($question, (string) $assistantMessage->content);
                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        $lastAssistant = $assistantMessages->first();
        if ($lastAssistant && $this->isShortFollowUpQuery($question)) {
            return trim($question . ' ' . $this->cleanSnippet((string) $lastAssistant->content, 260));
        }

        return $question;
    }

    private function isShortFollowUpQuery(string $question): bool
    {
        $wordCount = count(array_filter(preg_split('/\s+/', trim($question)) ?: []));
        return $wordCount <= 4 || strlen($question) <= 24;
    }

    private function resolveNumberedFollowUp(string $question, string $assistantContent): ?string
    {
        $number = null;
        if (preg_match('/\b([1-9])\b/', $question, $digitMatch)) {
            $number = (int) $digitMatch[1];
        } else {
            $wordToNumber = [
                'first' => 1,
                'second' => 2,
                'third' => 3,
                'fourth' => 4,
                'fifth' => 5,
                'sixth' => 6,
                'seventh' => 7,
                'eighth' => 8,
                'ninth' => 9,
            ];

            foreach ($wordToNumber as $word => $value) {
                if (stripos($question, $word) !== false) {
                    $number = $value;
                    break;
                }
            }
        }

        if ($number === null) {
            return null;
        }

        $plain = strip_tags($assistantContent);
        $plain = preg_replace('/[*_`>#]/', '', $plain) ?? $plain;

        if (preg_match('/^\s*' . $number . '\.\s*(.+)$/im', $plain, $match)) {
            $topic = trim((string) $match[1]);
            if ($topic !== '') {
                return $topic;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function buildConversationHistoryBlocks(Conversation $conversation, int $currentUserMessageId): array
    {
        $maxMessages = max(0, (int) config('ask.max_history_messages', 8));
        $maxChars = max(0, (int) config('ask.max_history_chars', 2000));
        if ($maxMessages === 0 || $maxChars === 0) {
            return [];
        }

        $historyMessages = $conversation->messages()
            ->where('id', '<', $currentUserMessageId)
            ->orderByDesc('id')
            ->limit($maxMessages)
            ->get()
            ->reverse()
            ->values();

        $blocks = [];
        $total = 0;

        foreach ($historyMessages as $message) {
            $role = strtolower((string) $message->role) === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) $message->content);
            if ($content === '') {
                continue;
            }

            $line = $role . ': ' . preg_replace('/\s+/', ' ', $content);
            $length = strlen($line);

            if ($total + $length > $maxChars) {
                $remaining = $maxChars - $total;
                if ($remaining <= 0) {
                    break;
                }

                $blocks[] = substr($line, 0, $remaining);
                break;
            }

            $blocks[] = $line;
            $total += $length;
        }

        return $blocks;
    }

    /**
     * @return array<int, string>
     */
    private function buildContextBlocks(\Illuminate\Support\Collection $chunks): array
    {
        $maxChunks = (int) config('ask.max_context_chunks');
        $maxChars = (int) config('ask.max_context_chars');
        $blocks = [];
        $total = 0;

        foreach ($chunks->take($maxChunks) as $chunk) {
            $title = $chunk->document?->title;
            $label = "Chunk #{$chunk->id}";
            if ($title) {
                $label .= " ({$title})";
            }

            $content = trim((string) $chunk->content);
            if ($content === '') {
                continue;
            }

            $block = $label . ":\n" . $content;
            $blockLength = strlen($block);

            if ($total + $blockLength > $maxChars) {
                $remaining = $maxChars - $total;
                if ($remaining <= 0) {
                    break;
                }

                $block = substr($block, 0, $remaining);
                $blocks[] = $block;
                break;
            }

            $blocks[] = $block;
            $total += $blockLength;
        }

        return $blocks;
    }

    private function parseOpenAiResponse(array $payload): string
    {
        $parts = [];

        foreach (($payload['output'] ?? []) as $output) {
            if (($output['type'] ?? '') !== 'message') {
                continue;
            }

            foreach (($output['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                    $parts[] = $content['text'];
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    private function cleanSnippet(string $text, int $limit): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim((string) $text);

        return $text === '' ? '' : substr($text, 0, $limit);
    }
}
