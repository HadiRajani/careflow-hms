<?php

namespace App\Http\Controllers;

class LanguageController extends Controller
{
    /**
     * GET /language/ur  or  /language/en
     * Saves the choice in the session; the SetLocale middleware applies it on every request.
     */
    public function switch(string $locale)
    {
        if (! in_array($locale, ['en', 'ur'])) {
            abort(404);   // only English and Urdu are supported
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
