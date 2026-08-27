<?php

namespace App\Http\Controllers\Admin4ps;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\FdsAttendance;
use App\Services\AuditLogService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FdsAttendanceController extends Controller
{
    /**
     * List all FDS attendance records with filters.
     */
    public function index(Request $request): Response
    {
        $query = FdsAttendance::with(['beneficiary', 'recorder']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('beneficiary', fn($q) =>
                $q->where('unique_id', 'ilike', "%{$s}%")
                  ->orWhere('first_name', 'ilike', "%{$s}%")
                  ->orWhere('last_name', 'ilike', "%{$s}%")
            );
        }

        if ($request->filled('period'))   $query->where('period', $request->period);
        if ($request->filled('barangay')) {
            $query->whereHas('beneficiary', fn($q) => $q->where('barangay', $request->barangay));
        }
        if ($request->filled('status')) {
            match ($request->status) {
                'complete'   => $query->where('is_complete', true),
                'incomplete' => $query->where('is_complete', false),
                default      => null,
            };
        }

        $records = $query->latest('session_date')->paginate(20)->withQueryString();

        $barangays = Beneficiary::active()->distinct()->pluck('barangay')->sort()->values();
        $periods   = $this->getAvailablePeriods();

        // Summary for current filter
        $summaryQuery = FdsAttendance::query();
        if ($request->filled('period')) $summaryQuery->where('period', $request->period);

        $summary = [
            'total'        => $summaryQuery->count(),
            'complete'     => (clone $summaryQuery)->where('is_complete', true)->count(),
            'incomplete'   => (clone $summaryQuery)->where('is_complete', false)->count(),
            'unique'       => (clone $summaryQuery)->distinct('beneficiary_id')->count('beneficiary_id'),
            'reported'     => (clone $summaryQuery)->where('is_reported', true)->count(),
            'unreported'   => (clone $summaryQuery)->where('is_reported', false)->count(),
        ];

        // Check if current period has unreported records
        $currentPeriod = $this->getCurrentPeriod();
        $hasUnreported = FdsAttendance::where('period', $currentPeriod['value'])
            ->where('is_complete', true)
            ->where('is_reported', false)
            ->exists();

        return Inertia::render('Admin4ps/FdsAttendance/Index', [
            'records'        => $records,
            'barangays'      => $barangays,
            'periods'        => $periods,
            'summary'        => $summary,
            'hasUnreported'  => $hasUnreported,
            'currentPeriod'  => $currentPeriod,
            'filters'        => $request->only(['search', 'period', 'barangay', 'status']),
        ]);
    }

    /**
     * FDS Scanner page — Barangay Assistant / Admin4Ps scans QR codes.
     */
    public function scanner(): Response
    {
        $periods       = $this->getAvailablePeriods();
        $currentPeriod = $this->getCurrentPeriod();

        // Today's stats
        $today = now()->toDateString();
        $todayStats = [
            'checked_in'  => FdsAttendance::where('session_date', $today)->whereNotNull('checked_in_at')->count(),
            'checked_out' => FdsAttendance::where('session_date', $today)->whereNotNull('checked_out_at')->count(),
            'complete'    => FdsAttendance::where('session_date', $today)->where('is_complete', true)->count(),
        ];

        return Inertia::render('Admin4ps/FdsAttendance/Scanner', [
            'periods'       => $periods,
            'currentPeriod' => $currentPeriod,
            'todayStats'    => $todayStats,
        ]);
    }

    /**
     * Process a QR scan — supports check_in and check_out modes.
     */
    public function scan(Request $request, QrCodeService $qrService): JsonResponse
    {
        $request->validate([
            'payload'        => 'required|string',
            'scan_type'      => 'required|in:check_in,check_out',
            'period'         => 'required|string|max:20',
            'session_title'  => 'nullable|string|max:200',
            'venue'          => 'nullable|string|max:200',
        ]);

        $payload  = trim($request->payload);
        $scanType = $request->scan_type;

        // ── Resolve beneficiary from QR or manual ID ─────────────────────────
        $beneficiary = $this->resolveBeneficiary($payload, $qrService);
        if (!$beneficiary) {
            return response()->json(['success' => false, 'message' => 'Beneficiary not found.'], 422);
        }

        if ($beneficiary->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => "Beneficiary is {$beneficiary->status}.",
            ], 422);
        }

        // ── Barangay assistant validation ─────────────────────────────────────
        $user = auth()->user();
        if ($user->role === 'barangay_assistant' && $user->assigned_barangay) {
            if (strtolower(trim($beneficiary->barangay)) !== strtolower(trim($user->assigned_barangay))) {
                return response()->json([
                    'success'     => false,
                    'message'     => "❌ Access Denied: Beneficiary belongs to Brgy. {$beneficiary->barangay}. Only beneficiaries from Brgy. {$user->assigned_barangay} can attend this FDS session.",
                    'beneficiary' => $this->formatBeneficiary($beneficiary),
                ], 422);
            }
        }

        $today      = now()->toDateString();
        $periodData = $this->resolvePeriodDates($request->period);

        if ($scanType === 'check_in') {
            return $this->handleCheckIn($beneficiary, $request, $today, $periodData);
        } else {
            return $this->handleCheckOut($beneficiary, $today);
        }
    }

    /**
     * Handle check-in scan (entry).
     */
    private function handleCheckIn(Beneficiary $beneficiary, Request $request, string $today, array $periodData): JsonResponse
    {
        // Check for existing check-in today
        $existing = FdsAttendance::where('beneficiary_id', $beneficiary->id)
            ->where('session_date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success'   => false,
                'message'   => 'Already checked in today.',
                'duplicate' => true,
                'beneficiary' => $this->formatBeneficiary($beneficiary),
                'checked_in_at' => $existing->checked_in_at?->format('g:i A'),
            ], 422);
        }

        $attendance = FdsAttendance::create([
            'beneficiary_id' => $beneficiary->id,
            'session_title'  => $request->session_title ?? "FDS Session — {$today}",
            'period'         => $request->period,
            'period_start'   => $periodData['start'],
            'period_end'     => $periodData['end'],
            'session_date'   => $today,
            'venue'          => $request->venue,
            'qr_verified'    => true,
            'scanned_at'     => now(),
            'scanned_device' => $request->header('User-Agent'),
            'checked_in_at'  => now(),
            'checked_in_device' => $request->header('User-Agent'),
            'is_complete'    => false,
            'recorded_by'    => auth()->id(),
        ]);

        AuditLogService::log('fds_check_in', $attendance, [], $attendance->toArray(),
            "FDS check-in recorded for {$beneficiary->unique_id}");

        try {
            if ($beneficiary->user) {
                $beneficiary->user->notify(new \App\Notifications\FdsAttendanceNotification($attendance, 'check_in'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("FDS check-in notification failed: " . $e->getMessage());
        }

        return response()->json([
            'success'     => true,
            'scan_type'   => 'check_in',
            'message'     => '✓ Check-In Recorded!',
            'beneficiary' => $this->formatBeneficiary($beneficiary),
            'attendance'  => [
                'id'            => $attendance->id,
                'session_date'  => $attendance->session_date->format('F d, Y'),
                'checked_in_at' => $attendance->checked_in_at->format('g:i A'),
                'is_complete'   => false,
            ],
        ]);
    }

    /**
     * Handle check-out scan (exit).
     */
    private function handleCheckOut(Beneficiary $beneficiary, string $today): JsonResponse
    {
        // Find today's check-in record
        $attendance = FdsAttendance::where('beneficiary_id', $beneficiary->id)
            ->where('session_date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'No check-in found for today. Beneficiary must check in first.',
            ], 422);
        }

        if ($attendance->is_complete) {
            return response()->json([
                'success'   => false,
                'message'   => 'Already checked out today.',
                'duplicate' => true,
                'beneficiary' => $this->formatBeneficiary($beneficiary),
                'checked_out_at' => $attendance->checked_out_at?->format('g:i A'),
            ], 422);
        }

        $attendance->update([
            'checked_out_at'     => now(),
            'checked_out_device' => request()->header('User-Agent'),
            'is_complete'        => true,
        ]);

        try {
            if ($beneficiary->user) {
                $beneficiary->user->notify(new \App\Notifications\FdsAttendanceNotification($attendance, 'check_out'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("FDS check-out notification failed: " . $e->getMessage());
        }

        AuditLogService::log('fds_check_out', $attendance, [], $attendance->toArray(),
            "FDS check-out recorded for {$beneficiary->unique_id}");

        return response()->json([
            'success'     => true,
            'scan_type'   => 'check_out',
            'message'     => '✓ Check-Out Recorded — Attendance Complete!',
            'beneficiary' => $this->formatBeneficiary($beneficiary),
            'attendance'  => [
                'id'             => $attendance->id,
                'session_date'   => $attendance->session_date->format('F d, Y'),
                'checked_in_at'  => $attendance->checked_in_at->format('g:i A'),
                'checked_out_at' => $attendance->checked_out_at->format('g:i A'),
                'is_complete'    => true,
            ],
        ]);
    }

    /**
     * Admin4Ps reports finalized attendance to Superadmin.
     */
    public function reportToSuperadmin(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|string|max:20',
        ]);

        $period = $request->period;

        // Mark all complete, unreported attendance for this period as reported
        $count = FdsAttendance::where('period', $period)
            ->where('is_complete', true)
            ->where('is_reported', false)
            ->update([
                'is_reported' => true,
                'reported_at' => now(),
                'reported_by' => auth()->id(),
            ]);

        if ($count === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No unreported complete attendance records found for this period.',
            ], 422);
        }

        AuditLogService::log('fds_reported_to_superadmin', null, [], [
            'period'         => $period,
            'records_count'  => $count,
            'reported_by'    => auth()->user()->name,
        ], "Admin4Ps reported {$count} FDS attendance records for {$period} to Superadmin.");

        return response()->json([
            'success' => true,
            'message' => "{$count} attendance records for {$period} have been reported to Superadmin.",
            'count'   => $count,
        ]);
    }

    /**
     * Get attendance count for today (for scanner UI counter).
     */
    public function todayCount(Request $request): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'checked_in'  => FdsAttendance::where('session_date', $today)->whereNotNull('checked_in_at')->count(),
            'checked_out' => FdsAttendance::where('session_date', $today)->whereNotNull('checked_out_at')->count(),
            'complete'    => FdsAttendance::where('session_date', $today)->where('is_complete', true)->count(),
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    private function resolveBeneficiary(string $payload, QrCodeService $qrService): ?Beneficiary
    {
        if (preg_match('/^4PS-LPA-\d+$/i', $payload)) {
            return Beneficiary::where('unique_id', strtoupper($payload))->first();
        }

        $result = $qrService->decode($payload);
        return $result['valid'] ? $result['beneficiary'] : null;
    }

    private function formatBeneficiary(Beneficiary $b): array
    {
        return [
            'id'        => $b->id,
            'full_name' => $b->full_name,
            'unique_id' => $b->unique_id,
            'barangay'  => $b->barangay,
            'photo_url' => $b->photo_path ? asset('storage/' . $b->photo_path) : null,
        ];
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

    private function resolvePeriodDates(string $value): array
    {
        foreach ($this->getAvailablePeriods() as $p) {
            if ($p['value'] === $value) return ['start' => $p['start'], 'end' => $p['end']];
        }
        return ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->endOfMonth()->toDateString()];
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
