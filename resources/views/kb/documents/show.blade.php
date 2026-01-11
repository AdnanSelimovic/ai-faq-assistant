<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $document->title }}</title>

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
                    Document details
                </span>
                <a
                    href="{{ route('kb.documents.index') }}"
                    class="rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3 py-1.5 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] hover:border-[#1915014a] dark:hover:border-[#62605b]"
                >
                    Back to list
                </a>
            </div>

            <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] p-6 lg:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-[13px] leading-[20px] font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ $document->title }}</h1>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {{ $document->source_type }}
                            @if ($document->source_ref)
                                · {{ $document->source_ref }}
                            @endif
                        </p>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Status: {{ $document->status }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('kb.documents.index-document', $document) }}">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-sm bg-[#1b1b18] text-white px-4 py-2 text-sm font-medium hover:bg-black"
                        >
                            Run indexing
                        </button>
                    </form>
                </div>

                @if ($errors->has('index'))
                    <p class="mt-4 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $errors->first('index') }}</p>
                @endif
                @if (!empty($document->meta['error']))
                    <p class="mt-4 text-sm text-[#F53003] dark:text-[#FF4433]">
                        {{ $document->meta['error'] }}
                    </p>
                @endif

                <div class="mt-6 rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-[#FDFDFC] dark:bg-[#0a0a0a] p-4">
                    <div class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Raw text</div>
                    <pre class="mt-2 whitespace-pre-wrap text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $document->meta['raw_text'] ?? '' }}</pre>
                </div>

                <div class="mt-6">
                    <div class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Chunks</div>
                    <div class="mt-3 space-y-3">
                        @forelse ($chunks as $chunk)
                            <div class="rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-[#FDFDFC] dark:bg-[#0a0a0a] p-4">
                                <div class="text-[13px] text-[#706f6c] dark:text-[#A1A09A]">
                                    Chunk {{ $chunk->chunk_index }} · {{ $chunk->content_hash }}
                                </div>
                                <div class="mt-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                    {{ $chunk->content }}
                                </div>
                            </div>
                        @empty
                            <div class="rounded-sm border border-dashed border-[#e3e3e0] dark:border-[#3E3E3A] p-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                No chunks yet. Run indexing to generate them.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
