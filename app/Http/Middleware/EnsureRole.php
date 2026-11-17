<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side role check. Usage in routes: ->middleware('role:nurse,front_desk')
 *
 * Hiding a menu link is only UX; this middleware is the actual security boundary.
 * A user without an allowed role gets HTTP 403 even if they type the URL directly.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->value, $roles, true)) {
            abort(403, 'Your role does not have access to this page.');
        }

        return $next($request);
    }
}
