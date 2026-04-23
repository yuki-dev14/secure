<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDocument;
use App\Models\Office;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BeneficiaryController extends Controller
{
    // ─── Required document types for card activation ──────────────────────────

    private const REQUIRED_DOC_TYPES = [
        'valid_id'             => 'Valid ID',
        'birth_certificate'    => 'Birth Certificate',
        'barangay_certificate' => 'Proof of Residency (Barangay Certificate)',
    ];

    // ─── List ────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $query = Beneficiary::with(['office', 'card', 'creator'])
            ->withCount(['familyMembers', 'proxies'])
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('unique_id', 'ilike', "%{$s}%")
                  ->orWhere('first_name', 'ilike', "%{$s}%")
                  ->orWhere('last_name', 'ilike', "%{$s}%")
                  ->orWhere('listahanan_id', 'ilike', "%{$s}%");
            });
        }

        if ($request->filled('barangay')) {
            $query->where('barangay', $request->barangay);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $beneficiaries = $query->paginate(20)->withQueryString();
        $barangays     = Beneficiary::distinct()->orderBy('barangay')->pluck('barangay');

        return Inertia::render('Admin/Beneficiaries/Index', compact(
            'beneficiaries', 'barangays'
        ));
    }

    // ─── Show (with documents + doc checklist) ────────────────────────────────

    public function show(int $id): Response
    {
        $beneficiary = Beneficiary::with([
            'office', 'user', 'card',
            'familyMembers', 'proxies',
            'documents.uploadedBy',
            'complianceRecords.verifier',
            'grantCalculations.distributionEvent',
            'distributions.distributionEvent',
        ])->findOrFail($id);

        // Build a checklist of the 3 required activation documents
        $adminDocs = $beneficiary->documents
            ->where('source', 'admin')
            ->keyBy('document_type');

        $docChecklist = collect(self::REQUIRED_DOC_TYPES)->map(function ($label, $type) use ($adminDocs) {
            $doc = $adminDocs->get($type);
            return [
                'type'       => $type,
                'label'      => $label,
                'submitted'  => ! is_null($doc),
                'doc'        => $doc ? [
                    'id'          => $doc->id,
                    'file_path'   => $doc->file_path,
                    'is_verified' => $doc->is_verified,
                    'uploaded_by' => $doc->uploadedBy?->name,
                    'uploaded_at' => $doc->created_at?->format('M d, Y'),
                ] : null,
            ];
        })->values();

        $requiredDocTypes   = array_keys(self::REQUIRED_DOC_TYPES);
        $allRequiredPresent = $docChecklist->every(fn($d) => $d['submitted']);

        return Inertia::render('Admin/Beneficiaries/Show', compact(
            'beneficiary', 'docChecklist', 'requiredDocTypes', 'allRequiredPresent'
        ));
    }

    // ─── Update basic info ────────────────────────────────────────────────────

    public function update(Request $request, int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);

        $validated = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'middle_name'     => 'nullable|string|max:100',
            'contact_number'  => 'nullable|string|max:20',
            'barangay'        => 'required|string|max:100',
            'office_id'       => 'nullable|exists:offices,id',
            'status'          => 'required|in:active,inactive,suspended,graduated,delisted',
            'remarks'         => 'nullable|string',
        ]);

        $old = $beneficiary->toArray();
        $beneficiary->update($validated);

        AuditLogService::updated($beneficiary, $old, $beneficiary->fresh()->toArray());

        return back()->with('success', 'Beneficiary record updated successfully.');
    }

    // ─── Activate beneficiary (approve application + issue card) ─────────────

    public function activate(int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::with(['user', 'cards'])->findOrFail($id);

        if ($beneficiary->status === 'active') {
            return back()->with('error', 'Beneficiary is already active.');
        }

        // Guard: all 3 required documents must have been uploaded by admin
        $submittedTypes = $beneficiary->documents()
            ->where('source', 'admin')
            ->pluck('document_type')
            ->toArray();

        $missing = array_diff(array_keys(self::REQUIRED_DOC_TYPES), $submittedTypes);

        if (! empty($missing)) {
            $labels = array_map(fn($t) => self::REQUIRED_DOC_TYPES[$t], $missing);
            return back()->with('error',
                'Cannot activate. Missing required documents: ' . implode(', ', $labels) . '.'
            );
        }

        $old = $beneficiary->toArray();

        DB::transaction(function () use ($beneficiary, $old) {
            // Activate the beneficiary record
            $beneficiary->update([
                'status'          => 'active',
                'enrollment_date' => $beneficiary->enrollment_date ?? now()->toDateString(),
            ]);

            // Activate the linked portal user account
            if ($beneficiary->user) {
                $beneficiary->user->update(['is_active' => true]);
            }

            // Mark all admin-uploaded docs as verified
            $beneficiary->documents()
                ->where('source', 'admin')
                ->update([
                    'is_verified' => true,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

            // Issue the QR card
            $cardService = app(\App\Services\BeneficiaryCardService::class);
            $card = $cardService->issueCard($beneficiary, auth()->id());

            AuditLogService::log(
                'beneficiary_activated',
                $beneficiary,
                $old,
                ['status' => 'active', 'card_number' => $card->card_number],
                'Beneficiary application approved by admin and card issued'
            );
        });

        return back()->with('success', 'Beneficiary activated. QR card issued and ready to download.');
    }

    // ─── Upload submitted document (admin only — 3 required types) ───────────

    public function uploadDocument(Request $request, int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);

        $request->validate([
            'document_type' => 'required|in:' . implode(',', array_keys(self::REQUIRED_DOC_TYPES)),
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // If a doc of this type already exists (admin-uploaded), replace it
        $existing = $beneficiary->documents()
            ->where('source', 'admin')
            ->where('document_type', $request->document_type)
            ->first();

        if ($existing) {
            if (Storage::disk('public')->exists($existing->file_path)) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $existing->delete();
        }

        $file   = $request->file('file');
        $path   = $file->store("documents/{$beneficiary->unique_id}", 'public');
        $sizeKb = (int) ceil($file->getSize() / 1024);

        $typeLabelMap = self::REQUIRED_DOC_TYPES;

        $doc = BeneficiaryDocument::create([
            'beneficiary_id'  => $beneficiary->id,
            'document_type'   => $request->document_type,
            'document_name'   => $typeLabelMap[$request->document_type] . ' — ' . $beneficiary->full_name,
            'file_path'       => $path,
            'file_type'       => $file->getMimeType(),
            'file_size_kb'    => $sizeKb,
            'source'          => 'admin',
            'uploaded_by'     => auth()->id(),
            'is_verified'     => false,
        ]);

        AuditLogService::log(
            'document_uploaded',
            $beneficiary,
            [],
            ['document_type' => $doc->document_type, 'source' => 'admin'],
            'Physical document uploaded by admin: ' . $typeLabelMap[$request->document_type]
        );

        return back()->with('success', ucfirst(str_replace('_', ' ', $request->document_type)) . ' uploaded successfully.');
    }

    // ─── Verify / un-verify a document ───────────────────────────────────────

    public function verifyDocument(int $beneficiaryId, int $docId): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);
        $doc = BeneficiaryDocument::where('beneficiary_id', $beneficiary->id)
            ->where('source', 'admin')
            ->findOrFail($docId);

        $doc->update([
            'is_verified' => ! $doc->is_verified,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Document verification status updated.');
    }

    // ─── Delete document ──────────────────────────────────────────────────────

    public function deleteDocument(int $beneficiaryId, int $docId): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);
        $doc         = BeneficiaryDocument::where('beneficiary_id', $beneficiary->id)->findOrFail($docId);

        if (Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $doc->delete();

        return back()->with('success', 'Document removed.');
    }
}
