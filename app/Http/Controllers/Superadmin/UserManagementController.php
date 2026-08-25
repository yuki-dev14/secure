<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $tab    = $request->get('tab', 'all');
        $search = $request->get('search');

        // ── Staff users (admin, verifier, officer, superadmin) ────────────────
        $staffQuery = User::with('office')
            ->whereNotIn('role', ['beneficiary'])
            ->latest();

        if ($search) {
            $staffQuery->where(function ($q) use ($search) {
                $q->where('name',        'ilike', "%{$search}%")
                  ->orWhere('email',     'ilike', "%{$search}%")
                  ->orWhere('employee_id', 'ilike', "%{$search}%");
            });
        }

        if ($tab !== 'all' && $tab !== 'beneficiary') {
            $staffQuery->where('role', $tab);
        }

        // ── Beneficiary users ─────────────────────────────────────────────────
        $beneficiaryQuery = Beneficiary::with('user')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('first_name',  'ilike', "%{$search}%")
                          ->orWhere('last_name',  'ilike', "%{$search}%")
                          ->orWhere('unique_id',  'ilike', "%{$search}%")
                          ->orWhere('barangay',   'ilike', "%{$search}%");
                });
            });

        // ── Counts per role for tab badges ────────────────────────────────────
        $counts = [
            'all'                => User::whereNotIn('role', ['beneficiary'])->count()
                                   + Beneficiary::count(),
            'superadmin'         => User::where('role', 'superadmin')->count(),
            'admin'              => User::where('role', 'admin')->count(),
            'admin_4ps'          => User::where('role', 'admin_4ps')->count(),
            'admin_swa'          => User::where('role', 'admin_swa')->count(),
            'barangay_assistant' => User::where('role', 'barangay_assistant')->count(),
            'beneficiary'        => Beneficiary::count(),
        ];

        // Paginate appropriately
        if ($tab === 'beneficiary') {
            $beneficiaries = $beneficiaryQuery->paginate(25)->withQueryString();
            $staff         = collect();
        } else {
            $staff         = $staffQuery->paginate(25)->withQueryString();
            $beneficiaries = collect();
        }

        return Inertia::render('Superadmin/Users/Index', [
            'staff'         => $staff,
            'beneficiaries' => $beneficiaries,
            'counts'        => $counts,
            'filters'       => ['tab' => $tab, 'search' => $search],
        ]);
    }

    public function toggleActive(User $user): RedirectResponse
    {
        // Superadmin cannot deactivate their own account
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        AuditLogService::log("user_{$status}", $user, [], [], "Superadmin {$status} account for {$user->name}");

        return back()->with('success', "Account {$status} for {$user->name}.");
    }

    public function toggleBeneficiaryActive(Beneficiary $beneficiary): RedirectResponse
    {
        $newStatus = $beneficiary->status === 'active' ? 'inactive' : 'active';
        $beneficiary->update(['status' => $newStatus]);

        if ($beneficiary->user) {
            $beneficiary->user->update(['is_active' => $newStatus === 'active']);
        }

        $action = $newStatus === 'active' ? 'activated' : 'deactivated';
        AuditLogService::log("beneficiary_{$action}", $beneficiary, [], [],
            "Superadmin {$action} beneficiary {$beneficiary->full_name}");

        return back()->with('success', "Beneficiary account {$action}.");
    }
}
