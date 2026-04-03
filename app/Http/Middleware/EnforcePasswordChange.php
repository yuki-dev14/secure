<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces password change on first login for beneficiaries.
 * Redirects to /beneficiary/change-password if must_change_password is true.
 */
class EnforcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user
            && $user->must_change_password
            && !$request->routeIs('beneficiary.password.change')
            && !$request->routeIs('beneficiary.password.update')
            && !$request->routeIs('logout')
        ) {
            return redirect()->route('beneficiary.password.change')
                ->with('warning', 'You must change your password before continuing.');
        }

        return $next($request);
    }
}
