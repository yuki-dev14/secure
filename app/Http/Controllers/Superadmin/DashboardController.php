<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CashGrantDistribution;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_beneficiaries'  => Beneficiary::count(),
            'active_beneficiaries' => Beneficiary::active()->count(),
            'compliant'            => Beneficiary::compliant()->count(),
            'non_compliant'        => Beneficiary::active()->where('is_compliant', false)->count(),
            'total_staff'          => User::staff()->active()->count(),
            'recent_logs'          => AuditLog::latest('created_at')->limit(10)->get(),
            'distributions_today'  => CashGrantDistribution::whereDate('claimed_at', today())->count(),
            'barangay_coverage'    => Beneficiary::distinct('barangay')->count('barangay'),
        ];

        return Inertia::render('Superadmin/Dashboard', compact('stats'));
    }
}
