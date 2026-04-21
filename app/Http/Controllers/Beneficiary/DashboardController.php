<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDocument;
use App\Models\CashGrantDistribution;
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
        return Inertia::render('Beneficiary/Profile', compact('beneficiary'));
    }

    public function documents(): Response
    {
        $beneficiary = $this->getBeneficiary();
        $docGroups   = $beneficiary->documents->groupBy('document_type');
        $unreadCount = auth()->user()->unreadNotifications()->count();
        return Inertia::render('Beneficiary/Documents', compact('beneficiary', 'docGroups', 'unreadCount'));
    }

    public function uploadDocument(Request $request): RedirectResponse
    {
        $beneficiary = Beneficiary::where('user_id', auth()->id())->firstOrFail();

        $request->validate([
            'document_type' => 'required|string|in:birth_certificate,valid_id,school_id,report_card,health_record,vaccination_booklet,medical_certificate,barangay_certificate,photo_1x1,certificate_of_indigency,prenatal_record,other',
            'document_name' => 'nullable|string|max:255',
            'description'   => 'nullable|string|max:500',
            'validity_date' => 'nullable|date',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file   = $request->file('file');
        $path   = $file->store("documents/{$beneficiary->unique_id}", 'public');
        $sizeKb = (int) ceil($file->getSize() / 1024);

        $doc = BeneficiaryDocument::create([
            'beneficiary_id' => $beneficiary->id,
            'document_type'  => $request->document_type,
            'document_name'  => $request->document_name ?? $file->getClientOriginalName(),
            'file_path'      => $path,
            'file_type'      => $file->getMimeType(),
            'file_size_kb'   => $sizeKb,
            'description'    => $request->description,
            'validity_date'  => $request->validity_date,
            'is_verified'    => false,
        ]);

        AuditLogService::log(
            'document_uploaded',
            $beneficiary,
            [],
            ['document_type' => $doc->document_type, 'file' => $doc->document_name],
            'Document uploaded by beneficiary via portal'
        );

        return back()->with('success', 'Document uploaded successfully. It will be reviewed by DSWD staff.');
    }

    public function deleteDocument(int $docId): RedirectResponse
    {
        $beneficiary = Beneficiary::where('user_id', auth()->id())->firstOrFail();
        $doc         = BeneficiaryDocument::where('beneficiary_id', $beneficiary->id)
            ->where('is_verified', false)      // Only allow deleting un-verified docs
            ->findOrFail($docId);

        if (Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        AuditLogService::log(
            'document_deleted',
            $beneficiary,
            ['document_name' => $doc->document_name],
            [],
            'Document removed by beneficiary via portal'
        );

        $doc->delete();

        return back()->with('success', 'Document removed.');
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
