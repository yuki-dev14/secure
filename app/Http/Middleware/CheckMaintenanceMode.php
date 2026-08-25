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
        $settingValue  = SystemSetting::get('maintenance_mode');
        $isMaintenance = (bool) $settingValue;

        if ($isMaintenance) {
            $user = Auth::user();

            // Allow superadmins to access everything during maintenance
            if ($user && $user->role === 'superadmin') {
                return $next($request);
            }

            // Allow login & logout routes so superadmin can sign in
            if ($request->routeIs('staff.login', 'login', 'logout')) {
                return $next($request);
            }

            // Show Maintenance Mode page to all non-superadmin users
            return Inertia::render('Errors/Maintenance', [
                'message' => 'SECURE 4Ps is currently undergoing scheduled system maintenance.',
            ])->toResponse($request)->setStatusCode(503);
        }

        return $next($request);
    }
}
