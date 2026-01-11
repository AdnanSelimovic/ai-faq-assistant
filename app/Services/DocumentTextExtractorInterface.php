<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

interface DocumentTextExtractorInterface
{
    public function extract(UploadedFile $file): DocumentTextExtractionResult;
}
