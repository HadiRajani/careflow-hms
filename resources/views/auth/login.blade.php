<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" dir="ltr" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" dir="ltr" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500" name="remember">
            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
        </label>

        <button type="submit" class="w-full rounded-lg bg-teal-600 py-2.5 font-semibold text-white shadow hover:bg-teal-700">
            {{ __('Log in') }}
        </button>
    </form>

    {{-- Demo accounts: shown only outside production-like "real" use; this is a synthetic-data demo. --}}
    <div class="mt-6 rounded-lg bg-slate-50 p-4 text-xs text-gray-600" dir="ltr">
        <div class="mb-2 font-semibold text-gray-700">Demo accounts (password in README)</div>
        <ul class="space-y-1 font-mono">
            <li>frontdesk@careflow.test <span class="text-gray-400">— Front Desk</span></li>
            <li>nurse@careflow.test <span class="text-gray-400">— Nurse</span></li>
            <li>quality@careflow.test <span class="text-gray-400">— Quality Manager</span></li>
        </ul>
    </div>
</x-guest-layout>
