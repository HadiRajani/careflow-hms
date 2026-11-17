{{-- Coloured risk label. Usage: <x-risk-badge level="high" :score="7" /> --}}
@props(['level', 'score' => null])

@php
    $colours = [
        'high' => 'bg-red-100 text-red-800 ring-red-200',
        'medium' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'low' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
    ];
@endphp

<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $colours[$level] ?? 'bg-gray-100 text-gray-700 ring-gray-200' }}">
    {{ __(ucfirst($level) . ' risk') }}
    @if (! is_null($score))
        <span class="opacity-75" dir="ltr">· NEWS2 {{ $score }}</span>
    @endif
</span>
