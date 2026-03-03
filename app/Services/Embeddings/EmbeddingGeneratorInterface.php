<?php

namespace App\Services\Embeddings;

interface EmbeddingGeneratorInterface
{
    /**
     * @return array<int, float>
     */
    public function embedText(string $text): array;

    /**
     * @param array<int, string> $texts
     * @return array<int, array<int, float>>
     */
    public function embedMany(array $texts): array;
}

