<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', 'AI FAQ Assistant')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            @include('partials.tailwind-fallback')
        @endif
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <header class="border-b border-zinc-200/70 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        AI FAQ Assistant
                    </a>
                    <nav class="hidden items-center gap-4 text-sm md:flex">
                        <a
                            href="{{ route('dashboard') }}"
                            class="transition {{ request()->routeIs('dashboard') ? 'text-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
                        >
                            Dashboard
                        </a>
                        <a
                            href="{{ route('kb.documents.index') }}"
                            class="transition {{ request()->routeIs('kb.documents.*') ? 'text-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
                        >
                            Knowledge Base
                        </a>
                    </nav>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button variant="secondary" type="submit">Log out</x-button>
                </form>
            </div>
            <div class="mx-auto w-full max-w-6xl px-4 pb-3 sm:px-6 lg:px-8 md:hidden">
                <nav class="flex items-center gap-4 text-sm">
                    <a
                        href="{{ route('dashboard') }}"
                        class="transition {{ request()->routeIs('dashboard') ? 'text-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
                    >
                        Dashboard
                    </a>
                    <a
                        href="{{ route('kb.documents.index') }}"
                        class="transition {{ request()->routeIs('kb.documents.*') ? 'text-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
                    >
                        Knowledge Base
                    </a>
                </nav>
            </div>
        </header>

        <main class="py-8">
            <div class="mx-auto w-full @yield('container-class', 'max-w-5xl') px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </body>
</html>
