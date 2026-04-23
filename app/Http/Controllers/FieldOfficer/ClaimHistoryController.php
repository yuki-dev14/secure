<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CashGrantDistribution;
use App\Models\DistributionEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClaimHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        // ── Filters ───────────────────────────────────────────────────────────
        $search  = (string) $request->string('search')->trim();
        $eventId = $request->integer('event_id') ?: null;
        $tab     = $request->input('tab', 'claims'); // 'claims' | 'double_claims'

        // ── Events list for filter dropdown ───────────────────────────────────
        $events = DistributionEvent::orderByDesc('created_at')
            ->get(['id', 'title', 'period', 'status']);

        // ── Successful Claims ─────────────────────────────────────────────────
        $claimsQuery = CashGrantDistribution::with([
            'beneficiary:id,unique_id,first_name,last_name,middle_name,barangay',
            'distributionEvent:id,title,period,venue',
            'proxy:id,full_name,relationship',
            'releasedBy:id,name',
        ])
        ->claimed()
        ->when($eventId, fn($q) => $q->where('distribution_event_id', $eventId))
        ->when($search, function ($q) use ($search) {
            $q->where(function ($inner) use ($search) {
                $inner->whereHas('beneficiary', function ($bq) use ($search) {
                    $bq->where('unique_id', 'ilike', "%{$search}%")
                       ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"])
                       ->orWhere('last_name', 'ilike', "%{$search}%");
                })->orWhere('transaction_reference', 'ilike', "%{$search}%");
            });
        })
        ->latest('claimed_at');

        $claims = $claimsQuery->paginate(20, ['*'], 'claims_page')
            ->withQueryString()
            ->through(fn($d) => [
                'id'                    => $d->id,
                'transaction_reference' => $d->transaction_reference,
                'claimed_at'            => $d->claimed_at?->format('M d, Y g:i A'),
                'amount_released'       => $d->amount_released,
                'payment_mode'          => $d->payment_mode,
                'claimed_by_type'       => $d->claimed_by_type,
                'beneficiary'           => $d->beneficiary ? [
                    'unique_id' => $d->beneficiary->unique_id,
                    'full_name' => $d->beneficiary->full_name,
                    'barangay'  => $d->beneficiary->barangay,
                ] : null,
                'event' => $d->distributionEvent ? [
                    'title'  => $d->distributionEvent->title,
                    'period' => $d->distributionEvent->period,
                ] : null,
                'proxy' => $d->proxy ? [
                    'full_name'    => $d->proxy->full_name,
                    'relationship' => $d->proxy->relationship,
                ] : null,
                'released_by' => $d->releasedBy?->name,
            ]);

        // ── Double-Claim Attempts from Audit Log ──────────────────────────────
        // new_values is stored as JSONB; cast the extracted text field to integer for filtering.
        $doubleClaimsQuery = AuditLog::with('user:id,name')
            ->where('event', 'double_claim_attempt')
            ->when($eventId, fn($q) => $q->whereRaw(
                "(new_values->>'distribution_event_id')::int = ?", [$eventId]
            ))
            ->when($search, function ($q) use ($search) {
                // Resolve beneficiary IDs matching the search string
                $beneficiaryIds = Beneficiary::where('unique_id', 'ilike', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"])
                    ->pluck('id');

                if ($beneficiaryIds->isNotEmpty()) {
                    $q->whereRaw(
                        "(new_values->>'beneficiary_id')::int = ANY(?)",
                        ['{' . $beneficiaryIds->implode(',') . '}']
                    );
                } else {
                    $q->whereRaw('1=0'); // no match
                }
            })
            ->latest();

        $doubleClaims = $doubleClaimsQuery->paginate(20, ['*'], 'dc_page')
            ->withQueryString()
            ->through(function ($log) {
                $beneficiaryId = data_get($log->new_values, 'beneficiary_id');
                $logEventId    = data_get($log->new_values, 'distribution_event_id');

                $beneficiary = $beneficiaryId
                    ? Beneficiary::find($beneficiaryId, ['id', 'unique_id', 'first_name', 'last_name', 'middle_name', 'barangay'])
                    : null;

                $event = $logEventId
                    ? DistributionEvent::find($logEventId, ['id', 'title', 'period'])
                    : null;

                return [
                    'id'          => $log->id,
                    'detected_at' => $log->created_at?->format('M d, Y g:i A'),
                    'description' => $log->description,
                    'officer_name'=> $log->user?->name ?? 'System',
                    'beneficiary' => $beneficiary ? [
                        'unique_id' => $beneficiary->unique_id,
                        'full_name' => $beneficiary->full_name,
                        'barangay'  => $beneficiary->barangay,
                    ] : null,
                    'event' => $event ? [
                        'title'  => $event->title,
                        'period' => $event->period,
                    ] : null,
                ];
            });

        // ── Summary stats ─────────────────────────────────────────────────────
        $stats = [
            'total_claims'        => CashGrantDistribution::claimed()->count(),
            'total_amount'        => (float) CashGrantDistribution::claimed()->sum('amount_released'),
            'total_double_claims' => AuditLog::where('event', 'double_claim_attempt')->count(),
            'unique_beneficiaries'=> CashGrantDistribution::claimed()
                ->distinct('beneficiary_id')->count('beneficiary_id'),
        ];

        return Inertia::render('FieldOfficer/ClaimHistory', [
            'claims'       => $claims,
            'doubleClaims' => $doubleClaims,
            'events'       => $events,
            'stats'        => $stats,
            'filters'      => [
                'search'   => $search,
                'event_id' => $eventId,
                'tab'      => $tab,
            ],
        ]);
    }
}
