<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CareFlow HMS') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased {{ app()->getLocale() === 'ur' ? 'font-urdu' : '' }}">
        <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-teal-50 via-white to-slate-100 px-4 py-10">
            <div class="mb-4 self-end sm:absolute sm:end-6 sm:top-6">
                <x-language-toggle />
            </div>

            <div class="mb-6 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-600 text-2xl font-bold text-white shadow-lg">+</div>
                <h1 class="mt-3 text-2xl font-bold text-gray-900">CareFlow HMS</h1>
                <p class="text-sm text-gray-500">{{ __('Outpatient registration, triage and quality') }}</p>
            </div>

            <div class="w-full rounded-2xl border border-gray-200 bg-white px-6 py-6 shadow-xl sm:max-w-md">
                {{ $slot }}
            </div>

            <p class="mt-6 max-w-md text-center text-xs text-amber-800">
                {{ __('Demo system with synthetic data only. Not for clinical use.') }}
            </p>
        </div>
    </body>
</html>
