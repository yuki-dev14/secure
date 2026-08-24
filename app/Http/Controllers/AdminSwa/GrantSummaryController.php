<?php

namespace App\Http\Controllers\AdminSwa;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\CashGrantCalculation;
use App\Models\DistributionEvent;
use App\Models\NonComplianceRecord;
use App\Services\CashGrantCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GrantSummaryController extends Controller
{
    public function __construct(private CashGrantCalculatorService $calculator) {}

    /**
     * Grant summary overview — shows computed grants with NC adjustments.
     */
    public function index(Request $request): Response
    {
        $period  = $request->get('period', $this->getCurrentPeriod()['value']);
        $periods = $this->getAvailablePeriods();

        // ── Summary stats ───────────────────────────────────────────────────
        $totalActive = Beneficiary::active()->count();

        $calcsForPeriod = CashGrantCalculation::whereHas('distributionEvent', fn($q) =>
            $q->where('period', $period)
        );

        $summary = [
            'total_beneficiaries' => $totalActive,
            'computed'            => (clone $calcsForPeriod)->count(),
            'eligible'            => (clone $calcsForPeriod)->where('is_eligible', true)->count(),
            'ineligible'          => (clone $calcsForPeriod)->where('is_eligible', false)->count(),
            'total_amount'        => (clone $calcsForPeriod)->where('is_eligible', true)->sum('total_grant_amount'),
            'health_total'        => (clone $calcsForPeriod)->sum('health_grant_amount'),
            'edu_total'           => (clone $calcsForPeriod)->sum('education_grant_total'),
            'rice_total'          => (clone $calcsForPeriod)->sum('rice_subsidy_amount'),
            'nc_adjusted'         => (clone $calcsForPeriod)->where('is_eligible', true)
                ->whereNotNull('computation_notes')->count(),
        ];

        // ── NC impact stats ─────────────────────────────────────────────────
        $ncConfirmed = NonComplianceRecord::where('period', $period)
            ->where('status', 'confirmed')
            ->count();

        $ncByGrant = NonComplianceRecord::where('period', $period)
            ->where('status', 'confirmed')
            ->selectRaw('grant_affected, COUNT(*) as total')
            ->groupBy('grant_affected')
            ->pluck('total', 'grant_affected')
            ->toArray();

        $ncImpact = [
            'total_confirmed'      => $ncConfirmed,
            'health_nc'            => $ncByGrant['health_grant'] ?? 0,
            'education_elem_nc'    => $ncByGrant['education_elementary'] ?? 0,
            'education_jhs_nc'     => $ncByGrant['education_junior_high'] ?? 0,
            'education_shs_nc'     => $ncByGrant['education_senior_high'] ?? 0,
            'rice_nc'              => $ncByGrant['rice_subsidy'] ?? 0,
        ];

        // ── Per-beneficiary grant list ──────────────────────────────────────
        $query = CashGrantCalculation::with(['beneficiary', 'computedBy'])
            ->whereHas('distributionEvent', fn($q) => $q->where('period', $period));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('beneficiary', fn($q) =>
                $q->where('unique_id', 'ilike', "%{$s}%")
                  ->orWhere('first_name', 'ilike', "%{$s}%")
                  ->orWhere('last_name', 'ilike', "%{$s}%")
            );
        }
        if ($request->filled('eligible')) {
            $query->where('is_eligible', $request->eligible === 'true');
        }
        if ($request->filled('barangay')) {
            $query->whereHas('beneficiary', fn($q) => $q->where('barangay', $request->barangay));
        }

        $grants = $query->latest('computed_at')->paginate(20)->withQueryString();

        $barangays = Beneficiary::active()->distinct()->pluck('barangay')->sort()->values();

        $events = DistributionEvent::orderBy('distribution_date_start', 'desc')->get();

        return Inertia::render('AdminSwa/GrantSummary/Index', [
            'grants'        => $grants,
            'summary'       => $summary,
            'ncImpact'      => $ncImpact,
            'barangays'     => $barangays,
            'periods'       => $periods,
            'events'        => $events,
            'filters'       => $request->only(['search', 'period', 'eligible', 'barangay']),
            'currentPeriod' => $period,
        ]);
    }

    /**
     * Trigger batch bimonthly grant computation for a distribution event or period.
     */
    public function compute(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => 'nullable|exists:distribution_events,id',
            'period'   => 'nullable|string',
        ]);

        if ($request->filled('event_id')) {
            $event = DistributionEvent::findOrFail($request->event_id);
        } else {
            $period    = $request->get('period', $this->getCurrentPeriod()['value']);
            $periodObj = $this->getPeriodDetails($period);
            $event     = DistributionEvent::firstOrCreate(
                ['period' => $period],
                [
                    'title'                   => "4Ps Cash Grant Disbursement — {$period}",
                    'period_start'            => $periodObj['start'],
                    'period_end'              => $periodObj['end'],
                    'months_covered'          => 2,
                    'distribution_date_start' => now()->toDateString(),
                    'distribution_date_end'   => now()->addDays(2)->toDateString(),
                    'venue'                   => 'City Gymnasium / Barangay Halls',
                    'status'                  => 'ongoing',
                    'created_by'              => auth()->id() ?? 1,
                ]
            );
        }

        $results = $this->calculator->batchCalculateBimonthly($event);

        return response()->json([
            'success' => true,
            'message' => "Grants computed successfully for {$results['computed']} households.",
            'results' => $results,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getPeriodDetails(string $periodValue): array
    {
        foreach ($this->getAvailablePeriods() as $p) {
            if ($p['value'] === $periodValue) return $p;
        }
        return ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->endOfMonth()->toDateString()];
    }

    private function getCurrentPeriod(): array
    {
        $periods = $this->getAvailablePeriods();
        $today   = now()->toDateString();
        foreach ($periods as $p) {
            if ($today >= $p['start'] && $today <= $p['end']) return $p;
        }
        return $periods[0];
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
        foreach ([$year, $year + 1] as $y) {
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
