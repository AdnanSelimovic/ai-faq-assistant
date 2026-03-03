<?php

namespace App\Services\Embeddings;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public function __construct(
        private string $apiKey,
        private string $model,
    ) {
    }

    public function embedText(string $text): array
    {
        $vectors = $this->embedMany([$text]);
        return $vectors[0] ?? [];
    }

    public function embedMany(array $texts): array
    {
        $texts = array_values(array_map(static fn ($text) => (string) $text, $texts));
        if (empty($texts)) {
            return [];
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(20)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $texts,
            ]);

        if (!$response->successful()) {
            $detail = $response->json('error.message');
            $reason = $detail ? "{$response->status()} {$detail}" : (string) $response->status();
            throw new RuntimeException("Embedding request failed: {$reason}");
        }

        $data = $response->json('data');
        if (!is_array($data)) {
            throw new RuntimeException('Embedding response missing data.');
        }

        usort($data, static fn ($a, $b) => ((int) ($a['index'] ?? 0)) <=> ((int) ($b['index'] ?? 0)));

        $vectors = [];
        foreach ($data as $row) {
            $embedding = $row['embedding'] ?? null;
            if (!is_array($embedding)) {
                throw new RuntimeException('Embedding response contained an invalid vector.');
            }

            $vectors[] = array_map(static fn ($value) => (float) $value, $embedding);
        }

        return $vectors;
    }
}

