<?php

namespace App\Services\DocumentTextExtractors;

use App\Services\DocumentTextExtractionException;
use App\Services\DocumentTextExtractionResult;
use Illuminate\Http\UploadedFile;

class PdfTextExtractor implements DocumentTextExtractorAdapter
{
    public function supports(UploadedFile $file): bool
    {
        return strtolower($file->getClientOriginalExtension()) === 'pdf';
    }

    public function extract(UploadedFile $file): DocumentTextExtractionResult
    {
        $path = $file->getRealPath();
        if (!$path) {
            throw new DocumentTextExtractionException('Unable to read the uploaded PDF.');
        }

        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new DocumentTextExtractionException('PDF extraction failed. Install smalot/pdfparser.');
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            $text = trim((string) $pdf->getText());
        } catch (\Throwable $exception) {
            throw new DocumentTextExtractionException('PDF extraction failed. Ensure the file is valid.');
        }

        if ($text === '') {
            throw new DocumentTextExtractionException(
                'PDF extraction produced no text. Confirm the file contains selectable text.'
            );
        }

        return new DocumentTextExtractionResult($text);
    }
}
