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
            @forelse ($conversations as $conversation)
                <a
                    href="{{ route('chats.show', $conversation) }}"
                    class="block rounded-xl border border-zinc-200/70 bg-white p-4 shadow-sm transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $conversation->title ?: 'Untitled chat' }}
                            </h2>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $conversation->messages_count }} messages
                            </p>
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
@endsection

