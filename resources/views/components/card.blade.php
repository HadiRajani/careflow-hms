{{-- White panel used on every page. Usage: <x-card title="Recent visits"> ... </x-card> --}}
@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 shadow-sm']) }}>
    @if ($title)
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">{{ $title }}</div>
    @endif
    <div class="p-5">{{ $slot }}</div>
</div>
