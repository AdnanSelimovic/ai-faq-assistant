<?php

namespace App\Http\Controllers;

use App\Models\KbDocument;
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

    public function indexDocument(int $id, KbIndexer $indexer): RedirectResponse
    {
        $document = KbDocument::findOrFail($id);
        $error = $indexer->index($document);
        if ($error) {
            return back()->withErrors(['index' => $error]);
        }

        return back();
    }
}
