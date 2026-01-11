<?php

namespace App\Services;

use App\Services\DocumentTextExtractors\DocumentTextExtractorAdapter;
use Illuminate\Http\UploadedFile;

class DocumentTextExtractor implements DocumentTextExtractorInterface
{
    /**
     * @var array<int, DocumentTextExtractorAdapter>
     */
    private array $adapters;

    public function __construct(
        DocumentTextExtractorAdapter ...$adapters
    ) {
        $this->adapters = $adapters;
    }

    public function extract(UploadedFile $file): DocumentTextExtractionResult
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($file)) {
                return $adapter->extract($file);
            }
        }

        throw new DocumentTextExtractionException('Unsupported file type.');
    }
}
