<x-app-layout>
    @php $patient = $visit->patient; @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">{{ __('Triage') }}: {{ $patient->full_name }}</h2>
                <p class="text-sm text-gray-500">
                    <span class="font-mono">{{ $patient->mrn }}</span> · {{ $patient->age() }} {{ __('yrs') }} · {{ __(ucfirst($patient->gender)) }}
                    · {{ __('arrived') }} {{ $visit->arrived_at->format('H:i') }}
                </p>
            </div>
            <a href="{{ route('visits.queue') }}" class="text-sm text-gray-500 hover:text-gray-800">← {{ __('Back to queue') }}</a>
        </div>
    </x-slot>

    @if ($patient->age() < 16)
        <div class="max-w-5xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                ⚠ {{ __('Patient is under 16. NEWS2 is validated for adults only — use clinical judgement.') }}
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('triage.store', $visit) }}" class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @csrf

        {{-- 1. Vital signs --}}
        <x-card :title="__('Vital signs')">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                <x-vital-input name="systolic_bp" label="BP systolic" unit="mmHg" min="50" max="260" hint="Normal" range="111–219" autofocus />
                <x-vital-input name="diastolic_bp" label="BP diastolic" unit="mmHg" min="20" max="160" hint="Crisis if" range="≥ 120" />
                <x-vital-input name="pulse" label="Pulse" unit="bpm" min="20" max="250" hint="Normal" range="51–90" />
                <x-vital-input name="temperature" label="Temperature" unit="°C" min="30" max="45" step="0.1" hint="Normal" range="36.1–38.0" />
                <x-vital-input name="spo2" label="SpO2" unit="%" min="50" max="100" hint="Normal" range="≥ 96" />
                <x-vital-input name="respiratory_rate" label="Respiratory rate" unit="/min" min="4" max="60" hint="Normal" range="12–20" />
            </div>
        </x-card>

        {{-- 2. Complaint --}}
        <x-card :title="__('Chief complaint')">
            <textarea name="chief_complaint" rows="2" required maxlength="500"
                      placeholder="{{ __('e.g. Fever and cough for 3 days') }}"
                      class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">{{ old('chief_complaint') }}</textarea>
            @error('chief_complaint') <p class="mt-1 text-sm text-red-600">{{ __($message) }}</p> @enderror
        </x-card>

        {{-- 3. One-tap fields with safe defaults --}}
        <x-card :title="__('Assessment')">
            <div class="space-y-5">

                {{-- Consciousness (AVPU) --}}
                <div>
                    <div class="text-sm font-medium text-gray-700 mb-2">{{ __('Consciousness (AVPU)') }}</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['A' => 'Alert', 'V' => 'Responds to voice', 'P' => 'Responds to pain', 'U' => 'Unresponsive'] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="consciousness" value="{{ $value }}" class="peer sr-only"
                                       @checked(old('consciousness', 'A') === $value)>
                                <span class="block rounded-lg border border-gray-300 px-3 py-2 text-sm peer-checked:border-teal-600 peer-checked:bg-teal-600 peer-checked:text-white hover:border-teal-400">
                                    <strong>{{ $value }}</strong> · {{ __($label) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Pain score 0-10 --}}
                <div>
                    <div class="text-sm font-medium text-gray-700 mb-2">{{ __('Pain score') }} (0-10)</div>
                    <div class="flex flex-wrap gap-1.5" dir="ltr">
                        @for ($i = 0; $i <= 10; $i++)
                            @php $tone = $i >= 7 ? 'peer-checked:bg-red-600 peer-checked:border-red-600' : ($i >= 4 ? 'peer-checked:bg-amber-500 peer-checked:border-amber-500' : 'peer-checked:bg-teal-600 peer-checked:border-teal-600'); @endphp
                            <label class="cursor-pointer">
                                <input type="radio" name="pain_score" value="{{ $i }}" class="peer sr-only" @checked((string) old('pain_score', '0') === (string) $i)>
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 font-semibold peer-checked:text-white {{ $tone }} hover:border-teal-400">{{ $i }}</span>
                            </label>
                        @endfor
                    </div>
                    @error('pain_score') <p class="mt-1 text-sm text-red-600">{{ __($message) }}</p> @enderror
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    {{-- Allergies --}}
                    <div>
                        <label for="allergies" class="block text-sm font-medium text-gray-700">{{ __('Allergies') }}</label>
                        <input type="text" id="allergies" name="allergies" value="{{ old('allergies', 'NKDA') }}" required maxlength="255"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        <p class="mt-1 text-xs text-gray-400">{{ __('NKDA = no known drug allergies. Replace if the patient has any.') }}</p>
                        @error('allergies') <p class="mt-1 text-sm text-red-600">{{ __($message) }}</p> @enderror
                    </div>

                    {{-- Fall risk --}}
                    <div>
                        <div class="text-sm font-medium text-gray-700 mb-2">{{ __('Fall risk') }}</div>
                        <div class="flex gap-2">
                            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                                <label class="cursor-pointer flex-1">
                                    <input type="radio" name="fall_risk" value="{{ $value }}" class="peer sr-only" @checked(old('fall_risk', 'low') === $value)>
                                    <span class="block rounded-lg border border-gray-300 px-3 py-2 text-center text-sm peer-checked:border-teal-600 peer-checked:bg-teal-600 peer-checked:text-white hover:border-teal-400">{{ __($label) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Save --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-gray-500">
                {{ __('Recorded by') }} <strong>{{ auth()->user()->name }}</strong> · {{ __('time is set automatically') }}.
                {{ __('NEWS2 score is calculated by the system after saving.') }}
            </p>
            <button class="rounded-lg bg-teal-600 px-8 py-3 text-base font-semibold text-white shadow hover:bg-teal-700">
                {{ __('Save triage') }}
            </button>
        </div>
    </form>
</x-app-layout>
