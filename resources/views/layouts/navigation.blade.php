{{-- Top menu. Each role only SEES its own links. (Real protection is the 'role' middleware on the routes.) --}}
@php $role = Auth::user()->role; @endphp

<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
            <div class="flex items-center gap-6 text-sm font-medium">
                <a href="/" class="text-lg font-bold text-teal-700">CareFlow HMS</a>

                @if ($role === 'nurse')
                    <a href="{{ route('visits.queue') }}" class="text-gray-600 hover:text-teal-700">{{ __('Triage queue') }}</a>
                @endif

                @if ($role === 'front_desk' || $role === 'nurse')
                    <a href="{{ route('patients.index') }}" class="text-gray-600 hover:text-teal-700">{{ __('Patients') }}</a>
                @endif

                @if ($role === 'quality_manager')
                    <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-teal-700">{{ __('Dashboard') }}</a>
                    <a href="{{ route('audit.index') }}" class="text-gray-600 hover:text-teal-700">{{ __('Audit log') }}</a>
                @endif
            </div>

            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-700">
                    {{ Auth::user()->name }}
                    <span class="ms-1 rounded bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700">{{ __(Auth::user()->roleName()) }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-red-600">{{ __('Log out') }}</button>
                </form>
            </div>
        </div>
    </div>
</nav>
