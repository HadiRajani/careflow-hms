<?php

use Illuminate\Support\Facades\Route;

/*
 * Who can open what. Checked on the server by the 'role' middleware
 * (app/Http/Middleware/EnsureRole.php). Wrong role = 403 error.
 *
 *   front_desk       -> patients
 *   nurse            -> patients + triage
 *   quality_manager  -> dashboard + audit log
 */

// Home: logged-in users go to their role's page, everyone else to login.
Route::get('/', function () {
    if (auth()->check()) {
        return redirect(auth()->user()->homePage());
    }
    return redirect('/login');
});

// Everything below needs a logged-in user ('auth').
Route::middleware('auth')->group(function () {

    // Front desk AND nurse
    Route::middleware('role:front_desk,nurse')->group(function () {
        Route::get('/patients', fn () => view('placeholder', ['title' => 'Patients']))->name('patients.index');
    });

    // Nurse only
    Route::middleware('role:nurse')->group(function () {
        Route::get('/triage', fn () => view('placeholder', ['title' => 'Triage queue']))->name('visits.queue');
    });

    // Quality manager only
    Route::middleware('role:quality_manager')->group(function () {
        Route::get('/dashboard', fn () => view('placeholder', ['title' => 'Dashboard']))->name('dashboard');
        Route::get('/audit', fn () => view('placeholder', ['title' => 'Audit log']))->name('audit.index');
    });
});

require __DIR__.'/auth.php';
