<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Knowledge Base Documents</title>

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
                    Knowledge base documents
                </span>
                <a
                    href="{{ route('kb.documents.create') }}"
                    class="rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3 py-1.5 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] hover:border-[#1915014a] dark:hover:border-[#62605b]"
                >
                    New document
                </a>
            </div>

            <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] p-6 lg:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-[13px] leading-[20px] font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Documents</h1>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Manage raw source text and indexing status.
                        </p>
                    </div>
                    <a
                        href="{{ route('dashboard') }}"
                        class="hidden lg:inline-flex items-center rounded-sm border border-[#19140035] dark:border-[#3E3E3A] px-3 py-1.5 text-[13px] text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Back to dashboard
                    </a>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($documents as $document)
                        <a
                            href="{{ route('kb.documents.show', $document) }}"
                            class="block rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-[#FDFDFC] dark:bg-[#0a0a0a] p-4 hover:border-[#1915014a] dark:hover:border-[#62605b]"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ $document->title }}
                                    </div>
                                    <div class="mt-1 text-[13px] text-[#706f6c] dark:text-[#A1A09A]">
                                        {{ $document->source_type }}
                                        @if ($document->source_ref)
                                            · {{ $document->source_ref }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right text-[13px] text-[#706f6c] dark:text-[#A1A09A]">
                                    <div>Status: {{ $document->status }}</div>
                                    <div>Chunks: {{ $document->chunks_count }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-sm border border-dashed border-[#e3e3e0] dark:border-[#3E3E3A] p-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No documents yet. Create your first entry to begin indexing.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </body>
</html>
