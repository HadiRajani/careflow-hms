<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
 * Deliberately minimal: login + logout only.
 * Breeze also ships public self-registration, password reset by email, email verification
 * and self-service account deletion. All removed: in a hospital, staff accounts are
 * provisioned by an administrator, and deleting an account would orphan the audit trail.
 */

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
