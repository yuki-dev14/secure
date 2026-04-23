<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\DistributionEvent;
use App\Services\AuditLogService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScannerController extends Controller
{
    public function __construct(private QrCodeService $qrService) {}

    public function index(): Response
    {
        $activeEvent = DistributionEvent::ongoing()->with('office')->first();

        return Inertia::render('FieldOfficer/Scanner', [
            'activeEvent' => $activeEvent,
        ]);
    }

    /**
     * Process a QR scan and return the beneficiary data with compliance and documents.
     * Accepts either:
     *   - A base64-encoded JSON QR payload (from physical card scan)
     *   - A raw Unique ID string like "4PS-LPA-000001" (from manual entry)
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'payload'  => 'required|string',
            'event_id' => 'nullable|exists:distribution_events,id',
        ]);

        $payload     = trim($request->payload);
        $beneficiary = null;

        // ── Manual Unique ID entry (e.g. "4PS-LPA-000001") ────────────────────
        if (preg_match('/^4PS-LPA-\d+$/i', $payload)) {
            $beneficiary = Beneficiary::where('unique_id', strtoupper($payload))->first();

            if (!$beneficiary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beneficiary not found in the system.',
                ], 422);
            }

            if ($beneficiary->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => "Beneficiary account is {$beneficiary->status}.",
                ], 422);
            }

            // No QR token validation — officer is visually verifying identity for manual lookups
            AuditLogService::log('manual_id_lookup', $beneficiary, [], [
                'officer' => auth()->user()->name,
            ], 'Manual ID lookup by field officer', 'distribution');

        } else {
            // ── Physical QR card scan path ────────────────────────────────────
            $result = $this->qrService->decode($payload);

            if (!$result['valid']) {
                AuditLogService::log('qr_scan_failed', null, [], [
                    'reason' => $result['error'],
                ], 'QR Scan failed: ' . $result['error'], 'distribution,security');

                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 422);
            }

            $beneficiary = $result['beneficiary'];
        }

        // ── Shared: load relationships & build response ────────────────────────
        $beneficiary->load([
            'familyMembers',
            'proxies'           => fn($q) => $q->where('is_approved', true)->where('is_active', true),
            'documents',
            'complianceRecords' => fn($q) => $q->latest()->limit(1),
            'grantCalculations' => fn($q) => $q->when(
                $request->event_id,
                fn($q2) => $q2->where('distribution_event_id', $request->event_id)
            )->latest()->limit(1),
        ]);

        // Check if already claimed in this event
        $alreadyClaimed = false;
        if ($request->event_id) {
            $alreadyClaimed = $beneficiary->distributions()
                ->where('distribution_event_id', $request->event_id)
                ->where('status', 'claimed')
                ->exists();

            if ($alreadyClaimed) {
                AuditLogService::doubleClaim($beneficiary->id, $request->event_id);
            }
        }

        AuditLogService::qrScanned($beneficiary->id, auth()->user()->name);

        return response()->json([
            'success'         => true,
            'beneficiary'     => [
                'id'                   => $beneficiary->id,
                'unique_id'            => $beneficiary->unique_id,
                'full_name'            => $beneficiary->full_name,
                'birthdate'            => $beneficiary->birthdate->format('F d, Y'),
                'age'                  => $beneficiary->age,
                'sex'                  => $beneficiary->sex,
                'barangay'             => $beneficiary->barangay,
                'full_address'         => $beneficiary->full_address,
                'photo_url'            => $beneficiary->photo_path
                    ? asset('storage/' . $beneficiary->photo_path) : null,
                'status'               => $beneficiary->status,
                'is_compliant'         => $beneficiary->is_compliant,
                'family_members_count' => $beneficiary->familyMembers->count(),
            ],
            'compliance'      => $beneficiary->complianceRecords->first() ? [
                'id'                => $beneficiary->complianceRecords->first()->id,
                'period'            => $beneficiary->complianceRecords->first()->period,
                'is_fully_compliant'=> $beneficiary->complianceRecords->first()->is_fully_compliant,
                'verified_by_name'  => $beneficiary->complianceRecords->first()->verifier?->name,
                'notes'             => $beneficiary->complianceRecords->first()->notes,
                'verified_at'       => $beneficiary->complianceRecords->first()->updated_at?->format('M d, Y'),
            ] : null,
            'grant'           => $beneficiary->grantCalculations->first(),
            'proxies'         => $beneficiary->proxies->map(fn($p) => [
                'id'           => $p->id,
                'full_name'    => $p->full_name,
                'relationship' => $p->relationship,
                'valid_id_url' => $p->valid_id_path ? asset('storage/' . $p->valid_id_path) : null,
                'has_docs'     => $p->hasRequiredDocuments(),
            ]),
            'documents'       => $beneficiary->documents->map(fn($d) => [
                'id'            => $d->id,
                'type_label'    => $d->document_type_label,
                'document_name' => $d->document_name,
                'file_url'      => asset('storage/' . $d->file_path),
                'is_verified'   => $d->is_verified,
                'verified_at'   => $d->verified_at?->format('M d, Y'),
                'source'        => $d->source,       // 'admin' = submitted physically
                'validity_date' => $d->validity_date?->format('M d, Y'),
            ]),
            'already_claimed' => $alreadyClaimed,
        ]);
    }
}
