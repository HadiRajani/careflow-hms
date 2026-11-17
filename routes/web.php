<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\TriageController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

/*
 * Who can open what. Checked on the server by the 'role' middleware
 * (app/Http/Middleware/EnsureRole.php). Wrong role = 403 error.
 *
 *   front_desk       -> patients + open visits
 *   nurse            -> patients + open visits + triage
 *   quality_manager  -> dashboard + audit log
 */

// Home: logged-in users go to their role's page, everyone else to login.
Route::get('/', function () {
    if (auth()->check()) {
        return redirect(auth()->user()->homePage());
    }
    return redirect('/login');
});

// English / Urdu switch. Works on the login page too, so no 'auth' here.
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Everything below needs a logged-in user ('auth').
Route::middleware('auth')->group(function () {

    // Front desk AND nurse
    Route::middleware('role:front_desk,nurse')->group(function () {
        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
        Route::post('/patients/{patient}/visits', [VisitController::class, 'store'])->name('visits.store');
    });

    // Nurse only
    Route::middleware('role:nurse')->group(function () {
        Route::get('/triage', [TriageController::class, 'queue'])->name('visits.queue');
        Route::get('/triage/{visit}', [TriageController::class, 'create'])->name('triage.create');
        Route::post('/triage/{visit}', [TriageController::class, 'store'])->name('triage.store');
    });

    // Quality manager only
    Route::middleware('role:quality_manager')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});

require __DIR__.'/auth.php';
