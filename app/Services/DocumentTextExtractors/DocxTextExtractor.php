<?php

namespace App\Services\DocumentTextExtractors;

use App\Services\DocumentTextExtractionException;
use App\Services\DocumentTextExtractionResult;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;

class DocxTextExtractor implements DocumentTextExtractorAdapter
{
    public function supports(UploadedFile $file): bool
    {
        return strtolower($file->getClientOriginalExtension()) === 'docx';
    }

    public function extract(UploadedFile $file): DocumentTextExtractionResult
    {
        $path = $file->getRealPath();
        if (!$path) {
            throw new DocumentTextExtractionException('Unable to read the uploaded DOCX.');
        }

        try {
            $document = IOFactory::load($path, 'Word2007');
        } catch (\Throwable $exception) {
            throw new DocumentTextExtractionException('DOCX extraction failed. Ensure the file is valid.');
        }

        $chunks = [];
        foreach ($document->getSections() as $section) {
            $chunks[] = $this->extractFromElements($section->getElements());
        }

        $text = trim(implode("\n", array_filter($chunks)));
        if ($text === '') {
            throw new DocumentTextExtractionException('DOCX extraction produced no text.');
        }

        return new DocumentTextExtractionResult($text);
    }

    /**
     * @param array<int, object> $elements
     */
    private function extractFromElements(array $elements): string
    {
        $text = [];

        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $value = $element->getText();
                if (is_string($value)) {
                    $text[] = $value;
                    continue;
                }
            }

            if (method_exists($element, 'getElements')) {
                $text[] = $this->extractFromElements($element->getElements());
            }
        }

        return trim(implode("\n", array_filter($text)));
    }
}
