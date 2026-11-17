{{--
    One vital sign box.
    Usage: <x-vital-input name="pulse" label="Pulse" unit="bpm" min="20" max="250" hint="Normal" range="51-90" />

    The number range is shown in its own left-to-right box (<bdi dir="ltr">). Without it, the Urdu
    (right-to-left) screen displayed "51-90" as "90-51" — a misread range is a clinical-safety risk.
--}}
@props(['name', 'label', 'unit', 'min', 'max', 'step' => 1, 'hint' => null, 'range' => null, 'autofocus' => false])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ __($label) }}</label>
    <div class="mt-1 flex rounded-lg shadow-sm" dir="ltr">
        <input type="number" id="{{ $name }}" name="{{ $name }}" value="{{ old($name) }}"
               min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" inputmode="decimal" required
               @if ($autofocus) autofocus @endif
               class="w-full rounded-s-lg border-gray-300 text-lg font-semibold focus:border-teal-500 focus:ring-teal-500 @error($name) border-red-400 @enderror">
        <span class="inline-flex items-center rounded-e-lg border border-s-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">{{ $unit }}</span>
    </div>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-400">{{ __($hint) }} <bdi dir="ltr" class="font-sans">{{ $range }}</bdi></p>
    @endif
    @error($name) <p class="mt-1 text-sm text-red-600">{{ __($message) }}</p> @enderror
</div>
