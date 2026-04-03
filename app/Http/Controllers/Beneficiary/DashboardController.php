<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\CashGrantDistribution;
use App\Services\CashGrantCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private CashGrantCalculatorService $calculator) {}

    private function getBeneficiary(): Beneficiary
    {
        return Beneficiary::where('user_id', auth()->id())
            ->with([
                'office', 'card',
                'familyMembers' => fn($q) => $q->orderBy('relationship'),
                'proxies'       => fn($q) => $q->where('is_active', true),
                'documents',
                'complianceRecords' => fn($q) => $q->latest()->limit(3),
                'grantCalculations.distributionEvent',
            ])->firstOrFail();
    }

    public function index(): Response
    {
        $beneficiary  = $this->getBeneficiary();
        $latestGrant  = $beneficiary->grantCalculations->first();
        $breakdown    = $latestGrant ? $this->calculator->getBreakdownSummary($latestGrant) : null;

        $claimHistory = CashGrantDistribution::where('beneficiary_id', $beneficiary->id)
            ->with('distributionEvent')
            ->claimed()->latest('claimed_at')->limit(5)->get();

        $notifications = auth()->user()->notifications()->latest()->limit(10)->get();
        $unreadCount   = auth()->user()->unreadNotifications()->count();

        return Inertia::render('Beneficiary/Dashboard', [
            'beneficiary'   => $beneficiary,
            'breakdown'     => $breakdown,
            'claim_history' => $claimHistory,
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function profile(): Response
    {
        $beneficiary = $this->getBeneficiary();
        return Inertia::render('Beneficiary/Profile', compact('beneficiary'));
    }

    public function documents(): Response
    {
        $beneficiary = $this->getBeneficiary();
        $docGroups   = $beneficiary->documents->groupBy('document_type');
        return Inertia::render('Beneficiary/Documents', compact('beneficiary', 'docGroups'));
    }

    public function grants(): Response
    {
        $beneficiary  = Beneficiary::where('user_id', auth()->id())
            ->with([
                'familyMembers',
                'grantCalculations.distributionEvent',
                'distributions.distributionEvent',
            ])->firstOrFail();

        $calculations = $beneficiary->grantCalculations;
        $breakdowns   = $calculations->map(fn($c) => $this->calculator->getBreakdownSummary($c));

        return Inertia::render('Beneficiary/Grants', compact('beneficiary', 'calculations', 'breakdowns'));
    }

    public function family(): Response
    {
        $beneficiary = $this->getBeneficiary();
        return Inertia::render('Beneficiary/Family', compact('beneficiary'));
    }

    public function notifications(): Response
    {
        $notifications = auth()->user()->notifications()->paginate(20);
        auth()->user()->unreadNotifications->markAsRead();
        return Inertia::render('Beneficiary/Notifications', compact('notifications'));
    }

    public function markNotificationRead(Request $request, string $id): RedirectResponse
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
        return back();
    }
}
