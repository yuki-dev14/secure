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

    // ─── Show (with documents) ────────────────────────────────────────────────

    public function show(int $id): Response
    {
        $beneficiary = Beneficiary::with([
            'office', 'user', 'card',
            'familyMembers', 'proxies',
            'documents',
            'complianceRecords.verifier',
            'grantCalculations.distributionEvent',
            'distributions.distributionEvent',
        ])->findOrFail($id);

        return Inertia::render('Admin/Beneficiaries/Show', compact('beneficiary'));
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

    // ─── Upload submitted document ────────────────────────────────────────────

    public function uploadDocument(Request $request, int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);

        $request->validate([
            'document_type' => 'required|string|max:60',
            'document_name' => 'nullable|string|max:255',
            'description'   => 'nullable|string|max:500',
            'validity_date' => 'nullable|date',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file     = $request->file('file');
        $ext      = $file->getClientOriginalExtension();
        $path     = $file->store("documents/{$beneficiary->unique_id}", 'public');
        $sizeKb   = (int) ceil($file->getSize() / 1024);

        $doc = BeneficiaryDocument::create([
            'beneficiary_id'  => $beneficiary->id,
            'document_type'   => $request->document_type,
            'document_name'   => $request->document_name ?? $file->getClientOriginalName(),
            'file_path'       => $path,
            'file_type'       => $file->getMimeType(),
            'file_size_kb'    => $sizeKb,
            'description'     => $request->description,
            'validity_date'   => $request->validity_date,
            'is_verified'     => false,
        ]);

        AuditLogService::log(
            'document_uploaded',
            $beneficiary,
            [],
            ['document_type' => $doc->document_type, 'file' => $doc->document_name],
            'Document uploaded by admin'
        );

        return back()->with('success', 'Document uploaded successfully.');
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
