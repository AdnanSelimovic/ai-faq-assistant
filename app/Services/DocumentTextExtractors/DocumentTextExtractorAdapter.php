<?php

namespace App\Services\DocumentTextExtractors;

use App\Services\DocumentTextExtractionResult;
use Illuminate\Http\UploadedFile;

interface DocumentTextExtractorAdapter
{
    public function supports(UploadedFile $file): bool;

    public function extract(UploadedFile $file): DocumentTextExtractionResult;
}
