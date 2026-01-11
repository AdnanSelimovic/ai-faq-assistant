<?php

namespace App\Services\DocumentTextExtractors;

use App\Services\DocumentTextExtractionException;
use App\Services\DocumentTextExtractionResult;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\RichText;

class PptxTextExtractor implements DocumentTextExtractorAdapter
{
    public function supports(UploadedFile $file): bool
    {
        return strtolower($file->getClientOriginalExtension()) === 'pptx';
    }

    public function extract(UploadedFile $file): DocumentTextExtractionResult
    {
        $path = $file->getRealPath();
        if (!$path) {
            throw new DocumentTextExtractionException('Unable to read the uploaded PPTX.');
        }

        try {
            $presentation = IOFactory::load($path);
        } catch (\Throwable $exception) {
            throw new DocumentTextExtractionException('PPTX extraction failed. Ensure the file is valid.');
        }

        $chunks = [];
        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if (method_exists($shape, 'getPlainText')) {
                    $chunks[] = (string) $shape->getPlainText();
                    continue;
                }

                if ($shape instanceof RichText) {
                    $chunks[] = $this->extractFromRichText($shape);
                    continue;
                }

                if (method_exists($shape, 'getText')) {
                    $value = $shape->getText();
                    if (is_string($value)) {
                        $chunks[] = $value;
                    }
                }
            }
        }

        $text = trim(implode("\n", array_filter($chunks)));
        if ($text === '') {
            throw new DocumentTextExtractionException('PPTX extraction produced no text.');
        }

        return new DocumentTextExtractionResult($text);
    }

    private function extractFromRichText(RichText $shape): string
    {
        $parts = [];

        foreach ($shape->getParagraphs() as $paragraph) {
            foreach ($paragraph->getRichTextElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $parts[] = (string) $element->getText();
                }
            }
        }

        return trim(implode("\n", array_filter($parts)));
    }
}
