<!DOCTYPE html>
{{-- Urdu is written right-to-left, so the whole page flips when Urdu is selected. --}}
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CareFlow HMS') }}</title>

        {{-- Fonts only (no patient data is ever sent to Google). Noto Naskh Arabic = clear Urdu on forms. --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased {{ app()->getLocale() === 'ur' ? 'font-urdu' : '' }}">
        <div class="min-h-screen bg-slate-50">
            <div class="bg-amber-100 py-1 text-center text-xs text-amber-900">
                {{ __('Demo system with synthetic data only. Not for clinical use.') }}
            </div>

            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-gray-200 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @if (session('success'))
                <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">✓ {{ session('success') }}</div>
                </div>
            @endif

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
