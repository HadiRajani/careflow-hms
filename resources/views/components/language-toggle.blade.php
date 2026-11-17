{{-- English | اردو switch. Saves the choice in the session (see LanguageController). --}}
<div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5 text-xs font-semibold" dir="ltr">
    <a href="{{ route('language.switch', 'en') }}"
       class="rounded-md px-2.5 py-1 {{ app()->getLocale() === 'en' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">English</a>
    <a href="{{ route('language.switch', 'ur') }}" lang="ur"
       class="rounded-md px-2.5 py-1 font-urdu {{ app()->getLocale() === 'ur' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">اردو</a>
</div>
