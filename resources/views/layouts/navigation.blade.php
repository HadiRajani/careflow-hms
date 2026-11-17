{{-- Top menu. Each role only SEES its own links. (Real protection is the 'role' middleware on the routes.) --}}
@php
    $role = Auth::user()->role;

    $links = [];
    if ($role === 'nurse') {
        $links[] = ['visits.queue', 'Triage queue', 'triage*'];
    }
    if ($role === 'front_desk' || $role === 'nurse') {
        $links[] = ['patients.index', 'Patients', 'patients*'];
    }
    if ($role === 'quality_manager') {
        $links[] = ['dashboard', 'Dashboard', 'dashboard'];
        $links[] = ['audit.index', 'Audit log', 'audit*'];
    }
@endphp

<nav class="border-b border-gray-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
            <div class="flex items-center gap-6">
                <a href="/" class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 font-bold text-white">+</span>
                    <span class="text-lg font-bold text-gray-900" dir="ltr">CareFlow</span>
                </a>

                <div class="flex items-center gap-1 text-sm font-medium">
                    @foreach ($links as [$route, $label, $pattern])
                        <a href="{{ route($route) }}"
                           class="rounded-lg px-3 py-2 {{ request()->is($pattern) ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                            {{ __($label) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-4 text-sm">
                <x-language-toggle />
                <div class="text-end leading-tight">
                    <div class="font-medium text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-teal-700">{{ __(Auth::user()->roleName()) }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-600 hover:border-red-200 hover:text-red-600">{{ __('Log out') }}</button>
                </form>
            </div>
        </div>
    </div>
</nav>
