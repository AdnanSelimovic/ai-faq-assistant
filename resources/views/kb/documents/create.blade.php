@extends('layouts.app')

@section('title', 'Create Document')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Add document</h1>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                Paste the full source text. Indexing will chunk it for retrieval later.
            </p>
        </div>

        <form method="POST" action="{{ route('kb.documents.store') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf

            <div class="rounded-xl border border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="space-y-5">
                    <div>
                        <label for="title" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Title</label>
                        <div class="mt-2">
                            <x-input id="title" name="title" value="{{ old('title') }}" />
                            @error('title')
                                <x-form-error :message="$message" />
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="source_type" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Source type</label>
                            <div class="mt-2">
                                <x-input
                                    id="source_type"
                                    name="source_type"
                                    value="{{ old('source_type') }}"
                                    placeholder="faq, handbook, docs"
                                />
                                @error('source_type')
                                    <x-form-error :message="$message" />
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="source_ref" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Source reference</label>
                            <div class="mt-2">
                                <x-input
                                    id="source_ref"
                                    name="source_ref"
                                    value="{{ old('source_ref') }}"
                                    placeholder="URL or internal ref"
                                />
                                @error('source_ref')
                                    <x-form-error :message="$message" />
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="raw_text" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Raw text</label>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Paste the source text. If you upload a file too, the pasted text will be used instead.
                        </p>
                        <div class="mt-2">
                            <x-textarea
                                id="raw_text"
                                name="raw_text"
                                rows="12"
                                placeholder="Paste the full document text here..."
                            >{{ old('raw_text') }}</x-textarea>
                            @error('raw_text')
                                <x-form-error :message="$message" />
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="upload" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Upload file (optional)</label>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Accepts PDF, DOCX, or PPTX. If raw text is empty, the upload will be extracted and used. If the title is empty, the filename will be used.
                        </p>
                        <div class="mt-2">
                            <x-input id="upload" name="upload" type="file" accept=".pdf,.docx,.pptx" />
                            @error('upload')
                                <x-form-error :message="$message" />
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <a
                    href="{{ route('kb.documents.index') }}"
                    class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    Back to list
                </a>
                <x-button type="submit">Save document</x-button>
            </div>
        </form>
    </div>
@endsection
