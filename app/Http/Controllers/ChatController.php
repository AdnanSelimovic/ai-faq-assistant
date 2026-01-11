<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\KbChunk;
use App\Models\Message;
use App\Services\AskModeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function ask(Request $request, AskModeResolver $modeResolver): JsonResponse
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
                ->with('document:id,title')
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

        $mode = $modeResolver->resolve($request);
        $answer = $this->buildExtractiveAnswer($query, $chunks);
        $model = null;
        $latencyMs = null;
        $fallbackReason = null;

        if ($mode === AskModeResolver::MODE_LLM) {
            $llmResult = $this->buildLlmAnswer($query, $chunks);
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
        ]);
    }

    private function buildExtractiveAnswer(string $question, \Illuminate\Support\Collection $chunks): string
    {
        if ($chunks->isEmpty()) {
            return "I don't have enough information in the knowledge base to answer that. What specific detail should I look for?";
        }

        $bullets = $this->buildBullets($chunks);
        $quotes = $this->buildQuotes($chunks);
        $sources = $this->buildSourcesLine($chunks);

        $answer = implode("\n", array_map(fn ($bullet) => '- ' . $bullet, $bullets));
        $answer .= "\n\nQuotes:\n";
        $answer .= implode("\n", array_map(fn ($quote) => '"' . $quote . '"', $quotes));
        $answer .= "\n\n" . $sources;

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

    private function buildSourcesLine(\Illuminate\Support\Collection $chunks): string
    {
        $sources = $chunks->map(function (KbChunk $chunk) {
            $label = '#' . $chunk->id;
            $title = $chunk->document?->title;
            if ($title) {
                $label .= ' (' . $title . ')';
            }

            return $label;
        })->all();

        return 'Sources: ' . implode(', ', $sources);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildLlmAnswer(string $question, \Illuminate\Support\Collection $chunks): ?array
    {
        $apiKey = config('ask.openai_api_key');
        if (!$apiKey) {
            Log::warning('OpenAI API key is missing, falling back to extractive mode.');
            return [
                'error' => 'OpenAI API key is missing.',
            ];
        }

        $contextBlocks = $this->buildContextBlocks($chunks);
        $input = "QUESTION:\n{$question}\n\nCONTEXT:\n" . implode("\n\n", $contextBlocks);
        $instructions = 'Answer ONLY using provided CONTEXT. If the answer is not in CONTEXT, say you don\'t have enough information in the KB. End with Sources: <chunk ids>.';

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

        if (stripos($answer, 'Sources:') === false) {
            $answer .= "\n\n" . $this->buildSourcesLine($chunks);
        }

        return [
            'answer' => $answer,
            'model' => config('ask.openai_model'),
            'latency_ms' => $latencyMs,
        ];
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
