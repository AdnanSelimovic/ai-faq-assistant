@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Chats</h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Select a chat to continue or start a new one.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('chats.store') }}">
                    @csrf
                    <x-button type="submit">New chat</x-button>
                </form>
                <a
                    href="{{ route('kb.documents.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600"
                >
                    Knowledge base
                </a>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <label for="chat-search" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Search chats</label>
                <div class="mt-2">
                    <x-input id="chat-search" type="text" placeholder="Filter by title or recent message..." />
                </div>
            </div>

            <div id="chat-empty-filtered" class="hidden rounded-xl border border-dashed border-zinc-200 bg-white p-6 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                No chats match your search.
            </div>

            @forelse ($conversations as $conversation)
                <a
                    href="{{ route('chats.show', $conversation) }}"
                    class="chat-item block rounded-xl border border-zinc-200/70 bg-white p-4 shadow-sm transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
                    data-chat-title="{{ strtolower($conversation->title ?: 'Untitled chat') }}"
                    data-chat-snippet="{{ strtolower(\Illuminate\Support\Str::limit($conversation->last_message_content ?: '', 120)) }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $conversation->title ?: 'Untitled chat' }}
                            </h2>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $conversation->messages_count }} messages
                            </p>
                            @if (!empty($conversation->last_message_content))
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ \Illuminate\Support\Str::limit($conversation->last_message_content, 110) }}
                                </p>
                            @endif
                        </div>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $conversation->updated_at?->diffForHumans() }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-200 bg-white p-6 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                    No chats yet. Create your first chat to begin.
                </div>
            @endforelse
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('chat-search');
        const chatItems = Array.from(document.querySelectorAll('.chat-item'));
        const filteredEmpty = document.getElementById('chat-empty-filtered');

        if (searchInput && chatItems.length > 0) {
            searchInput.addEventListener('input', () => {
                const term = searchInput.value.trim().toLowerCase();
                let visible = 0;

                chatItems.forEach((item) => {
                    const title = item.dataset.chatTitle || '';
                    const snippet = item.dataset.chatSnippet || '';
                    const matches = term === '' || title.includes(term) || snippet.includes(term);
                    item.classList.toggle('hidden', !matches);
                    if (matches) {
                        visible += 1;
                    }
                });

                filteredEmpty.classList.toggle('hidden', visible > 0);
            });
        }
    </script>
@endsection
