<?php

namespace App\Services;

use App\Models\KbChunk;
use App\Models\KbDocument;
use Illuminate\Support\Facades\DB;

class KbIndexer
{
    public function __construct(private TextChunker $chunker)
    {
    }

    public function index(KbDocument $document): ?string
    {
        $meta = $document->meta ?? [];
        $rawText = $meta['raw_text'] ?? null;

        if (!is_string($rawText) || $rawText === '') {
            $meta['error'] = 'Missing raw_text in document meta.';
            $document->update([
                'status' => 'error',
                'meta' => $meta,
            ]);

            return 'Document is missing raw text.';
        }

        try {
            DB::transaction(function () use ($document, $rawText, &$meta) {
                $document->chunks()->delete();

                $chunks = $this->chunker->chunk($rawText, 1000, 120);
                foreach ($chunks as $index => $content) {
                    KbChunk::create([
                        'document_id' => $document->id,
                        'chunk_index' => $index,
                        'content' => $content,
                        'content_hash' => hash('sha256', $content),
                        'embedding' => null,
                        'token_count' => null,
                    ]);
                }

                unset($meta['error']);
                $document->update([
                    'status' => 'indexed',
                    'meta' => $meta,
                ]);
            });
        } catch (\Throwable $e) {
            $meta['error'] = $e->getMessage();
            $document->update([
                'status' => 'error',
                'meta' => $meta,
            ]);

            return 'Indexing failed.';
        }

        return null;
    }
}
