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
                    Retrieval is enabled. Answers are placeholder until embeddings are wired up.
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
                        <x-button type="button" id="ask-button">
                            <span id="ask-button-text">Ask</span>
                        </x-button>
                    </div>
                    <div id="ask-error" class="mt-3 hidden text-sm text-red-600 dark:text-red-400"></div>
                    <div id="ask-result" class="mt-4 hidden space-y-3">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Answer</div>
                            <p id="ask-answer" class="mt-2 text-sm text-zinc-700 dark:text-zinc-200"></p>
                        </div>
                        <details class="rounded-lg border border-zinc-200 bg-zinc-50/80 p-4 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200">
                            <summary class="cursor-pointer text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Sources
                            </summary>
                            <div id="ask-chunks" class="mt-3 space-y-2"></div>
                        </details>
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

    <script>
        const askButton = document.getElementById('ask-button');
        const askButtonText = document.getElementById('ask-button-text');
        const questionInput = document.getElementById('question');
        const askError = document.getElementById('ask-error');
        const askResult = document.getElementById('ask-result');
        const askAnswer = document.getElementById('ask-answer');
        const askChunks = document.getElementById('ask-chunks');

        askButton.addEventListener('click', async () => {
            askError.classList.add('hidden');
            askResult.classList.add('hidden');
            askChunks.innerHTML = '';
            askButton.setAttribute('disabled', 'disabled');
            askButtonText.textContent = 'Asking...';

            const question = questionInput.value.trim();
            if (!question) {
                askError.textContent = 'Please enter a question.';
                askError.classList.remove('hidden');
                askButton.removeAttribute('disabled');
                askButtonText.textContent = 'Ask';
                return;
            }

            try {
                const response = await fetch('{{ route('chat.ask') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ question }),
                });

                if (!response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    askError.textContent = payload.message || 'Unable to process your question.';
                    askError.classList.remove('hidden');
                    askButton.removeAttribute('disabled');
                    askButtonText.textContent = 'Ask';
                    return;
                }

                const payload = await response.json();
                askAnswer.textContent = payload.answer || '';
                (payload.chunks || []).forEach((chunk) => {
                    const item = document.createElement('div');
                    item.className = 'rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200';
                    item.textContent = `#${chunk.id}: ${chunk.snippet}`;
                    askChunks.appendChild(item);
                });

                askResult.classList.remove('hidden');
                askButton.removeAttribute('disabled');
                askButtonText.textContent = 'Ask';
            } catch (error) {
                askError.textContent = 'Network error while asking the question.';
                askError.classList.remove('hidden');
                askButton.removeAttribute('disabled');
                askButtonText.textContent = 'Ask';
            }
        });
    </script>
@endsection
