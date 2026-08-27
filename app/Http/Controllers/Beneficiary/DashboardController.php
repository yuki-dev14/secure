<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDocument;
use App\Models\CashGrantDistribution;
use App\Models\FdsAttendance;
use App\Models\NonComplianceRecord;
use App\Services\AuditLogService;
use App\Services\CashGrantCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $unreadCount = auth()->user()->unreadNotifications()->count();

        return Inertia::render('Beneficiary/Profile', [
            'beneficiary'  => $beneficiary,
            'unread_count' => $unreadCount,
        ]);
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
        $unreadCount   = auth()->user()->unreadNotifications()->count();
        $notifications = auth()->user()->notifications()->paginate(20);
        auth()->user()->unreadNotifications->markAsRead();

        return Inertia::render('Beneficiary/Notifications', [
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function markNotificationRead(Request $request, string $id): RedirectResponse
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
        return back();
    }

    /**
     * Compliance transparency page — shows non-compliance flags and FDS attendance.
     */
    public function compliance(): Response
    {
        $beneficiary = Beneficiary::where('user_id', auth()->id())
            ->with(['familyMembers'])
            ->firstOrFail();

        // Non-compliance records (all statuses, for transparency)
        $ncRecords = NonComplianceRecord::where('beneficiary_id', $beneficiary->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'category'       => $r->category,
                'reason'         => $r->reason,
                'details'        => $r->details,
                'grant_affected' => $r->grant_affected,
                'period'         => $r->period,
                'status'         => $r->status,
                'source'         => $r->source,
                'processed_at'   => $r->processed_at?->format('M d, Y'),
                'created_at'     => $r->created_at?->format('M d, Y'),
                'family_member'  => $r->familyMember ? [
                    'name' => $r->familyMember->full_name,
                    'relationship' => $r->familyMember->relationship,
                ] : null,
            ]);

        // FDS Attendance history
        $fdsAttendance = FdsAttendance::where('beneficiary_id', $beneficiary->id)
            ->orderByDesc('session_date')
            ->limit(20)
            ->get()
            ->map(fn($a) => [
                'id'            => $a->id,
                'session_title' => $a->session_title,
                'session_date'  => $a->session_date?->format('M d, Y'),
                'period'        => $a->period,
                'venue'         => $a->venue,
                'qr_verified'   => $a->qr_verified,
                'scanned_at'    => $a->scanned_at?->format('g:i A'),
            ]);

        // Compliance summary
        $summary = [
            'total_nc'          => $ncRecords->count(),
            'confirmed_nc'      => $ncRecords->where('status', 'confirmed')->count(),
            'pending_nc'        => $ncRecords->where('status', 'pending')->count(),
            'dismissed_nc'      => $ncRecords->where('status', 'dismissed')->count(),
            'fds_sessions'      => $fdsAttendance->count(),
            'is_compliant'      => $beneficiary->is_compliant,
        ];

        $unreadCount = auth()->user()->unreadNotifications()->count();

        return Inertia::render('Beneficiary/Compliance', [
            'beneficiary'   => $beneficiary,
            'ncRecords'     => $ncRecords,
            'fdsAttendance' => $fdsAttendance,
            'summary'       => $summary,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * Upload or update beneficiary profile picture.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $beneficiary = Beneficiary::where('user_id', auth()->id())->firstOrFail();

        // Delete old photo if exists in storage
        if ($beneficiary->photo_path && Storage::disk('public')->exists($beneficiary->photo_path)) {
            Storage::disk('public')->delete($beneficiary->photo_path);
        }

        $path = $request->file('photo')->store('beneficiaries/photos', 'public');
        $beneficiary->update(['photo_path' => $path]);

        AuditLogService::log('beneficiary_photo_updated', $beneficiary, [], [], "Beneficiary updated profile photo");

        return back()->with('success', 'Profile photo updated successfully!');
    }
}
