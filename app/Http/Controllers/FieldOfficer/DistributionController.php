<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Http\Controllers\Controller;
use App\Models\CashGrantDistribution;
use App\Models\DistributionEvent;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DistributionController extends Controller
{
    public function index(): Response
    {
        $activeEvent = DistributionEvent::ongoing()->first();

        $distributions = CashGrantDistribution::with([
            'beneficiary', 'proxy', 'releasedBy', 'distributionEvent',
        ])
        ->where('released_by', auth()->id())
        ->latest('claimed_at')
        ->paginate(20);

        return Inertia::render('FieldOfficer/Distribution', compact('distributions', 'activeEvent'));
    }

    /**
     * Release (record) a cash grant distribution.
     * Concurrent-safe via DB transaction + SELECT FOR UPDATE.
     */
    public function release(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'beneficiary_id'          => 'required|exists:beneficiaries,id',
            'distribution_event_id'   => 'required|exists:distribution_events,id',
            'cash_grant_calculation_id' => 'nullable|exists:cash_grant_calculations,id',
            'claimed_by_type'         => 'required|in:beneficiary,proxy',
            'proxy_id'                => 'required_if:claimed_by_type,proxy|nullable|exists:proxies,id',
            'amount_released'         => 'required|numeric|min:1',
            'payment_mode'            => 'required|in:cash,check,ewallet',
            'verification_notes'      => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // ── Concurrent double-claim prevention ────────────────────────────────
            // Lock the beneficiary's distribution row for this event to prevent
            // simultaneous releases from two field officers.
            $existing = CashGrantDistribution::where([
                'beneficiary_id'       => $validated['beneficiary_id'],
                'distribution_event_id'=> $validated['distribution_event_id'],
                'status'               => 'claimed',
            ])->lockForUpdate()->first();

            if ($existing) {
                AuditLogService::doubleClaim(
                    $validated['beneficiary_id'],
                    (string) $validated['distribution_event_id'],
                );

                abort(409, 'This beneficiary has already claimed their grant for this event.');
            }

            $txnRef = 'TXN-'.strtoupper(Str::random(4)).'-'.now()->format('YmdHis');

            CashGrantDistribution::create([
                ...$validated,
                'transaction_reference' => $txnRef,
                'released_by'           => auth()->id(),
                'status'                => 'claimed',
                'claimed_at'            => now(),
                'ip_address'            => $request->ip(),
                'device_info'           => $request->userAgent(),
            ]);

            AuditLogService::grantReleased(
                $validated['beneficiary_id'],
                $validated['amount_released'],
                $txnRef,
            );
        });

        return back()->with('success', 'Cash grant recorded successfully. Transaction logged.');
    }

    public function show(string $txn): Response
    {
        $distribution = CashGrantDistribution::with([
            'beneficiary.familyMembers',
            'beneficiary.documents',
            'proxy',
            'distributionEvent',
            'releasedBy',
            'calculation',
        ])->where('transaction_reference', $txn)->firstOrFail();

        return Inertia::render('FieldOfficer/DistributionReceipt', compact('distribution'));
    }
}
