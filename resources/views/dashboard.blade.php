<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Dashboard</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            @include('partials.tailwind-fallback')
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full lg:max-w-4xl max-w-[335px]">
            <div class="mb-6 flex items-center justify-between text-sm text-[#706f6c] dark:text-[#A1A09A]">
                <span class="inline-flex items-center gap-3">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#F53003] dark:bg-[#FF4433]"></span>
                    Knowledge base dashboard
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3 py-1.5 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] hover:border-[#1915014a] dark:hover:border-[#62605b]"
                    >
                        Log out
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] p-6 lg:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-[13px] leading-[20px] font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Dashboard</h1>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            You are signed in with the single-user email.
                        </p>
                    </div>
                    <span class="hidden lg:inline-flex items-center rounded-sm border border-[#19140035] dark:border-[#3E3E3A] px-3 py-1.5 text-[13px] text-[#1b1b18] dark:text-[#EDEDEC]">
                        {{ config('app.name', 'AI FAQ Assistant') }}
                    </span>
                </div>

                <div class="mt-6 rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-[#FDFDFC] dark:bg-[#0a0a0a] p-4">
                    <label for="question" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Ask a question
                    </label>
                    <textarea
                        id="question"
                        rows="4"
                        class="mt-2 w-full rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-[#1915014a] dark:focus:border-[#62605b]"
                        placeholder="What are the hours of support for premium customers?"
                    ></textarea>
                    <div class="mt-3 flex justify-end">
                        <button
                            type="button"
                            class="rounded-sm bg-[#1b1b18] text-white px-4 py-2 text-sm font-medium hover:bg-black"
                        >
                            Ask
                        </button>
                    </div>
                </div>

                <div class="mt-6 border-t border-[#e3e3e0] dark:border-[#3E3E3A] pt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Responses will appear here once retrieval is enabled.
                </div>
            </div>
        </div>
    </body>
</html>


