<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    // GET /login -> show the login form
    public function create()
    {
        return view('auth.login');
    }

    // POST /login -> check email + password, then send the user to their role's page
    public function store(LoginRequest $request)
    {
        $request->authenticate();            // checks password + blocks after 5 wrong tries (see LoginRequest)
        $request->session()->regenerate();   // new session id after login (security best practice)

        return redirect($request->user()->homePage());   // nurse -> /triage, etc.
    }

    // POST /logout -> end the session completely
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
