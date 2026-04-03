<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\DistributionEvent;
use App\Models\Office;
use App\Services\AuditLogService;
use App\Services\CashGrantCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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
            'months_covered'          => 'required|integer|min:1|max:12',
            'distribution_date_start' => 'required|date',
            'distribution_date_end'   => 'required|date|after_or_equal:distribution_date_start',
            'distribution_time_start' => 'nullable|date_format:H:i',
            'distribution_time_end'   => 'nullable|date_format:H:i',
            'office_id'               => 'nullable|exists:offices,id',
            'venue'                   => 'required|string|max:200',
            'venue_address'           => 'nullable|string',
            'notes'                   => 'nullable|string',
        ]);

        $event = DistributionEvent::create([
            ...$validated,
            'status'     => 'upcoming',
            'created_by' => auth()->id(),
        ]);

        AuditLogService::created($event);

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Distribution event created. Remember to notify beneficiaries.');
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

        $old = $distributionEvent->toArray();
        $distributionEvent->update($validated);
        AuditLogService::updated($distributionEvent, $old, $distributionEvent->fresh()->toArray());

        return back()->with('success', 'Distribution event updated.');
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
     */
    public function notifyBeneficiaries(DistributionEvent $event): RedirectResponse
    {
        $beneficiaries = Beneficiary::active()->with('user')->get();
        $count = 0;

        foreach ($beneficiaries as $beneficiary) {
            if ($beneficiary->user) {
                $beneficiary->user->notify(new \App\Notifications\DistributionScheduleNotification($event));
                $count++;
            }
        }

        AuditLogService::log('beneficiaries_notified', $event, [], ['count' => $count],
            "{$count} beneficiaries notified about {$event->title}");

        return back()->with('success', "{$count} beneficiaries have been notified.");
    }

    /**
     * Batch-compute cash grants for all eligible beneficiaries.
     */
    public function batchComputeGrants(DistributionEvent $event): RedirectResponse
    {
        $results = $this->calculator->batchCalculate($event);

        AuditLogService::log('grants_batch_computed', $event, [], $results,
            "Batch computation: {$results['computed']} computed, {$results['errors']} errors");

        return back()->with('success',
            "Grants computed for {$results['computed']} beneficiaries. Total: ₱".number_format($results['total_amount'], 2)
        );
    }
}
