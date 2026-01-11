<?php

namespace App\Services;

class TextChunker
{
    /**
     * Split text into overlapping chunks.
     *
     * @return array<int, string>
     */
    public function chunk(string $text, int $size = 1000, int $overlap = 120): array
    {
        $length = strlen($text);
        if ($length === 0) {
            return [];
        }

        if ($size < 1) {
            $size = 1;
        }

        if ($overlap >= $size) {
            $overlap = max(0, $size - 1);
        }

        $step = max(1, $size - $overlap);
        $chunks = [];
        for ($start = 0; $start < $length; $start += $step) {
            $chunks[] = substr($text, $start, $size);
        }

        return $chunks;
    }
}
