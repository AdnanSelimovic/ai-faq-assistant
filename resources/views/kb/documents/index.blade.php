@extends('layouts.app')

@section('title', 'Knowledge Base Documents')

@section('container-class', 'max-w-6xl')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Knowledge Base</h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Track sources, chunk status, and raw text.</p>
            </div>
            <a
                href="{{ route('kb.documents.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 focus:ring-offset-2 focus:ring-offset-zinc-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white dark:focus:ring-zinc-100/20 dark:focus:ring-offset-zinc-950"
            >
                New document
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($documents as $document)
                <div class="rounded-xl border border-zinc-200/70 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <a
                                href="{{ route('kb.documents.show', $document) }}"
                                class="text-sm font-semibold text-zinc-900 transition hover:text-zinc-700 dark:text-zinc-100 dark:hover:text-zinc-200"
                            >
                                {{ $document->title }}
                            </a>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $document->source_type }}
                                @if ($document->source_ref)
                                    ? {{ $document->source_ref }}
                                @endif
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ $document->status }}
                        </span>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        <span>Chunks: {{ $document->chunks_count }}</span>
                        <span>Updated {{ $document->updated_at?->diffForHumans() }}</span>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-xs">
                        <a
                            href="{{ route('kb.documents.edit', $document) }}"
                            class="inline-flex items-center rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600"
                        >
                            Edit
                        </a>
                        <form method="POST" action="{{ route('kb.documents.destroy', $document) }}" onsubmit="return confirm('Delete this document? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 shadow-sm transition hover:border-red-300 hover:text-red-800 dark:border-red-900/60 dark:bg-red-950 dark:text-red-200"
                            >
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-200 bg-white p-6 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                    No documents yet. Create your first entry to begin indexing.
                </div>
            @endforelse
        </div>
    </div>
@endsection
