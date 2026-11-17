<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Runs on every page: reads the language saved in the session ('en' or 'ur')
 * and tells Laravel to use it. Then {{ __('Save patient') }} in a view returns
 * the Urdu text from lang/ur.json when Urdu is selected.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', 'en');

        if (in_array($locale, ['en', 'ur'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
