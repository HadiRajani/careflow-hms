<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">{{ __('Triage queue') }}</h2>
                <p class="text-sm text-gray-500">{{ __('Oldest arrival first. Click a patient to start triage.') }}</p>
            </div>
            <span class="rounded-full bg-teal-50 px-3 py-1 text-sm font-semibold text-teal-700">
                {{ $waiting->count() }} {{ __('waiting') }}
            </span>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-3">

        {{-- Waiting patients --}}
        <x-card :title="__('Waiting for triage')" class="lg:col-span-2">
            <div class="-m-5 divide-y">
                @forelse ($waiting as $visit)
                    @php $minutes = (int) $visit->arrived_at->diffInMinutes(now()); @endphp
                    <a href="{{ route('triage.create', $visit) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-teal-50 transition">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $visit->patient->full_name }}</div>
                            <div class="text-sm text-gray-500">
                                <span class="font-mono">{{ $visit->patient->mrn }}</span> ·
                                {{ $visit->patient->age() }} {{ __('yrs') }} · {{ __(ucfirst($visit->patient->gender)) }}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-sm font-semibold {{ $minutes >= 30 ? 'text-red-600' : ($minutes >= 15 ? 'text-amber-600' : 'text-gray-700') }}" dir="ltr">
                                {{ $minutes }} min
                            </div>
                            <div class="text-xs text-gray-400">{{ __('arrived') }} {{ $visit->arrived_at->format('H:i') }}</div>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-gray-500">{{ __('No patients waiting. Well done!') }}</div>
                @endforelse
            </div>
        </x-card>

        {{-- Triaged today --}}
        <x-card :title="__('Triaged today')">
            <div class="-m-5 divide-y">
                @forelse ($triagedToday as $visit)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-gray-900 truncate">{{ $visit->patient->full_name }}</span>
                            <x-risk-badge :level="$visit->triage->risk_level" :score="$visit->triage->news2_score" />
                        </div>
                        @if (count($visit->triage->flags))
                            <div class="mt-1 text-xs text-gray-500">
                                {{ collect($visit->triage->flags)->map(fn ($f) => __(\App\Services\TriageScorer::flagLabel($f)))->join(', ') }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-500">{{ __('Nothing yet today.') }}</div>
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>
