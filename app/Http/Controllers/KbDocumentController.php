<?php

namespace App\Http\Controllers;

use App\Models\KbDocument;
use App\Services\DocumentTextExtractionException;
use App\Services\DocumentTextExtractorInterface;
use App\Services\KbIndexer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request, DocumentTextExtractorInterface $extractor): RedirectResponse
    {
        $validated = $this->validateIngest($request);
        $payload = $this->buildIngestPayload($request, $validated, $extractor);
        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        $document = KbDocument::create($payload);

        return redirect()->route('kb.documents.show', $document);
    }

    public function edit(int $id): View
    {
        $document = KbDocument::findOrFail($id);

        return view('kb.documents.edit', [
            'document' => $document,
        ]);
    }

    public function update(Request $request, int $id, DocumentTextExtractorInterface $extractor): RedirectResponse
    {
        $document = KbDocument::findOrFail($id);

        $validated = $this->validateIngest($request);
        $payload = $this->buildIngestPayload($request, $validated, $extractor);
        if ($payload instanceof RedirectResponse) {
            return $payload;
        }

        $document->update($payload);

        return redirect()->route('kb.documents.show', $document);
    }

    public function destroy(int $id): RedirectResponse
    {
        $document = KbDocument::findOrFail($id);
        $document->delete();

        return redirect()->route('kb.documents.index');
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

    public function indexDocument(int $id, KbIndexer $indexer): RedirectResponse
    {
        $document = KbDocument::findOrFail($id);
        $error = $indexer->index($document);
        if ($error) {
            return back()->withErrors(['index' => $error]);
        }

        return back();
    }

    private function validateIngest(Request $request): array
    {
        $validator = validator($request->all(), [
            'title' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:50'],
            'source_ref' => ['nullable', 'string', 'max:2048'],
            'raw_text' => ['nullable', 'string'],
            'upload' => ['nullable', 'file', 'mimes:pdf,docx,pptx', 'max:10240'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $hasRawText = $request->filled('raw_text');
            $hasUpload = $request->hasFile('upload');

            if (!$hasRawText && !$hasUpload) {
                $validator->errors()->add('raw_text', 'Provide raw text or upload a file.');
            }

            if ($hasRawText) {
                if (!$request->filled('title')) {
                    $validator->errors()->add('title', 'The title field is required when raw text is provided.');
                }
                if (!$request->filled('source_type')) {
                    $validator->errors()->add('source_type', 'The source type field is required when raw text is provided.');
                }
            }
        });

        return $validator->validate();
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function buildIngestPayload(
        Request $request,
        array $validated,
        DocumentTextExtractorInterface $extractor
    ): array|RedirectResponse {
        if ($request->filled('raw_text')) {
            return [
                'title' => $validated['title'],
                'source_type' => $validated['source_type'],
                'source_ref' => $validated['source_ref'] ?? null,
                'meta' => [
                    'raw_text' => $validated['raw_text'],
                ],
            ];
        }

        $upload = $request->file('upload');
        if (!$upload) {
            return back()->withErrors(['raw_text' => 'Provide raw text or upload a file.'])->withInput();
        }

        try {
            $extraction = $extractor->extract($upload);
        } catch (DocumentTextExtractionException $exception) {
            return back()->withErrors(['upload' => $exception->getMessage()])->withInput();
        }

        $title = $validated['title'] ?? null;
        if (!$title) {
            $title = pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Uploaded document';
        }

        $temporaryPath = $upload->getRealPath();
        if (!$temporaryPath) {
            return back()->withErrors(['upload' => 'Unable to read the uploaded file.'])->withInput();
        }

        $meta = [
            'raw_text' => $extraction->text,
            'original_filename' => $upload->getClientOriginalName(),
            'mime_type' => $upload->getMimeType(),
            'size_bytes' => strlen($extraction->text),
            'file_sha256' => hash_file('sha256', $temporaryPath),
            'extracted_at' => now()->toIso8601String(),
        ];

        if (!empty($extraction->warnings)) {
            $meta['extraction_warnings'] = $extraction->warnings;
        }

        return [
            'title' => $title,
            'source_type' => 'upload',
            'source_ref' => null,
            'meta' => $meta,
        ];
    }
}
