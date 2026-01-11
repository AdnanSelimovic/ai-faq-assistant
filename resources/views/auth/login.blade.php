@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <div>
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Welcome back</h2>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            Use the single-user email to access the knowledge base.
        </p>
    </div>

    <form method="POST" action="{{ url('/login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Email address</label>
            <div class="mt-2">
                <x-input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                />
                @error('email')
                    <x-form-error :message="$message" />
                @enderror
            </div>
        </div>

        <x-button type="submit" class="w-full">Sign in</x-button>
    </form>
@endsection
