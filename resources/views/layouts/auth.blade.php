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
        <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-12 sm:px-6">
            <div class="mb-8 text-center">
                <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">AI FAQ Assistant</h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Sign in to manage your knowledge base.</p>
            </div>

            <div class="rounded-xl border border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                @yield('content')
            </div>
        </main>
    </body>
</html>
