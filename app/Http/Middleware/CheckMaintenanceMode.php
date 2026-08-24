<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = SystemSetting::get('maintenance_mode') === '1';

        if ($isMaintenance) {
            $user = Auth::user();

            // Allow superadmins to access everything during maintenance
            if ($user && $user->role === 'superadmin') {
                return $next($request);
            }

            // Allow staff login route so superadmin can sign in
            if ($request->routeIs('staff.login', 'login')) {
                return $next($request);
            }

            // Show Maintenance Mode page to all other users
            return Inertia::render('Errors/Maintenance', [
                'message' => 'SECURE 4Ps is currently undergoing scheduled system maintenance.',
            ])->toResponse($request)->setStatusCode(503);
        }

        return $next($request);
    }
}
