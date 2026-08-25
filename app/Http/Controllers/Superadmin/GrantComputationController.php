<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\CashGrantCalculation;
use App\Models\DistributionEvent;
use App\Models\FdsAttendance;
use App\Models\NonComplianceRecord;
use App\Services\CashGrantCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GrantComputationController extends Controller
{
    public function __construct(private CashGrantCalculatorService $calculator) {}

    /**
     * Grant Computation dashboard — period selector, summary, and per-beneficiary table.
     */
    public function index(Request $request): Response
    {
        $period  = $request->get('period', $this->getCurrentPeriod()['value']);
        $periods = $this->getAvailablePeriods();

        // ── Find or preview distribution event for this period ──────────────
        $event = DistributionEvent::where('period', $period)->latest()->first();

        // ── Summary stats ───────────────────────────────────────────────────
        $totalActive = Beneficiary::where('status', 'active')->count();

        $calcsQuery = CashGrantCalculation::when($event, fn($q) =>
            $q->where('distribution_event_id', $event->id)
        );

        $summary = [
            'total_beneficiaries' => $totalActive,
            'computed'            => $event ? (clone $calcsQuery)->count() : 0,
            'eligible'            => $event ? (clone $calcsQuery)->where('is_eligible', true)->count() : 0,
            'with_deductions'     => $event ? (clone $calcsQuery)->where('is_eligible', true)
                                        ->whereNotNull('computation_notes')->count() : 0,
            'total_amount'        => $event ? (clone $calcsQuery)->where('is_eligible', true)
                                        ->sum('total_grant_amount') : 0,
            'health_total'        => $event ? (clone $calcsQuery)->sum('health_grant_amount') : 0,
            'edu_total'           => $event ? (clone $calcsQuery)->sum('education_grant_total') : 0,
            'rice_total'          => $event ? (clone $calcsQuery)->sum('rice_subsidy_amount') : 0,
        ];

        // ── Impact from reports received ────────────────────────────────────
        // Admin4Ps: FDS attendance — only count COMPLETE records (check-in + check-out)
        $fdsAttended = FdsAttendance::where('period', $period)
            ->where('is_complete', true)
            ->distinct('beneficiary_id')
            ->pluck('beneficiary_id')
            ->count();
        $fdsAbsent = max(0, $totalActive - $fdsAttended);

        // Check if Admin4Ps has reported this period
        $fdsReported = FdsAttendance::where('period', $period)
            ->where('is_complete', true)
            ->where('is_reported', true)
            ->count();

        // AdminSWA: Non-compliance records
        $ncByCategory = NonComplianceRecord::where('period', $period)
            ->where('status', 'confirmed')
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $reportImpact = [
            'fds_attended'     => $fdsAttended,
            'fds_absent'       => $fdsAbsent,
            'fds_reported'     => $fdsReported > 0,
            'nc_education'     => $ncByCategory['education'] ?? 0,
            'nc_health'        => $ncByCategory['health'] ?? 0,
            'nc_total'         => array_sum($ncByCategory),
        ];

        // ── Per-beneficiary grant list ──────────────────────────────────────
        $grantsQuery = CashGrantCalculation::with(['beneficiary', 'computedBy'])
            ->when($event, fn($q) => $q->where('distribution_event_id', $event->id));

        if ($request->filled('search')) {
            $s = $request->search;
            $grantsQuery->whereHas('beneficiary', fn($q) =>
                $q->where('unique_id', 'ilike', "%{$s}%")
                  ->orWhere('first_name', 'ilike', "%{$s}%")
                  ->orWhere('last_name', 'ilike', "%{$s}%")
            );
        }
        if ($request->filled('barangay')) {
            $grantsQuery->whereHas('beneficiary', fn($q) => $q->where('barangay', $request->barangay));
        }
        if ($request->filled('eligible')) {
            $grantsQuery->where('is_eligible', $request->eligible === 'true');
        }

        $grants    = $grantsQuery->latest('computed_at')->paginate(25)->withQueryString();
        $barangays = Beneficiary::where('status', 'active')->distinct()->pluck('barangay')->sort()->values();

        return Inertia::render('Superadmin/GrantComputation/Index', [
            'grants'        => $grants,
            'summary'       => $summary,
            'reportImpact'  => $reportImpact,
            'barangays'     => $barangays,
            'periods'       => $periods,
            'currentPeriod' => $period,
            'event'         => $event ? [
                'id'     => $event->id,
                'title'  => $event->title,
                'period' => $event->period,
                'status' => $event->status,
            ] : null,
            'filters' => $request->only(['search', 'period', 'barangay', 'eligible']),
        ]);
    }

    /**
     * "Update Grants" — batch compute grants for all active beneficiaries
     * based on the selected period. Auto-creates a distribution event if needed.
     */
    public function compute(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|string|max:20',
        ]);

        $period = $request->period;
        $periodData = $this->resolvePeriodDates($period);

        // Auto-create distribution event for this period if it doesn't exist
        $event = DistributionEvent::firstOrCreate(
            ['period' => $period],
            [
                'title'                   => "Grant Distribution {$period}",
                'notes'                   => "Auto-created for grant computation — period {$period}",
                'period_start'            => $periodData['start'],
                'period_end'              => $periodData['end'],
                'distribution_date_start' => $periodData['start'],
                'distribution_date_end'   => $periodData['end'],
                'venue'                   => 'Lipa City SWDO Office',
                'venue_address'           => 'Marawoy, Lipa City, Batangas',
                'status'                  => 'upcoming',
                'months_covered'          => 2,
                'created_by'              => auth()->id() ?? 1,
            ]
        );

        // Run batch computation using NonComplianceRecord zero-out logic
        $results = $this->calculator->batchCalculateBimonthly($event);

        return response()->json([
            'success' => true,
            'message' => "Grants updated for {$results['computed']} beneficiaries. "
                       . "Eligible: {$results['eligible']}, With Deductions: {$results['partial']}, "
                       . "Zeroed Out: {$results['ineligible']}.",
            'results' => $results,
        ]);
    }

    /**
     * Export computed grants for the selected period as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $period = $request->get('period', $this->getCurrentPeriod()['value']);
        $event  = DistributionEvent::where('period', $period)->latest()->first();

        $rows = CashGrantCalculation::with(['beneficiary'])
            ->when($event, fn($q) => $q->where('distribution_event_id', $event->id))
            ->latest('computed_at')
            ->get();

        $filename = 'SECURE_Grants_' . str_replace('-', '_', $period) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $period) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'Unique ID', 'Last Name', 'First Name', 'Barangay',
                'Period', 'Health Grant', 'Education Grant', 'Rice Subsidy',
                'Total Grant', 'Elem Children', 'JHS Children', 'SHS Children',
                'NC Notes', 'Computed At',
            ]);

            foreach ($rows as $g) {
                fputcsv($handle, [
                    $g->beneficiary?->unique_id ?? '',
                    $g->beneficiary?->last_name ?? '',
                    $g->beneficiary?->first_name ?? '',
                    $g->beneficiary?->barangay ?? '',
                    $period,
                    number_format($g->health_grant_amount, 2),
                    number_format($g->education_grant_total, 2),
                    number_format($g->rice_subsidy_amount, 2),
                    number_format($g->total_grant_amount, 2),
                    $g->elementary_children_count ?? 0,
                    $g->junior_high_children_count ?? 0,
                    $g->senior_high_children_count ?? 0,
                    $g->computation_notes ?? '',
                    $g->computed_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getCurrentPeriod(): array
    {
        $periods = $this->getAvailablePeriods();
        $today   = now()->toDateString();
        foreach ($periods as $p) {
            if ($today >= $p['start'] && $today <= $p['end']) return $p;
        }
        return $periods[0];
    }

    private function resolvePeriodDates(string $value): array
    {
        foreach ($this->getAvailablePeriods() as $p) {
            if ($p['value'] === $value) return ['start' => $p['start'], 'end' => $p['end']];
        }
        return [
            'start' => now()->startOfMonth()->toDateString(),
            'end'   => now()->endOfMonth()->toDateString(),
        ];
    }

    private function getAvailablePeriods(): array
    {
        $bimonthly = [
            ['p' => 1, 'label' => 'P1 (January–February)',   'start' => '01-01', 'end' => '02-28'],
            ['p' => 2, 'label' => 'P2 (March–April)',        'start' => '03-01', 'end' => '04-30'],
            ['p' => 3, 'label' => 'P3 (May–June)',           'start' => '05-01', 'end' => '06-30'],
            ['p' => 4, 'label' => 'P4 (July–August)',        'start' => '07-01', 'end' => '08-31'],
            ['p' => 5, 'label' => 'P5 (September–October)',  'start' => '09-01', 'end' => '10-31'],
            ['p' => 6, 'label' => 'P6 (November–December)',  'start' => '11-01', 'end' => '12-31'],
        ];
        $periods = [];
        $year    = now()->year;
        foreach ([$year - 1, $year, $year + 1] as $y) {
            foreach ($bimonthly as $p) {
                $end = $p['end'];
                if ($p['p'] === 1 && date('L', mktime(0, 0, 0, 1, 1, $y))) $end = '02-29';
                $periods[] = [
                    'value' => "{$y}-P{$p['p']}",
                    'label' => "{$y} {$p['label']}",
                    'start' => "{$y}-{$p['start']}",
                    'end'   => "{$y}-{$end}",
                ];
            }
        }
        return $periods;
    }
}
