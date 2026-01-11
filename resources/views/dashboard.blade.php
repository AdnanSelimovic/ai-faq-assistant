@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Dashboard</h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    You are signed in with the single-user email. Use the knowledge base to manage sources.
                </p>
            </div>
            <a
                href="{{ route('kb.documents.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600"
            >
                Go to knowledge base
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Ask a question</h2>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    This is a placeholder until retrieval and embeddings are wired up.
                </p>
                <div class="mt-4">
                    <label for="question" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Question</label>
                    <div class="mt-2">
                        <x-textarea
                            id="question"
                            rows="5"
                            placeholder="What are the hours of support for premium customers?"
                        ></x-textarea>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <x-button type="button">Ask</x-button>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Next steps</h2>
                <ul class="mt-3 space-y-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <li>Upload or paste source text in the knowledge base.</li>
                    <li>Run indexing to generate chunks.</li>
                    <li>Connect embeddings when ready.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
