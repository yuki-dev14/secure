<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     * These are available to every Vue page component.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'                  => $user->id,
                    'name'                => $user->name,
                    'email'               => $user->email,
                    'role'                => $user->role,
                    'role_display'        => $user->role_display,
                    'must_change_password'=> $user->must_change_password,
                    'beneficiary'         => $user->isBeneficiary() ? [
                        'id'        => $user->beneficiary?->id,
                        'unique_id' => $user->beneficiary?->unique_id,
                        'full_name' => $user->beneficiary?->full_name,
                        'status'    => $user->beneficiary?->status,
                        'barangay'  => $user->beneficiary?->barangay,
                    ] : null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
                'error'   => fn () => $request->session()->get('error'),
                'info'    => fn () => $request->session()->get('info'),
            ],
            'app' => [
                'name'    => config('app.name', 'SECURE 4Ps'),
                'version' => '1.0.0',
                'city'    => 'Lipa City',
                'province'=> 'Batangas',
                'agency'  => 'DSWD Field Office IV-A CALABARZON',
            ],
        ]);
    }
}
