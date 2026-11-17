<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">{{ __('Patients') }}</h2>
            <a href="{{ route('patients.create') }}" class="rounded bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                + {{ __('Register patient') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        {{-- Search --}}
        <form method="GET" action="{{ route('patients.index') }}" class="flex gap-2">
            <input type="text" name="q" value="{{ $search }}" autofocus
                   placeholder="{{ __('Search by name, phone, CNIC or MRN') }}"
                   class="flex-1 rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500">
            <button class="rounded bg-gray-800 px-4 py-2 text-sm font-semibold text-white">{{ __('Search') }}</button>
        </form>

        {{-- Results --}}
        @if ($search !== '')
            <div class="bg-white rounded shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-start">
                        <tr>
                            <th class="px-4 py-2 text-start">{{ __('MRN') }}</th>
                            <th class="px-4 py-2 text-start">{{ __('Name') }}</th>
                            <th class="px-4 py-2 text-start">{{ __('Age') }}</th>
                            <th class="px-4 py-2 text-start">{{ __('Phone') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($patients as $patient)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $patient->mrn }}</td>
                                <td class="px-4 py-2">{{ $patient->full_name }}</td>
                                <td class="px-4 py-2">{{ $patient->age() }}</td>
                                <td class="px-4 py-2">{{ $patient->phone }}</td>
                                <td class="px-4 py-2 text-end">
                                    <a href="{{ route('patients.show', $patient) }}" class="text-teal-700 hover:underline">{{ __('Open') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('No patient found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">{{ __('Search for a patient first. If they are new, register them.') }}</p>
        @endif
    </div>
</x-app-layout>
