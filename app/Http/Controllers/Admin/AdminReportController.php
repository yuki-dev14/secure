<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CashGrantDistribution;
use App\Models\CashGrantCalculation;
use App\Models\DistributionEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function dashboardPdf(): Response
    {
        $generatedAt = now()->format('F d, Y \a\t g:i A');

        // ── Claims by month ───────────────────────────────────────────────────
        $claimsByMonth = CashGrantDistribution::where('status', 'claimed')
            ->selectRaw("TO_CHAR(claimed_at, 'YYYY-MM') as month, COUNT(*) as total, SUM(amount_released) as amount")
            ->where('claimed_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw("TO_CHAR(claimed_at, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(claimed_at, 'YYYY-MM')")
            ->get()
            ->map(fn($r) => [
                'label'  => Carbon::createFromFormat('Y-m', $r->month)->format('M Y'),
                'total'  => (int) $r->total,
                'amount' => (float) $r->amount,
            ]);

        // ── Claims per barangay ───────────────────────────────────────────────
        $claimsByBarangay = CashGrantDistribution::where('cash_grant_distributions.status', 'claimed')
            ->join('beneficiaries', 'cash_grant_distributions.beneficiary_id', '=', 'beneficiaries.id')
            ->selectRaw('beneficiaries.barangay, COUNT(*) as total_claims, SUM(cash_grant_distributions.amount_released) as total_amount')
            ->groupBy('beneficiaries.barangay')
            ->orderByDesc('total_claims')
            ->limit(20)
            ->get();

        $maxClaims = $claimsByBarangay->max('total_claims') ?: 1;

        // ── Beneficiaries per barangay ────────────────────────────────────────
        $beneficiariesByBarangay = Beneficiary::active()
            ->selectRaw('barangay, COUNT(*) as total')
            ->groupBy('barangay')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $maxBeneficiaries = $beneficiariesByBarangay->max('total') ?: 1;

        // ── Beneficiaries claiming per barangay ───────────────────────────────
        $claimingByBarangay = CashGrantDistribution::where('cash_grant_distributions.status', 'claimed')
            ->join('beneficiaries', 'cash_grant_distributions.beneficiary_id', '=', 'beneficiaries.id')
            ->selectRaw('beneficiaries.barangay, COUNT(DISTINCT cash_grant_distributions.beneficiary_id) as claimers')
            ->groupBy('beneficiaries.barangay')
            ->orderByDesc('claimers')
            ->limit(20)
            ->get();

        $maxClaimers = $claimingByBarangay->max('claimers') ?: 1;

        // ── Double claims ─────────────────────────────────────────────────────
        $doubleClaims = AuditLog::where('event', 'double_claim_attempt')
            ->selectRaw("new_values->>'distribution_event_id' as event_id, COUNT(*) as attempts")
            ->groupByRaw("new_values->>'distribution_event_id'")
            ->orderByDesc('attempts')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $event = DistributionEvent::find($row->event_id, ['id', 'title', 'period']);
                return [
                    'event_title' => $event?->title ?? 'Unknown Event',
                    'period'      => $event?->period,
                    'attempts'    => (int) $row->attempts,
                ];
            });

        $totalDoubleClaims = AuditLog::where('event', 'double_claim_attempt')->count();
        $maxAttempts = collect($doubleClaims)->max('attempts') ?: 1;

        // ── Summary KPIs ──────────────────────────────────────────────────────
        $summary = [
            'total_beneficiaries'  => Beneficiary::active()->count(),
            'total_claims'         => CashGrantDistribution::where('status', 'claimed')->count(),
            'total_amount'         => (float) CashGrantDistribution::where('status', 'claimed')->sum('amount_released'),
            'total_double_claims'  => $totalDoubleClaims,
            'unique_barangays'     => Beneficiary::active()->distinct('barangay')->count('barangay'),
        ];

        $pdf = Pdf::loadView('reports.admin-dashboard', compact(
            'generatedAt', 'summary',
            'claimsByMonth',
            'claimsByBarangay', 'maxClaims',
            'beneficiariesByBarangay', 'maxBeneficiaries',
            'claimingByBarangay', 'maxClaimers',
            'doubleClaims', 'maxAttempts', 'totalDoubleClaims',
        ))->setPaper('a4', 'portrait');

        $filename = 'admin-dashboard-report-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    // ── Per-event completion report PDF ───────────────────────────────────────
    public function eventReport(DistributionEvent $distributionEvent): Response
    {
        $event = $distributionEvent;
        $event->load([
            'office', 'creator',
            'distributions.beneficiary',
            'distributions.proxy',
            'distributions.releasedBy',
            'calculations.beneficiary',
        ]);

        $generatedAt = now()->format('F d, Y \a\t g:i A');

        // Claimed distributions sorted by barangay
        $claimed = $event->distributions->where('status', 'claimed')->sortBy(
            fn($d) => $d->beneficiary?->barangay
        );

        // Eligible / ineligible from calculations
        $eligible   = $event->calculations->where('is_eligible', true);
        $ineligible = $event->calculations->where('is_eligible', false);

        // Claims per barangay breakdown
        $barangayBreakdown = $claimed
            ->groupBy(fn($d) => $d->beneficiary?->barangay ?? 'Unknown')
            ->map(fn($group, $barangay) => [
                'barangay'     => $barangay,
                'count'        => $group->count(),
                'total_amount' => $group->sum('amount_released'),
            ])
            ->sortByDesc('count')
            ->values();

        $maxBarangayCount = $barangayBreakdown->max('count') ?: 1;

        // Summary KPIs
        $summary = [
            'total_beneficiaries' => Beneficiary::active()->count(),
            'computed'            => $event->calculations->count(),
            'eligible'            => $eligible->count(),
            'ineligible'          => $ineligible->count(),
            'claimed'             => $claimed->count(),
            'unclaimed'           => max(0, $eligible->count() - $claimed->count()),
            'total_released'      => $claimed->sum('amount_released'),
            'claim_rate'          => $eligible->count() > 0
                ? round(($claimed->count() / $eligible->count()) * 100)
                : 0,
        ];

        // Double-claim attempts for this event
        $doubleClaims = AuditLog::where('event', 'double_claim_attempt')
            ->whereRaw("(new_values->>'distribution_event_id')::int = ?", [$event->id])
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(function ($log) {
                $bid = data_get($log->new_values, 'beneficiary_id');
                $beneficiary = $bid
                    ? Beneficiary::find($bid, ['id', 'unique_id', 'first_name', 'last_name', 'barangay'])
                    : null;
                return [
                    'detected_at'      => $log->created_at?->format('M d, Y g:i A'),
                    'officer'          => $log->user?->name ?? 'System',
                    'beneficiary_name' => $beneficiary?->full_name ?? 'Unknown',
                    'beneficiary_uid'  => $beneficiary?->unique_id ?? '—',
                    'barangay'         => $beneficiary?->barangay ?? '—',
                ];
            });

        $pdf = Pdf::loadView('reports.event-completion', compact(
            'event', 'generatedAt', 'summary',
            'claimed', 'eligible', 'ineligible',
            'barangayBreakdown', 'maxBarangayCount',
            'doubleClaims',
        ))->setPaper('a4', 'portrait');

        $slug     = str($event->title)->slug();
        $filename = "event-report-{$slug}-" . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
