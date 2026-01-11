<?php

namespace App\Services;

class DocumentTextExtractionResult
{
    public function __construct(
        public readonly string $text,
        public readonly array $warnings = [],
    ) {
    }
}
