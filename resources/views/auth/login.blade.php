<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Login</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            @include('partials.tailwind-fallback')
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full lg:max-w-md max-w-[335px]">
            <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] p-6 lg:p-8">
                <form method="POST" action="{{ url('/login') }}" class="space-y-6">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            class="mt-1 w-full rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] px-5 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-[#1915014a] dark:focus:border-[#62605b]"
                            placeholder="you@example.com"
                        />
                        @error('email')
                            <p class="mt-2 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-sm bg-[#1b1b18] text-white px-4 py-2 text-sm font-medium hover:bg-black"
                    >
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </body>
</html>


