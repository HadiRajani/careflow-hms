<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Audit log</h2>
        <p class="text-sm text-gray-500">Who viewed or changed which patient record, and when. Entries cannot be edited or deleted.</p>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="mrn" value="{{ request('mrn') }}" placeholder="Patient MRN, e.g. CF-000042"
                   class="rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
            <select name="action" class="rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white">Filter</button>
            @if (request('mrn') || request('action'))
                <a href="{{ route('audit.index') }}" class="px-3 py-2 text-sm text-gray-500">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2">When</th>
                        <th class="px-4 py-2">Who</th>
                        <th class="px-4 py-2">Action</th>
                        <th class="px-4 py-2">Patient</th>
                        <th class="px-4 py-2">Details</th>
                        <th class="px-4 py-2">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-2 text-gray-600">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-2">
                                {{ $log->user?->name ?? '—' }}
                                @if ($log->user)<span class="text-xs text-gray-400">({{ $log->user->roleName() }})</span>@endif
                            </td>
                            <td class="px-4 py-2"><span class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs">{{ $log->action }}</span></td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $log->patient?->mrn ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">
                                @if ($log->meta)
                                    @foreach ($log->meta as $key => $value)
                                        <span class="me-2">{{ str_replace('_', ' ', $key) }}: <strong>{{ is_array($value) ? implode(', ', $value) : var_export($value, true) }}</strong></span>
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-400">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No audit entries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
</x-app-layout>
