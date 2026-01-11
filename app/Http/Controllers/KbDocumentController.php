<?php

namespace App\Http\Controllers;

use App\Models\KbChunk;
use App\Models\KbDocument;
use App\Services\TextChunker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KbDocumentController extends Controller
{
    public function index(): View
    {
        $documents = KbDocument::withCount('chunks')
            ->latest()
            ->get();

        return view('kb.documents.index', [
            'documents' => $documents,
        ]);
    }

    public function create(): View
    {
        return view('kb.documents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'string', 'max:50'],
            'source_ref' => ['nullable', 'string', 'max:2048'],
            'raw_text' => ['required', 'string'],
        ]);

        $document = KbDocument::create([
            'title' => $validated['title'],
            'source_type' => $validated['source_type'],
            'source_ref' => $validated['source_ref'] ?? null,
            'meta' => [
                'raw_text' => $validated['raw_text'],
            ],
        ]);

        return redirect()->route('kb.documents.show', $document);
    }

    public function show(int $id): View
    {
        $document = KbDocument::findOrFail($id);
        $chunks = $document->chunks()
            ->orderBy('chunk_index')
            ->get();

        return view('kb.documents.show', [
            'document' => $document,
            'chunks' => $chunks,
        ]);
    }

    public function indexDocument(int $id, TextChunker $chunker): RedirectResponse
    {
        $document = KbDocument::findOrFail($id);
        $meta = $document->meta ?? [];
        $rawText = $meta['raw_text'] ?? null;

        if (!is_string($rawText) || $rawText === '') {
            $meta['error'] = 'Missing raw_text in document meta.';
            $document->update([
                'status' => 'error',
                'meta' => $meta,
            ]);

            return back()->withErrors(['raw_text' => 'Document is missing raw text.']);
        }

        try {
            DB::transaction(function () use ($document, $chunker, $rawText, &$meta) {
                $document->chunks()->delete();

                $chunks = $chunker->chunk($rawText, 1000, 120);
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

            return back()->withErrors(['index' => 'Indexing failed.']);
        }

        return back();
    }
}
