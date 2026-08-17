<?php

namespace App\Http\Controllers\AdminSwa;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\NonComplianceRecord;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Current bimonthly period
        $currentPeriod = $this->getCurrentPeriod();

        // ── Core KPI Stats ────────────────────────────────────────────────────
        $stats = [
            'total_beneficiaries'  => Beneficiary::active()->count(),
            'compliant'            => Beneficiary::active()->where('is_compliant', true)->count(),
            'non_compliant'        => Beneficiary::active()->where('is_compliant', false)->count(),

            // Non-compliance records for current period
            'nc_pending'           => NonComplianceRecord::pending()->forPeriod($currentPeriod['value'])->count(),
            'nc_confirmed'         => NonComplianceRecord::confirmed()->forPeriod($currentPeriod['value'])->count(),
            'nc_education'         => NonComplianceRecord::education()->forPeriod($currentPeriod['value'])->count(),
            'nc_health'            => NonComplianceRecord::health()->forPeriod($currentPeriod['value'])->count(),

            // All-time totals
            'nc_total_all_time'    => NonComplianceRecord::count(),
        ];

        // ── Non-Compliance by Barangay (for current period) ──────────────────
        $ncByBarangay = NonComplianceRecord::where('non_compliance_records.period', $currentPeriod['value'])
            ->join('beneficiaries', 'non_compliance_records.beneficiary_id', '=', 'beneficiaries.id')
            ->selectRaw('beneficiaries.barangay, COUNT(*) as total')
            ->groupBy('beneficiaries.barangay')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn($r) => [
                'barangay' => $r->barangay,
                'total'    => (int) $r->total,
            ]);

        // ── Recent Non-Compliance Records ────────────────────────────────────
        $recentRecords = NonComplianceRecord::with(['beneficiary', 'familyMember', 'processor'])
            ->latest()
            ->limit(10)
            ->get();

        // ── Available periods ────────────────────────────────────────────────
        $periods = $this->getAvailablePeriods();

        return Inertia::render('AdminSwa/Dashboard', [
            'stats'          => $stats,
            'ncByBarangay'   => $ncByBarangay,
            'recentRecords'  => $recentRecords,
            'currentPeriod'  => $currentPeriod,
            'periods'        => $periods,
        ]);
    }

    /**
     * Get the current bimonthly period based on today's date.
     * Periods: P1 (Jan-Feb), P2 (Mar-Apr), P3 (May-Jun), P4 (Jul-Aug), P5 (Sep-Oct), P6 (Nov-Dec)
     */
    private function getCurrentPeriod(): array
    {
        $periods = $this->getAvailablePeriods();
        $today = now()->toDateString();

        foreach ($periods as $period) {
            if ($today >= $period['start'] && $today <= $period['end']) {
                return $period;
            }
        }

        return $periods[0]; // Fallback to first period
    }

    /**
     * Generate bimonthly periods for 4Ps compliance tracking.
     * Periods are 2-month blocks as per RA 11310 cash grant release schedule.
     */
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
        $currentYear = now()->year;

        foreach ([$currentYear, $currentYear + 1] as $year) {
            foreach ($bimonthly as $p) {
                // Handle Feb end for leap year
                $end = $p['end'];
                if ($p['p'] === 1 && date('L', mktime(0, 0, 0, 1, 1, $year))) {
                    $end = '02-29';
                }

                $periods[] = [
                    'value' => "{$year}-P{$p['p']}",
                    'label' => "{$year} {$p['label']}",
                    'start' => "{$year}-{$p['start']}",
                    'end'   => "{$year}-{$end}",
                ];
            }
        }

        return $periods;
    }
}
