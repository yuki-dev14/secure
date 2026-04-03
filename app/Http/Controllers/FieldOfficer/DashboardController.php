<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Http\Controllers\Controller;
use App\Models\CashGrantDistribution;
use App\Models\DistributionEvent;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $ongoingEvent = DistributionEvent::ongoing()->with('office')->latest()->first();

        $stats = [
            'claimed_today'      => CashGrantDistribution::claimed()
                ->whereDate('claimed_at', today())
                ->where('released_by', auth()->id())
                ->count(),
            'total_released_today' => CashGrantDistribution::claimed()
                ->whereDate('claimed_at', today())
                ->where('released_by', auth()->id())
                ->sum('amount_released'),
            'pending_in_event'   => $ongoingEvent
                ? CashGrantDistribution::where('distribution_event_id', $ongoingEvent->id)
                    ->where('status', 'unclaimed')->count()
                : 0,
            'recent_releases'    => CashGrantDistribution::with(['beneficiary'])
                ->where('released_by', auth()->id())
                ->claimed()->latest('claimed_at')->limit(10)->get(),
            'ongoing_event'      => $ongoingEvent,
        ];

        return Inertia::render('FieldOfficer/Dashboard', compact('stats'));
    }
}
