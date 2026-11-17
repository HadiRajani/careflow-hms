<?php

use Illuminate\Support\Facades\Route;

/*
 * Access map (enforced server-side by the 'role' middleware, see App\Http\Middleware\EnsureRole):
 *
 *   front_desk       -> register / search patients, open OPD visits
 *   nurse            -> everything front_desk can do + triage queue and triage form
 *   quality_manager  -> dashboard + audit log only (aggregate view, no clinical data entry)
 */

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->role->homeRoute())
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {

    // Front desk + nurse: patient registration and visits
    Route::middleware('role:front_desk,nurse')->group(function () {
        Route::get('/patients', fn () => view('placeholder', ['title' => 'Patients']))->name('patients.index');
    });

    // Nurse only: triage
    Route::middleware('role:nurse')->group(function () {
        Route::get('/triage', fn () => view('placeholder', ['title' => 'Triage queue']))->name('visits.queue');
    });

    // Quality manager only: insight + audit trail
    Route::middleware('role:quality_manager')->group(function () {
        Route::get('/dashboard', fn () => view('placeholder', ['title' => 'Dashboard']))->name('dashboard');
        Route::get('/audit', fn () => view('placeholder', ['title' => 'Audit log']))->name('audit.index');
    });
});

require __DIR__.'/auth.php';
