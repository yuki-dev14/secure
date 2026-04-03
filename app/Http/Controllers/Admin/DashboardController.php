<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\CashGrantCalculation;
use App\Models\CashGrantDistribution;
use App\Models\DistributionEvent;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'beneficiaries'         => Beneficiary::active()->count(),
            'compliant'             => Beneficiary::compliant()->count(),
            'pending_compliance'    => Beneficiary::active()->where('is_compliant', false)->count(),
            'field_officers'        => User::byRole('field_officer')->active()->count(),
            'verifiers'             => User::byRole('compliance_verifier')->active()->count(),
            'upcoming_events'       => DistributionEvent::upcoming()->count(),
            'ongoing_events'        => DistributionEvent::ongoing()->count(),
            'claimed_this_month'    => CashGrantDistribution::claimed()
                ->whereMonth('claimed_at', now()->month)->count(),
            'total_released_month'  => CashGrantDistribution::claimed()
                ->whereMonth('claimed_at', now()->month)
                ->sum('amount_released'),
            'latest_event'          => DistributionEvent::with('office')
                ->orderByDesc('distribution_date_start')
                ->first(),
            'recent_distributions'  => CashGrantDistribution::with([
                'beneficiary', 'distributionEvent', 'releasedBy'
            ])->claimed()->latest('claimed_at')->limit(7)->get(),
        ];

        return Inertia::render('Admin/Dashboard', compact('stats'));
    }
}
