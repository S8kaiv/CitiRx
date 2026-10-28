<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1, viewport-fit=cover"
        >

        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        >

        <meta
            name="theme-color"
            content="#f8fafc"
        >

        <meta
            name="color-scheme"
            content="light"
        >

        <title>{{ config('app.name', 'CitiRx') }}</title>

        <link
            rel="preconnect"
            href="https://fonts.bunny.net"
        >

        <link
            href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap"
            rel="stylesheet"
        >

        @vite([
            'resources/css/app.css',
            'resources/js/app.js',
        ])
    </head>

    <body class="bg-canvas font-sans text-slate-900 antialiased">
        @if ($attributes->has('fullscreen'))
            {{ $slot }}
        @else
            <div class="flex min-h-screen flex-col items-center bg-canvas pt-6 sm:justify-center sm:pt-0">
                <div>
                    <a href="/">
                        <x-application-logo class="h-20 w-20 fill-current text-slate-500" />
                    </a>
                </div>

                <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </body>
</html>
