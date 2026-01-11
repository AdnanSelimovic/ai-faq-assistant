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
                <a
                    href="{{ route('kb.documents.show', $document) }}"
                    class="block rounded-xl border border-zinc-200/70 bg-white p-5 shadow-sm transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $document->title }}
                            </h2>
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
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-200 bg-white p-6 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                    No documents yet. Create your first entry to begin indexing.
                </div>
            @endforelse
        </div>
    </div>
@endsection
