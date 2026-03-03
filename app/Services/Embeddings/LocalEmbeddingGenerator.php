<?php

namespace App\Services\Embeddings;

class LocalEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public function __construct(private int $dimensions = 256)
    {
        $this->dimensions = max(32, $this->dimensions);
    }

    public function embedText(string $text): array
    {
        $tokens = preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $text) ?? ''));
        $tokens = array_values(array_filter($tokens, static fn ($token) => $token !== ''));
        $vector = array_fill(0, $this->dimensions, 0.0);

        foreach ($tokens as $token) {
            $hash = (int) sprintf('%u', crc32($token));
            $index = $hash % $this->dimensions;
            $sign = ($hash & 1) === 0 ? 1.0 : -1.0;
            $weight = min(3.0, 1.0 + (strlen($token) / 10));

            $vector[$index] += $sign * $weight;
        }

        $magnitude = sqrt(array_reduce($vector, static fn ($carry, $value) => $carry + ($value * $value), 0.0));
        if ($magnitude > 0) {
            foreach ($vector as $index => $value) {
                $vector[$index] = round($value / $magnitude, 8);
            }
        }

        return $vector;
    }

    public function embedMany(array $texts): array
    {
        return array_map(fn ($text) => $this->embedText((string) $text), $texts);
    }
}

