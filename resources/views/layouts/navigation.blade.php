@php
    use App\Enums\Role;
    $user = Auth::user();
    // Menu links per role. Hiding links is only convenience — routes are protected by the 'role' middleware.
    $links = match ($user->role) {
        Role::FrontDesk => [['patients.index', 'Patients']],
        Role::Nurse => [['visits.queue', 'Triage queue'], ['patients.index', 'Patients']],
        Role::QualityManager => [['dashboard', 'Dashboard'], ['audit.index', 'Audit log']],
    };
@endphp
<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
            <div class="flex items-center gap-6">
                <a href="{{ route($user->role->homeRoute()) }}" class="text-lg font-bold text-teal-700">CareFlow HMS</a>
                @foreach ($links as [$route, $label])
                    <a href="{{ route($route) }}"
                       class="text-sm font-medium {{ request()->routeIs($route) ? 'text-teal-700 border-b-2 border-teal-600' : 'text-gray-600 hover:text-gray-900' }}">
                        {{ __($label) }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-700">
                    {{ $user->name }}
                    <span class="ms-1 rounded bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700">{{ __($user->role->label()) }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-red-600">{{ __('Log out') }}</button>
                </form>
            </div>
        </div>
    </div>
</nav>
