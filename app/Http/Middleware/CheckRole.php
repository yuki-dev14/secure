<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-based access control middleware.
 * Usage in routes: ->middleware('role:superadmin') or ->middleware('role:admin,superadmin')
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            \Illuminate\Support\Facades\Log::warning('Security Alert: Access control denied', [
                'user_id'     => $user?->id,
                'user_role'   => $user?->role,
                'required'    => $roles,
                'target_url'  => $request->fullUrl(),
                'method'      => $request->method(),
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
