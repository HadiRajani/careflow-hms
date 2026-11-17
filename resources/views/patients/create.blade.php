<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">{{ __('Register patient') }}</h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        {{-- Duplicate warning: shown when the server found likely matches --}}
        @if (count($duplicates) > 0)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4">
                <p class="font-semibold text-amber-900">⚠ {{ __('This patient may already be registered') }}</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($duplicates as $match)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded bg-white p-3 text-sm">
                            <span>
                                <span class="font-mono">{{ $match['patient']->mrn }}</span> ·
                                <strong>{{ $match['patient']->full_name }}</strong> ·
                                {{ $match['patient']->date_of_birth->format('d M Y') }} ·
                                {{ $match['patient']->phone }}
                                <span class="block text-xs text-amber-800">{{ __($match['reason']) }}</span>
                            </span>
                            <a href="{{ route('patients.show', $match['patient']) }}"
                               class="rounded-lg bg-teal-600 px-3 py-1.5 text-white font-semibold">{{ __('Use this patient') }}</a>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-amber-900">{{ __('If you are sure this is a different person, tick the box below and save again. This will be recorded in the audit log.') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('patients.store') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Full name') }} *</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" required autofocus
                       class="mt-1 w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                <p class="text-xs text-gray-500 mt-1">{{ __('In English letters, as on the CNIC.') }}</p>
                @error('full_name') <p class="text-sm text-red-600 mt-1">{{ __($message) }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Date of birth') }} *</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required max="{{ date('Y-m-d') }}"
                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    @error('date_of_birth') <p class="text-sm text-red-600 mt-1">{{ __($message) }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Gender') }} *</label>
                    <select name="gender" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">—</option>
                        @foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    @error('gender') <p class="text-sm text-red-600 mt-1">{{ __($message) }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Mobile phone') }} *</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="0300-1234567"
                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500" dir="ltr">
                    @error('phone') <p class="text-sm text-red-600 mt-1">{{ __($message) }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('CNIC (optional)') }}</label>
                    <input type="text" name="cnic" value="{{ old('cnic') }}" placeholder="42101-1234567-1"
                           class="mt-1 w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500" dir="ltr">
                    @error('cnic') <p class="text-sm text-red-600 mt-1">{{ __($message) }}</p> @enderror
                </div>
            </div>

            @if (count($duplicates) > 0)
                <label class="flex items-center gap-2 text-sm text-amber-900">
                    <input type="checkbox" name="confirm_not_duplicate" value="1" class="rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    {{ __('I have checked: this is a different person') }}
                </label>
            @endif

            <div class="flex justify-end gap-3">
                <a href="{{ route('patients.index') }}" class="px-4 py-2 text-sm text-gray-600">{{ __('Cancel') }}</a>
                <button class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700">{{ __('Save patient') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
