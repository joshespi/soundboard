<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Soundboard') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        @include('partials.pwa-head')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900 px-6">
            <div class="w-16 h-16 mb-4 rounded-2xl bg-indigo-600 flex items-center justify-center">
                <x-application-logo class="w-9 h-9 fill-current text-white" />
            </div>

            <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">
                {{ config('app.name', 'Soundboard') }}
            </h1>

            <p class="mt-2 text-center text-gray-600 dark:text-gray-400 max-w-sm">
                Build your own soundboards from your uploaded audio clips, then play them from a big-button touch grid.
            </p>

            @if (Route::has('login'))
                <div class="mt-8 flex items-center gap-4">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            wire:navigate
                            class="rounded-md bg-gray-900 dark:bg-white px-5 py-2.5 text-sm font-semibold text-white dark:text-gray-900 hover:opacity-90"
                        >
                            Go to dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            wire:navigate
                            class="rounded-md bg-gray-900 dark:bg-white px-5 py-2.5 text-sm font-semibold text-white dark:text-gray-900 hover:opacity-90"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                wire:navigate
                                class="rounded-md px-5 py-2.5 text-sm font-semibold text-gray-900 dark:text-white ring-1 ring-gray-300 dark:ring-gray-700 hover:bg-white dark:hover:bg-gray-800"
                            >
                                Sign up
                            </a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>
    </body>
</html>
