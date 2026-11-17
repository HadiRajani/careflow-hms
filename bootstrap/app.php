<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Our own middleware: ->middleware('role:nurse')
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Laravel's built-in 'auth' middleware: where to send someone who is NOT logged in.
        $middleware->redirectGuestsTo('/login');

        // Laravel's built-in 'guest' middleware: where to send someone who IS logged in
        // but opens /login again. Without this, Laravel sends everyone to /dashboard,
        // and a nurse would get a 403 there (dashboard is quality-manager only).
        $middleware->redirectUsersTo(fn ($request) => $request->user()->homePage());
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
