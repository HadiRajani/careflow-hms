<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">{{ $patient->full_name }}
            <span class="ms-2 font-mono text-base text-gray-500">{{ $patient->mrn }}</span>
        </h2>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Patient details --}}
        <div class="bg-white rounded shadow-sm p-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div><div class="text-gray-500">{{ __('Age') }}</div><div class="font-semibold">{{ $patient->age() }}</div></div>
            <div><div class="text-gray-500">{{ __('Gender') }}</div><div class="font-semibold">{{ __(ucfirst($patient->gender)) }}</div></div>
            <div><div class="text-gray-500">{{ __('Mobile phone') }}</div><div class="font-semibold" dir="ltr">{{ $patient->phone }}</div></div>
            <div><div class="text-gray-500">CNIC</div><div class="font-semibold" dir="ltr">{{ $patient->cnic ?? '—' }}</div></div>
        </div>

        {{-- OPD visits (opening a visit comes in the next block) --}}
        <div class="bg-white rounded shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-3">{{ __('Recent OPD visits') }}</h3>
            @forelse ($visits as $visit)
                <div class="flex justify-between border-t py-2 text-sm">
                    <span>{{ $visit->arrived_at->format('d M Y, H:i') }}</span>
                    <span class="text-gray-600">{{ __(ucfirst($visit->status)) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('No visits yet.') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
