<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CashGrantCalculation;
use App\Models\DistributionEvent;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Find latest distribution event (for grant computed count)
        $latestEvent = DistributionEvent::latest()->first();

        $stats = [
            'total_beneficiaries'  => Beneficiary::count(),
            'active_beneficiaries' => Beneficiary::active()->count(),
            'total_staff'          => User::staff()->active()->count(),
            'grants_computed'      => $latestEvent
                ? CashGrantCalculation::where('distribution_event_id', $latestEvent->id)
                    ->where('is_eligible', true)->count()
                : 0,
            'latest_period'        => $latestEvent?->period ?? '—',
            'recent_logs'          => AuditLog::latest('created_at')->limit(10)->get(),
            'barangay_coverage'    => Beneficiary::distinct('barangay')->count('barangay'),
        ];

        return Inertia::render('Superadmin/Dashboard', compact('stats'));
    }
}
