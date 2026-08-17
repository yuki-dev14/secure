<?php

namespace App\Http\Controllers\Admin4ps;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\FdsAttendance;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $currentPeriod = $this->getCurrentPeriod();

        $totalActive = Beneficiary::active()->count();

        // FDS Attendance stats for current period
        $periodQuery = FdsAttendance::forPeriod($currentPeriod['value']);

        $stats = [
            'total_beneficiaries'   => $totalActive,

            // Attendance
            'fds_total'             => (clone $periodQuery)->count(),
            'fds_complete'          => (clone $periodQuery)->complete()->count(),
            'fds_incomplete'        => (clone $periodQuery)->incomplete()->count(),
            'fds_unique_attendees'  => (clone $periodQuery)->complete()
                ->distinct('beneficiary_id')->count('beneficiary_id'),

            // Reporting status
            'fds_reported'          => (clone $periodQuery)->complete()->reported()->count(),
            'fds_unreported'        => (clone $periodQuery)->complete()->unreported()->count(),

            // Barangay assistants
            'barangay_assistants'   => User::byRole('barangay_assistant')->active()->count(),
        ];

        // Attendance rate (complete attendance vs total active beneficiaries)
        $stats['attendance_rate'] = $totalActive > 0
            ? round(($stats['fds_unique_attendees'] / $totalActive) * 100, 1)
            : 0;

        // ── FDS Attendance by Barangay ────────────────────────────────────────
        $fdsByBarangay = FdsAttendance::where('fds_attendance.period', $currentPeriod['value'])
            ->join('beneficiaries', 'fds_attendance.beneficiary_id', '=', 'beneficiaries.id')
            ->selectRaw('
                beneficiaries.barangay,
                COUNT(*) as total,
                SUM(CASE WHEN fds_attendance.is_complete = true THEN 1 ELSE 0 END) as complete,
                SUM(CASE WHEN fds_attendance.is_complete = false THEN 1 ELSE 0 END) as incomplete,
                COUNT(DISTINCT CASE WHEN fds_attendance.is_complete = true THEN fds_attendance.beneficiary_id END) as unique_complete
            ')
            ->groupBy('beneficiaries.barangay')
            ->orderByDesc('unique_complete')
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'barangay'         => $r->barangay,
                'total'            => (int) $r->total,
                'complete'         => (int) $r->complete,
                'incomplete'       => (int) $r->incomplete,
                'unique_complete'  => (int) $r->unique_complete,
            ]);

        // ── Recent FDS Attendance ────────────────────────────────────────────
        $recentAttendance = FdsAttendance::with(['beneficiary', 'recorder'])
            ->latest('checked_in_at')
            ->limit(10)
            ->get();

        $periods = $this->getAvailablePeriods();

        return Inertia::render('Admin4ps/Dashboard', [
            'stats'              => $stats,
            'fdsByBarangay'      => $fdsByBarangay,
            'recentAttendance'   => $recentAttendance,
            'currentPeriod'      => $currentPeriod,
            'periods'            => $periods,
        ]);
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
