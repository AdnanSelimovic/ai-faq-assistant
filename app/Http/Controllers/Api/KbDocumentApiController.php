<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KbDocument;
use App\Services\KbIndexer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KbDocumentApiController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:draft,indexed,error'],
            'older_than_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $documents = KbDocument::query()
            ->when($validated['status'] ?? null, function ($query, string $status) {
                $query->where('status', $status);
            })
            ->when($validated['older_than_days'] ?? null, function ($query, int $days) {
                $query->where('updated_at', '<', now()->subDays($days));
            })
            ->withCount('chunks')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (KbDocument $document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'source_type' => $document->source_type,
                    'source_ref' => $document->source_ref,
                    'status' => $document->status,
                    'chunks_count' => $document->chunks_count,
                    'updated_at' => $document->updated_at,
                ];
            });

        return response()->json([
            'data' => $documents,
        ]);
    }

    public function store(Request $request): JsonResponse
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

        return response()->json([
            'id' => $document->id,
            'title' => $document->title,
            'source_type' => $document->source_type,
            'source_ref' => $document->source_ref,
            'status' => $document->status,
            'created_at' => $document->created_at,
        ], 201);
    }

    public function index(int $id, KbIndexer $indexer): JsonResponse
    {
        $document = KbDocument::findOrFail($id);
        $error = $indexer->index($document);
        $document->refresh();

        return response()->json([
            'id' => $document->id,
            'status' => $document->status,
            'error' => $error,
        ]);
    }
}
