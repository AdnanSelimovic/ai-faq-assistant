@extends('layouts.app')

@section('title', $conversation->title ?: 'Chat')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $conversation->title ?: 'Untitled chat' }}
                </h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Chat history is saved automatically.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600"
                >
                    Back to chats
                </a>
                <a
                    href="{{ route('kb.documents.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600"
                >
                    Knowledge base
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Messages</h2>
                <div class="flex items-center gap-3">
                    <label for="ask-mode" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mode</label>
                    <select
                        id="ask-mode"
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-100 dark:focus:ring-zinc-100/10"
                    >
                        <option value="extractive" @selected(($askMode ?? 'extractive') === 'extractive')>Extractive</option>
                        <option value="llm" @selected(($askMode ?? 'extractive') === 'llm')>LLM</option>
                    </select>
                    <span id="ask-mode-status" class="hidden text-xs text-emerald-600 dark:text-emerald-400">Saved</span>
                </div>
            </div>

            <div id="messages" class="max-h-[28rem] space-y-3 overflow-auto rounded-lg border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                @forelse ($messages as $message)
                    <div class="rounded-lg border px-3 py-2 {{ $message->role === 'assistant' ? 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900' : 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/60 dark:bg-emerald-950/40' }}">
                        <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $message->role }}</div>
                        <p class="mt-1 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $message->content }}</p>
                    </div>
                @empty
                    <div id="empty-state" class="text-sm text-zinc-500 dark:text-zinc-400">
                        No messages yet. Ask your first question.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                <label for="question" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Question</label>
                <div class="mt-2">
                    <x-textarea
                        id="question"
                        rows="4"
                        placeholder="Ask a question about your indexed knowledge base..."
                    ></x-textarea>
                </div>
                <div class="mt-4 flex justify-end">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            id="copy-last-answer"
                            class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600"
                        >
                            Copy answer
                        </button>
                        <button
                            type="button"
                            id="regenerate-answer"
                            class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600"
                        >
                            Regenerate
                        </button>
                        <x-button type="button" id="ask-button">
                            <span id="ask-button-text">Send</span>
                        </x-button>
                    </div>
                </div>
                <div id="chat-action-status" class="mt-2 hidden text-xs text-zinc-500 dark:text-zinc-400"></div>
                <div id="ask-error" class="mt-3 hidden text-sm text-red-600 dark:text-red-400"></div>
                <details id="ask-sources" class="mt-4 hidden rounded-lg border border-zinc-200 bg-zinc-50/80 p-4 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200">
                    <summary class="cursor-pointer text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        Retrieved chunks
                    </summary>
                    <div id="ask-chunks" class="mt-3 space-y-2"></div>
                </details>
            </div>
        </div>
    </div>

    <script>
        const messagesContainer = document.getElementById('messages');
        const emptyState = document.getElementById('empty-state');
        const askButton = document.getElementById('ask-button');
        const askButtonText = document.getElementById('ask-button-text');
        const questionInput = document.getElementById('question');
        const askError = document.getElementById('ask-error');
        const askSources = document.getElementById('ask-sources');
        const askChunks = document.getElementById('ask-chunks');
        const askModeSelect = document.getElementById('ask-mode');
        const askModeStatus = document.getElementById('ask-mode-status');
        const copyLastAnswerButton = document.getElementById('copy-last-answer');
        const regenerateButton = document.getElementById('regenerate-answer');
        const actionStatus = document.getElementById('chat-action-status');

        const messageState = @json($messages->map(fn ($message) => [
            'role' => $message->role,
            'content' => $message->content,
        ])->values());

        function setBusy(isBusy) {
            askButton.toggleAttribute('disabled', isBusy);
            copyLastAnswerButton.toggleAttribute('disabled', isBusy);
            regenerateButton.toggleAttribute('disabled', isBusy);
            askButtonText.textContent = isBusy ? 'Sending...' : 'Send';
        }

        function createMessageElement(message) {
            const wrapper = document.createElement('div');
            const isAssistant = message.role === 'assistant';
            wrapper.className = isAssistant
                ? 'rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-800 dark:bg-zinc-900'
                : 'rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 dark:border-emerald-900/60 dark:bg-emerald-950/40';

            const role = document.createElement('div');
            role.className = 'text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400';
            role.textContent = message.role;

            const content = document.createElement('p');
            content.className = 'mt-1 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200';
            content.textContent = message.content || '';

            wrapper.appendChild(role);
            wrapper.appendChild(content);

            return { wrapper, content };
        }

        function appendMessage(message) {
            if (emptyState) {
                emptyState.remove();
            }

            const { wrapper, content } = createMessageElement(message);
            messagesContainer.appendChild(wrapper);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            return content;
        }

        async function typeText(target, text) {
            target.textContent = '';

            for (let index = 0; index < text.length; index += 1) {
                target.textContent += text[index];
                if (index % 3 === 0) {
                    await new Promise((resolve) => setTimeout(resolve, 8));
                }
            }
        }

        function getLastMessageByRole(role) {
            for (let index = messageState.length - 1; index >= 0; index -= 1) {
                if (messageState[index].role === role) {
                    return messageState[index];
                }
            }

            return null;
        }

        async function askQuestion(question) {
            askError.classList.add('hidden');
            askSources.classList.add('hidden');
            askChunks.innerHTML = '';
            actionStatus.classList.add('hidden');
            setBusy(true);

            if (!question) {
                askError.textContent = 'Please enter a question.';
                askError.classList.remove('hidden');
                setBusy(false);
                return;
            }

            try {
                const response = await fetch('{{ route('chats.ask', $conversation) }}', {
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
                    setBusy(false);
                    return;
                }

                const payload = await response.json();
                const userMessage = { role: 'user', content: question };
                const assistantMessage = { role: 'assistant', content: payload.answer || '' };

                messageState.push(userMessage);
                appendMessage(userMessage);
                const assistantTarget = appendMessage({ role: 'assistant', content: '' });
                await typeText(assistantTarget, assistantMessage.content);
                messageState.push(assistantMessage);

                (payload.chunks || []).forEach((chunk) => {
                    const item = document.createElement('div');
                    item.className = 'rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200';
                    const prefix = document.createElement('div');
                    prefix.className = 'text-xs text-zinc-500 dark:text-zinc-400';
                    if (chunk.document_url) {
                        const link = document.createElement('a');
                        link.href = chunk.document_url;
                        link.className = 'underline hover:no-underline';
                        const shareLabel = chunk.relevance_share_pct !== null && chunk.relevance_share_pct !== undefined
                            ? ` - ${Number(chunk.relevance_share_pct).toFixed(1)}% relevance`
                            : '';
                        link.textContent = `#${chunk.id} ${chunk.document_title ? `(${chunk.document_title})` : ''}${shareLabel}`.trim();
                        prefix.appendChild(link);
                    } else {
                        const shareLabel = chunk.relevance_share_pct !== null && chunk.relevance_share_pct !== undefined
                            ? ` - ${Number(chunk.relevance_share_pct).toFixed(1)}% relevance`
                            : '';
                        prefix.textContent = `#${chunk.id}${shareLabel}`;
                    }

                    const text = document.createElement('div');
                    text.className = 'mt-1';
                    text.textContent = chunk.snippet;

                    item.appendChild(prefix);
                    item.appendChild(text);
                    askChunks.appendChild(item);
                });

                if ((payload.chunks || []).length > 0) {
                    askSources.classList.remove('hidden');
                }

                questionInput.value = '';
                setBusy(false);
            } catch (error) {
                askError.textContent = 'Network error while sending your question.';
                askError.classList.remove('hidden');
                setBusy(false);
            }
        }

        askButton.addEventListener('click', async () => {
            await askQuestion(questionInput.value.trim());
        });

        copyLastAnswerButton.addEventListener('click', async () => {
            const lastAssistantMessage = getLastMessageByRole('assistant');
            if (!lastAssistantMessage || !lastAssistantMessage.content) {
                actionStatus.textContent = 'No assistant answer to copy yet.';
                actionStatus.classList.remove('hidden');
                return;
            }

            try {
                await navigator.clipboard.writeText(lastAssistantMessage.content);
                actionStatus.textContent = 'Copied last answer.';
                actionStatus.classList.remove('hidden');
            } catch (error) {
                actionStatus.textContent = 'Copy failed in this browser context.';
                actionStatus.classList.remove('hidden');
            }
        });

        regenerateButton.addEventListener('click', async () => {
            const lastUserMessage = getLastMessageByRole('user');
            if (!lastUserMessage || !lastUserMessage.content) {
                actionStatus.textContent = 'No previous user message to regenerate.';
                actionStatus.classList.remove('hidden');
                return;
            }

            questionInput.value = lastUserMessage.content;
            await askQuestion(lastUserMessage.content);
        });

        askModeSelect.addEventListener('change', async () => {
            askModeStatus.classList.add('hidden');
            const mode = askModeSelect.value;

            try {
                const response = await fetch('{{ route('preferences.ask-mode') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ mode }),
                });

                if (!response.ok) {
                    return;
                }

                askModeStatus.classList.remove('hidden');
                setTimeout(() => askModeStatus.classList.add('hidden'), 2000);
            } catch (error) {
                // Ignore preference save errors to avoid blocking chat flow.
            }
        });
    </script>
@endsection
