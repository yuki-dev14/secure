<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\DistributionEvent;
use App\Models\Office;
use App\Notifications\DistributionEventCreatedNotification;
use App\Notifications\DistributionScheduleNotification;
use App\Services\AuditLogService;
use App\Services\CashGrantCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DistributionEventController extends Controller
{
    public function __construct(private CashGrantCalculatorService $calculator) {}

    public function index(Request $request): Response
    {
        $events = DistributionEvent::with(['office', 'creator'])
            ->withCount('distributions')
            ->latest('distribution_date_start')
            ->paginate(15)->withQueryString();

        return Inertia::render('Admin/Events/Index', compact('events'));
    }

    public function create(): Response
    {
        $offices = Office::active()->get(['id', 'name', 'barangay']);
        return Inertia::render('Admin/Events/Create', compact('offices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'                   => 'required|string|max:200',
            'period'                  => 'required|string|max:50',
            'period_start'            => 'required|date',
            'period_end'              => 'required|date|after:period_start',
            'months_covered'          => 'required|integer|min:1|max:3',
            // Distribution window spans the entire quarter — no specific sub-dates needed
            'distribution_time_start' => 'nullable|date_format:H:i',
            'distribution_time_end'   => 'nullable|date_format:H:i',
            'office_id'               => 'nullable|exists:offices,id',
            'venue'                   => 'required|string|max:200',
            'venue_address'           => 'nullable|string',
            'notes'                   => 'nullable|string',
        ]);

        $event = DistributionEvent::create([
            ...$validated,
            // Distribution is open for the whole quarter
            'distribution_date_start' => $validated['period_start'],
            'distribution_date_end'   => $validated['period_end'],
            'status'                  => 'upcoming',
            'created_by'              => auth()->id(),
        ]);

        AuditLogService::created($event);

        // Auto-notify all active beneficiaries about the new upcoming event
        $notified = $this->notifyAllBeneficiaries($event, 'upcoming');

        return redirect()->route('admin.events.show', $event)
            ->with('success', "Distribution event created. {$notified} beneficiaries notified of the upcoming quarter.");
    }

    public function show(DistributionEvent $distributionEvent): Response
    {
        $distributionEvent->load([
            'office', 'creator',
            'distributions.beneficiary',
            'distributions.releasedBy',
            'calculations.beneficiary',
        ]);

        $summary = [
            'total_beneficiaries' => Beneficiary::active()->count(),
            'computed'            => $distributionEvent->calculations()->count(),
            'claimed'             => $distributionEvent->distributions()->where('status', 'claimed')->count(),
            'total_released'      => $distributionEvent->distributions()->where('status', 'claimed')->sum('amount_released'),
        ];

        return Inertia::render('Admin/Events/Show', [
            'event'   => $distributionEvent,
            'summary' => $summary,
        ]);
    }

    public function update(Request $request, DistributionEvent $distributionEvent): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:200',
            'venue'   => 'required|string|max:200',
            'status'  => 'required|in:upcoming,ongoing,completed,cancelled',
            'notes'   => 'nullable|string',
        ]);

        $oldStatus = $distributionEvent->status;
        $newStatus = $validated['status'];

        $old = $distributionEvent->toArray();
        $distributionEvent->update($validated);
        AuditLogService::updated($distributionEvent, $old, $distributionEvent->fresh()->toArray());

        $message = 'Distribution event updated.';

        // Auto-compute grants when event goes ONGOING
        if ($oldStatus !== 'ongoing' && $newStatus === 'ongoing') {
            // Batch compute (safety net for those not yet computed individually)
            $results = $this->calculator->batchCalculate($distributionEvent);
            AuditLogService::log(
                'grants_auto_computed_on_ongoing',
                $distributionEvent,
                [],
                $results,
                "Auto-computation on status change to ongoing: {$results['computed']} processed, {$results['eligible']} eligible"
            );

            // Notify all active beneficiaries that claims are now open
            $notified = $this->notifyAllBeneficiaries($distributionEvent, 'ongoing');

            $message = "Event set to Ongoing. "
                . "Grants computed: {$results['eligible']} eligible ✓ · {$results['ineligible']} ineligible. "
                . "{$notified} beneficiaries notified that claims are now open.";
        }

        return back()->with('success', $message);
    }

    public function destroy(DistributionEvent $distributionEvent): RedirectResponse
    {
        if ($distributionEvent->distributions()->exists()) {
            return back()->with('error', 'Cannot delete an event that already has distribution records.');
        }

        AuditLogService::deleted($distributionEvent);
        $distributionEvent->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Distribution event deleted.');
    }

    /**
     * Send in-system notification to all active beneficiaries about the event.
     * Still available as a manual re-send option from the UI.
     */
    public function notifyBeneficiaries(DistributionEvent $event): RedirectResponse
    {
        $count = $this->notifyAllBeneficiaries($event, 'ongoing');
        AuditLogService::log('beneficiaries_notified', $event, [], ['count' => $count],
            "{$count} beneficiaries manually re-notified about {$event->title}");
        return back()->with('success', "{$count} beneficiaries have been notified.");
    }

    /**
     * Notify all active beneficiaries. Sends the appropriate notification class
     * based on the trigger context ('upcoming' = event created, 'ongoing' = claims open).
     *
     * @return int  Number of users notified.
     */
    private function notifyAllBeneficiaries(DistributionEvent $event, string $trigger): int
    {
        $beneficiaries = Beneficiary::active()->with('user')->get();
        $count = 0;

        $notificationClass = $trigger === 'upcoming'
            ? DistributionEventCreatedNotification::class
            : DistributionScheduleNotification::class;

        foreach ($beneficiaries as $beneficiary) {
            if ($beneficiary->user) {
                $beneficiary->user->notify(new $notificationClass($event));
                $count++;
            }
        }

        return $count;
    }

    /**
     * Batch-compute cash grants for all active beneficiaries.
     * Eligibility is determined by the quarterly ComplianceRecord (is_fully_compliant).
     * Ineligible beneficiaries are recorded with zero amounts + reason.
     */
    public function batchComputeGrants(DistributionEvent $event): RedirectResponse
    {
        $results = $this->calculator->batchCalculate($event);

        AuditLogService::log('grants_batch_computed', $event, [], $results,
            "Batch computation: {$results['computed']} processed, {$results['eligible']} eligible, {$results['ineligible']} ineligible, {$results['errors']} errors");

        return back()->with('success',
            "Computation complete: {$results['eligible']} eligible ✓ · {$results['ineligible']} ineligible (no completion record) · Total: ₱" . number_format($results['total_amount'], 2)
        );
    }
}
