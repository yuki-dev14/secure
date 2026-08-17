<?php

namespace App\Http\Controllers\BarangayAssistant;

use App\Http\Controllers\Controller;
use App\Models\FdsAttendance;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user     = auth()->user();
        $barangay = $user->assigned_barangay;
        $today    = now()->toDateString();

        // Today's stats (filtered by barangay if assigned)
        $todayQuery = FdsAttendance::where('session_date', $today);
        if ($barangay) {
            $todayQuery->whereHas('beneficiary', fn($q) => $q->where('barangay', $barangay));
        }

        $todayStats = [
            'checked_in'  => (clone $todayQuery)->whereNotNull('checked_in_at')->count(),
            'checked_out' => (clone $todayQuery)->whereNotNull('checked_out_at')->count(),
            'complete'    => (clone $todayQuery)->where('is_complete', true)->count(),
        ];

        // Recent scans by this user
        $recentScans = FdsAttendance::with('beneficiary')
            ->where('recorded_by', $user->id)
            ->latest('checked_in_at')
            ->limit(15)
            ->get()
            ->map(fn($r) => [
                'id'            => $r->id,
                'beneficiary'   => $r->beneficiary?->full_name ?? '—',
                'unique_id'     => $r->beneficiary?->unique_id ?? '—',
                'barangay'      => $r->beneficiary?->barangay ?? '—',
                'checked_in_at' => $r->checked_in_at?->format('g:i A'),
                'checked_out_at'=> $r->checked_out_at?->format('g:i A'),
                'is_complete'   => $r->is_complete,
                'session_date'  => $r->session_date?->format('M d, Y'),
            ]);

        return Inertia::render('BarangayAssistant/Dashboard', [
            'todayStats'   => $todayStats,
            'recentScans'  => $recentScans,
            'barangay'     => $barangay ?? 'All Barangays',
        ]);
    }
}
