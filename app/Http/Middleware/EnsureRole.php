<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Checks the logged-in user's role BEFORE the page runs.
 *
 * In routes/web.php we write:   ->middleware('role:front_desk,nurse')
 * Laravel then calls:            handle($request, $next, 'front_desk', 'nurse')
 * so $allowedRoles becomes:      ['front_desk', 'nurse']
 *
 * This is the real security check. Hiding menu links is only for convenience.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$allowedRoles)
    {
        $userRole = $request->user()->role;   // e.g. 'nurse'

        if (! in_array($userRole, $allowedRoles)) {
            abort(403, 'Your role does not have access to this page.');
        }

        return $next($request);   // role is allowed -> continue to the page
    }
}
