<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CashGrantDistribution;
use App\Models\DistributionEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // ── Core KPI stats ────────────────────────────────────────────────────
        $stats = [
            'beneficiaries'        => Beneficiary::active()->count(),
            'compliant'            => Beneficiary::compliant()->count(),
            'pending_compliance'   => Beneficiary::active()->where('is_compliant', false)->count(),
            'field_officers'       => User::byRole('field_officer')->active()->count(),
            'verifiers'            => User::byRole('compliance_verifier')->active()->count(),
            'upcoming_events'      => DistributionEvent::upcoming()->count(),
            'ongoing_events'       => DistributionEvent::ongoing()->count(),
            'claimed_this_month'   => CashGrantDistribution::claimed()
                ->whereMonth('claimed_at', now()->month)->count(),
            'total_released_month' => CashGrantDistribution::claimed()
                ->whereMonth('claimed_at', now()->month)
                ->sum('amount_released'),
            'latest_event'         => DistributionEvent::with('office')
                ->orderByDesc('distribution_date_start')
                ->first(),
            'recent_distributions' => CashGrantDistribution::with([
                'beneficiary', 'distributionEvent', 'releasedBy',
            ])->claimed()->latest('claimed_at')->limit(7)->get(),

            // ── Double-claim total ────────────────────────────────────────────
            'double_claim_count'   => AuditLog::where('event', 'double_claim_attempt')->count(),
        ];

        // ── Claims over time — last 12 months (for Line chart) ────────────────
        $claimsByMonth = CashGrantDistribution::claimed()
            ->selectRaw("TO_CHAR(claimed_at, 'YYYY-MM') as month, COUNT(*) as total, SUM(amount_released) as amount")
            ->where('claimed_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw("TO_CHAR(claimed_at, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(claimed_at, 'YYYY-MM')")
            ->get()
            ->map(fn($r) => [
                'month'  => $r->month,
                'label'  => \Carbon\Carbon::createFromFormat('Y-m', $r->month)->format('M Y'),
                'total'  => (int) $r->total,
                'amount' => (float) $r->amount,
            ]);

        // Fill in missing months with 0 so the line chart has no gaps
        $claimsByMonth = $this->fillMonthGaps($claimsByMonth->toArray(), 12);

        // ── Claims per barangay (Horizontal Bar) ─────────────────────────────
        $claimsByBarangay = CashGrantDistribution::where('cash_grant_distributions.status', 'claimed')
            ->join('beneficiaries', 'cash_grant_distributions.beneficiary_id', '=', 'beneficiaries.id')
            ->selectRaw('beneficiaries.barangay, COUNT(*) as total_claims, SUM(cash_grant_distributions.amount_released) as total_amount')
            ->groupBy('beneficiaries.barangay')
            ->orderByDesc('total_claims')
            ->limit(15)
            ->get()
            ->map(fn($r) => [
                'barangay'     => $r->barangay,
                'total_claims' => (int) $r->total_claims,
                'total_amount' => (float) $r->total_amount,
            ]);

        // ── Beneficiaries per barangay (Horizontal Bar) ───────────────────────
        $beneficiariesByBarangay = Beneficiary::active()
            ->selectRaw('barangay, COUNT(*) as total')
            ->groupBy('barangay')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn($r) => [
                'barangay' => $r->barangay,
                'total'    => (int) $r->total,
            ]);

        // ── Beneficiaries claiming per barangay — unique claimers ─────────────
        $claimingByBarangay = CashGrantDistribution::where('cash_grant_distributions.status', 'claimed')
            ->join('beneficiaries', 'cash_grant_distributions.beneficiary_id', '=', 'beneficiaries.id')
            ->selectRaw('beneficiaries.barangay, COUNT(DISTINCT cash_grant_distributions.beneficiary_id) as claimers')
            ->groupBy('beneficiaries.barangay')
            ->orderByDesc('claimers')
            ->limit(15)
            ->get()
            ->map(fn($r) => [
                'barangay' => $r->barangay,
                'claimers' => (int) $r->claimers,
            ]);

        // ── Double claims per event (mini bar in card) ────────────────────────
        $doubleClaimsByEvent = AuditLog::where('event', 'double_claim_attempt')
            ->selectRaw("new_values->>'distribution_event_id' as event_id, COUNT(*) as attempts")
            ->groupByRaw("new_values->>'distribution_event_id'")
            ->orderByDesc('attempts')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $event = DistributionEvent::find($row->event_id, ['id', 'title', 'period']);
                return [
                    'event_id'    => $row->event_id,
                    'event_title' => $event?->title ?? 'Unknown Event',
                    'period'      => $event?->period,
                    'attempts'    => (int) $row->attempts,
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats'                  => $stats,
            'claimsByMonth'          => $claimsByMonth,
            'claimsByBarangay'       => $claimsByBarangay,
            'beneficiariesByBarangay'=> $beneficiariesByBarangay,
            'claimingByBarangay'     => $claimingByBarangay,
            'doubleClaimsByEvent'    => $doubleClaimsByEvent,
        ]);
    }

    // ── Fill month gaps helper ────────────────────────────────────────────────
    private function fillMonthGaps(array $data, int $months): array
    {
        $map = collect($data)->keyBy('month');
        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $d = now()->subMonths($i)->startOfMonth();
            $key = $d->format('Y-m');
            $result[] = $map[$key] ?? [
                'month'  => $key,
                'label'  => $d->format('M Y'),
                'total'  => 0,
                'amount' => 0,
            ];
        }

        return $result;
    }
}
