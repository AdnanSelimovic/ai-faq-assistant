<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Create Knowledge Base Document</title>

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
                    New knowledge base document
                </span>
                <a
                    href="{{ route('kb.documents.index') }}"
                    class="rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3 py-1.5 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] hover:border-[#1915014a] dark:hover:border-[#62605b]"
                >
                    Back to list
                </a>
            </div>

            <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] p-6 lg:p-8">
                <div>
                    <h1 class="text-[13px] leading-[20px] font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Add document</h1>
                    <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Paste the full source text; it will be stored in metadata for indexing.
                    </p>
                </div>

                <form method="POST" action="{{ route('kb.documents.store') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="title" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Title</label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            value="{{ old('title') }}"
                            required
                            class="mt-1 w-full rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] px-4 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-[#1915014a] dark:focus:border-[#62605b]"
                        />
                        @error('title')
                            <p class="mt-2 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div>
                            <label for="source_type" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Source type</label>
                            <input
                                id="source_type"
                                name="source_type"
                                type="text"
                                value="{{ old('source_type') }}"
                                required
                                class="mt-1 w-full rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] px-4 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-[#1915014a] dark:focus:border-[#62605b]"
                                placeholder="faq, handbook, docs"
                            />
                            @error('source_type')
                                <p class="mt-2 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="source_ref" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Source reference</label>
                            <input
                                id="source_ref"
                                name="source_ref"
                                type="text"
                                value="{{ old('source_ref') }}"
                                class="mt-1 w-full rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] px-4 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-[#1915014a] dark:focus:border-[#62605b]"
                                placeholder="URL or internal ref"
                            />
                            @error('source_ref')
                                <p class="mt-2 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="raw_text" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Raw text</label>
                        <textarea
                            id="raw_text"
                            name="raw_text"
                            rows="10"
                            required
                            class="mt-1 w-full rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] px-4 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-[#1915014a] dark:focus:border-[#62605b]"
                            placeholder="Paste the full document text here..."
                        >{{ old('raw_text') }}</textarea>
                        @error('raw_text')
                            <p class="mt-2 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end">
                        <button
                            type="submit"
                            class="rounded-sm bg-[#1b1b18] text-white px-4 py-2 text-sm font-medium hover:bg-black"
                        >
                            Save document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
