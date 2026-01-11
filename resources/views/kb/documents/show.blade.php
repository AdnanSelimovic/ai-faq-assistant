@extends('layouts.app')

@section('title', $document->title)

@section('container-class', 'max-w-6xl')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $document->title }}</h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $document->source_type }}
                    @if ($document->source_ref)
                        ? {{ $document->source_ref }}
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    Status: {{ $document->status }}
                </span>
                <form method="POST" action="{{ route('kb.documents.index-document', $document) }}">
                    @csrf
                    <x-button type="submit">Run indexing</x-button>
                </form>
            </div>
        </div>

        @if ($errors->has('index'))
            <x-form-error :message="$errors->first('index')" />
        @endif
        @if (!empty($document->meta['error']))
            <x-form-error :message="$document->meta['error']" />
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="rounded-xl border border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Raw text</h2>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ strlen($document->meta['raw_text'] ?? '') }} characters
                    </span>
                </div>
                <pre class="mt-3 max-h-[28rem] overflow-auto whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-300">{{ $document->meta['raw_text'] ?? '' }}</pre>
            </div>

            <div class="rounded-xl border border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Summary</h2>
                <dl class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                    <div class="flex items-center justify-between">
                        <dt>Chunks</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $chunks->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>Last updated</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $document->updated_at?->diffForHumans() }}
                        </dd>
                    </div>
                </dl>
                @if ($document->source_type === 'upload' && !empty($document->meta['original_filename']))
                    <div class="mt-6 border-t border-zinc-200/70 pt-4 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Upload metadata</h3>
                        <dl class="mt-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <dt>Filename</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $document->meta['original_filename'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>MIME type</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $document->meta['mime_type'] ?? 'n/a' }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>Size</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ number_format((int) ($document->meta['size_bytes'] ?? 0)) }} bytes
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>SHA256</dt>
                                <dd class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $document->meta['file_sha256'] ?? 'n/a' }}</dd>
                            </div>
                        </dl>
                        <div class="mt-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Extracted preview</h4>
                            <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-300">
                                {{ \Illuminate\Support\Str::limit($document->meta['raw_text'] ?? '', 400) }}
                            </p>
                            @if (!empty($document->meta['extraction_warnings']))
                                <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                    Warnings: {{ implode('; ', (array) $document->meta['extraction_warnings']) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
                <div class="mt-6">
                    <a
                        href="{{ route('kb.documents.index') }}"
                        class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200"
                    >
                        Back to documents
                    </a>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Chunks</h2>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Ordered by index</span>
            </div>
            <div class="mt-4 space-y-4">
                @forelse ($chunks as $chunk)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                Chunk {{ $chunk->chunk_index }}
                            </span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $chunk->content_hash }}</span>
                        </div>
                        <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-200">{{ $chunk->content }}</p>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        No chunks yet. Run indexing to generate them.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
