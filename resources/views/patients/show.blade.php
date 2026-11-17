<x-app-layout>
    @php $waitingVisit = $visits->first(fn ($v) => $v->status === 'waiting' && $v->arrived_at->isToday()); @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">{{ $patient->full_name }}</h2>
                <p class="text-sm text-gray-500"><span class="font-mono">{{ $patient->mrn }}</span> · {{ __('registered') }} {{ $patient->created_at->format('d M Y') }}</p>
            </div>

            @if ($waitingVisit)
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">{{ __('Waiting for triage') }}</span>
                    @if (auth()->user()->role === 'nurse')
                        <a href="{{ route('triage.create', $waitingVisit) }}" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">{{ __('Start triage') }}</a>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('visits.store', $patient) }}">
                    @csrf
                    <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-teal-700">+ {{ __('Open OPD visit') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Patient details --}}
        <x-card>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div><dt class="text-gray-500">{{ __('Age') }}</dt><dd class="font-semibold text-gray-900">{{ $patient->age() }} {{ __('yrs') }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Date of birth') }}</dt><dd class="font-semibold text-gray-900">{{ $patient->date_of_birth->format('d M Y') }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Gender') }}</dt><dd class="font-semibold text-gray-900">{{ __(ucfirst($patient->gender)) }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Mobile phone') }}</dt><dd class="font-semibold text-gray-900" dir="ltr">{{ $patient->phone }}</dd></div>
                <div><dt class="text-gray-500">CNIC</dt><dd class="font-semibold text-gray-900" dir="ltr">{{ $patient->cnic ?? '—' }}</dd></div>
            </dl>
        </x-card>

        {{-- Visit history --}}
        <x-card :title="__('Recent OPD visits')">
            <div class="-m-5 divide-y">
                @forelse ($visits as $visit)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-gray-900">{{ $visit->arrived_at->format('d M Y, H:i') }}</span>
                            @if ($visit->triage)
                                <x-risk-badge :level="$visit->triage->risk_level" :score="$visit->triage->news2_score" />
                            @elseif ($visit->arrived_at->isToday())
                                <span class="text-sm text-amber-700">{{ __('Waiting for triage') }}</span>
                            @else
                                <span class="text-sm text-gray-500">{{ __('Left without triage') }}</span>
                            @endif
                        </div>

                        @if ($visit->triage)
                            @php $t = $visit->triage; @endphp
                            <div class="mt-2 grid grid-cols-3 sm:grid-cols-6 gap-2 text-xs text-gray-600" dir="ltr">
                                <span>BP <strong>{{ $t->systolic_bp }}/{{ $t->diastolic_bp }}</strong></span>
                                <span>{{ __('Pulse') }} <strong>{{ $t->pulse }}</strong></span>
                                <span>{{ __('Temp') }} <strong>{{ $t->temperature }}°C</strong></span>
                                <span>SpO2 <strong>{{ $t->spo2 }}%</strong></span>
                                <span>RR <strong>{{ $t->respiratory_rate }}</strong></span>
                                <span>{{ __('Pain') }} <strong>{{ $t->pain_score }}/10</strong></span>
                            </div>
                            <p class="mt-2 text-sm text-gray-700">“{{ $t->chief_complaint }}”</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('Allergies') }}: {{ $t->allergies }} · {{ __('Fall risk') }}: {{ __(ucfirst($t->fall_risk)) }} ·
                                {{ __('Recorded by') }} {{ $t->recorder->name }}, {{ $t->recorded_at->format('H:i') }}
                            </p>
                            @if (count($t->flags))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($t->flags as $flag)
                                        <span class="rounded bg-red-50 px-2 py-0.5 text-xs text-red-700">{{ __(\App\Services\TriageScorer::flagLabel($flag)) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500">{{ __('No visits yet.') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>
