<?php

namespace App\Http\Controllers\ComplianceVerifier;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\ComplianceRecord;
use App\Notifications\ComplianceResultNotification;
use App\Services\AuditLogService;
use App\Services\CashGrantCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    public function __construct(private CashGrantCalculatorService $calculator) {}
    public function index(Request $request): Response
    {
        $query = Beneficiary::with(['office', 'familyMembers', 'complianceRecords'])
            ->withCount('familyMembers')
            ->active();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('unique_id', 'ilike', "%{$s}%")
                  ->orWhere('first_name', 'ilike', "%{$s}%")
                  ->orWhere('last_name', 'ilike', "%{$s}%")
            );
        }

        if ($request->filled('barangay')) $query->where('barangay', $request->barangay);
        if ($request->filled('compliant')) {
            $query->where('is_compliant', $request->compliant === 'true');
        }

        $beneficiaries = $query->paginate(20)->withQueryString();
        $barangays     = Beneficiary::active()->distinct()->pluck('barangay');

        return Inertia::render('ComplianceVerifier/Beneficiaries', compact('beneficiaries', 'barangays'));
    }

    public function show(int $id): Response
    {
        $beneficiary = Beneficiary::with([
            'familyMembers', 'documents',
            'complianceRecords.verifier',
            'complianceRecords.familyMember',
        ])->findOrFail($id);

        $periods = $this->getAvailablePeriods();
        $latestCompliance = $beneficiary->complianceRecords()->latest()->first();

        return Inertia::render('ComplianceVerifier/BeneficiaryDetail', compact(
            'beneficiary', 'periods', 'latestCompliance'
        ));
    }

    /**
     * Record compliance for a beneficiary and optionally a specific family member.
     */
    public function store(Request $request, int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);

        $validated = $request->validate([
            'family_member_id'               => 'nullable|exists:family_members,id',
            'period'                         => 'required|string|max:50',
            'period_start'                   => 'required|date',
            'period_end'                     => 'required|date|after:period_start',

            // Education
            'edu_enrolled'                   => 'nullable|boolean',
            'edu_attendance_rate'            => 'nullable|numeric|min:0|max:100',
            'edu_attendance_compliant'       => 'nullable|boolean',

            // Health 0-5
            'health_immunization_complete'   => 'nullable|boolean',
            'health_weight_monitored'        => 'nullable|boolean',
            'health_last_checkup'            => 'nullable|date',
            'health_compliant'               => 'nullable|boolean',

            // Pregnancy
            'pregnancy_prenatal_compliant'   => 'nullable|boolean',
            'pregnancy_postnatal_compliant'  => 'nullable|boolean',
            'pregnancy_professional_delivery'=> 'nullable|boolean',
            'pregnancy_compliant'            => 'nullable|boolean',

            // FDS
            'fds_attended'                   => 'nullable|boolean',
            'fds_date'                       => 'nullable|date',
            'fds_venue'                      => 'nullable|string|max:200',
            'fds_compliant'                  => 'nullable|boolean',

            'is_fully_compliant'             => 'nullable|boolean',
            'is_override'                    => 'nullable|boolean',
            'override_reason'                => 'nullable|string|max:500',
            'remarks'                        => 'nullable|string',
            'non_compliance_reasons'         => 'nullable|string',
        ]);

        // Auto-compute attendance compliance
        if (isset($validated['edu_attendance_rate'])) {
            $validated['edu_attendance_compliant'] = $validated['edu_attendance_rate'] >= 85;
        }

        // Use frontend-computed is_fully_compliant (sent as 1/0 by the form).
        // If not provided, auto-compute from sub-fields as server-side fallback.
        // Requires EXPLICIT true — null/unanswered counts as incomplete.
        if (!array_key_exists('is_fully_compliant', $validated) || $validated['is_fully_compliant'] === null) {
            $hasSchoolAge = $beneficiary->familyMembers()->where('is_school_age', true)->exists();
            $hasUnderFive = $beneficiary->familyMembers()->where('is_under_five', true)->exists();
            $hasPregnant  = $beneficiary->familyMembers()->where('is_pregnant', true)->exists();

            $eduOk      = !$hasSchoolAge || ($validated['edu_attendance_compliant'] === true);
            $healthOk   = !$hasUnderFive || ($validated['health_compliant'] === true);
            $pregnancyOk= !$hasPregnant  || ($validated['pregnancy_compliant'] === true);
            $fdsOk      = ($validated['fds_attended'] === true);

            $validated['is_fully_compliant'] = $eduOk && $healthOk && $pregnancyOk && $fdsOk;
        }

        $record = ComplianceRecord::updateOrCreate(
            [
                'beneficiary_id'  => $beneficiary->id,
                'family_member_id'=> $validated['family_member_id'] ?? null,
                'period'          => $validated['period'],
            ],
            array_merge($validated, ['verified_by' => auth()->id()])
        );

        // Recompute household compliance
        $this->recomputeHouseholdCompliance($beneficiary);

        AuditLogService::log('compliance_recorded', $record, [], $record->toArray(),
            "Compliance recorded for {$beneficiary->unique_id} — Period: {$validated['period']}");

        // Notify beneficiary via in-app + email
        if ($beneficiary->user) {
            $beneficiary->user->notify(new ComplianceResultNotification($record->fresh()));
        }

        // Auto-compute grant for this beneficiary's quarter if an active event exists
        $grantMsg = '';
        if ($record->is_fully_compliant) {
            $calc = $this->calculator->calculateForPeriod($beneficiary, $validated['period']);
            if ($calc) {
                $amount = number_format($calc->total_grant_amount, 2);
                $grantMsg = " Grant auto-computed: ₱{$amount}.";
            }
        }

        return back()->with('success', "Completion record saved successfully.{$grantMsg}");
    }

    public function update(Request $request, ComplianceRecord $complianceRecord): RedirectResponse
    {
        $validated = $request->validate([
            'is_fully_compliant'        => 'required|boolean',
            'remarks'                   => 'nullable|string',
            'non_compliance_reasons'    => 'nullable|string',
        ]);

        $old = $complianceRecord->toArray();
        $complianceRecord->update($validated);

        $this->recomputeHouseholdCompliance($complianceRecord->beneficiary);
        AuditLogService::updated($complianceRecord, $old, $complianceRecord->fresh()->toArray());

        // Re-compute grant whenever compliance status changes
        $grantMsg = '';
        $calc = $this->calculator->calculateForPeriod(
            $complianceRecord->beneficiary,
            $complianceRecord->period
        );
        if ($calc) {
            $status   = $calc->is_eligible ? '✓ Eligible — ₱' . number_format($calc->total_grant_amount, 2) : '✕ Ineligible';
            $grantMsg = " Grant updated: {$status}.";
        }

        return back()->with('success', "Completion record updated.{$grantMsg}");
    }

    public function periods(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->getAvailablePeriods());
    }

    /**
     * Recompute the is_compliant flag on the beneficiary household.
     * A household is compliant if the representative-level record is fully compliant.
     */
    private function recomputeHouseholdCompliance(Beneficiary $beneficiary): void
    {
        $latestRecord = $beneficiary->complianceRecords()
            ->whereNull('family_member_id')
            ->latest()
            ->first();

        $isCompliant = $latestRecord?->is_fully_compliant ?? false;

        $beneficiary->update([
            'is_compliant'          => $isCompliant,
            'last_compliance_check' => now(),
        ]);
    }

    /**
     * Generate available quarters for compliance recording.
     * Returns Q1–Q4 for the current year and Q1–Q4 for the next year (for advance planning).
     *
     * Quarter definitions (4Ps standard):
     *   Q1: January 1 – March 31
     *   Q2: April 1 – June 30
     *   Q3: July 1 – September 30
     *   Q4: October 1 – December 31
     */
    private function getAvailablePeriods(): array
    {
        $quarters = [
            ['q' => 1, 'label' => 'Q1 (First Quarter)',  'start' => '01-01', 'end' => '03-31'],
            ['q' => 2, 'label' => 'Q2 (Second Quarter)', 'start' => '04-01', 'end' => '06-30'],
            ['q' => 3, 'label' => 'Q3 (Third Quarter)',  'start' => '07-01', 'end' => '09-30'],
            ['q' => 4, 'label' => 'Q4 (Fourth Quarter)', 'start' => '10-01', 'end' => '12-31'],
        ];

        $periods = [];
        $currentYear = now()->year;

        // Show current year and next year quarters
        foreach ([$currentYear, $currentYear + 1] as $year) {
            foreach ($quarters as $q) {
                $periods[] = [
                    'value' => "{$year}-Q{$q['q']}",
                    'label' => "{$year} {$q['label']}: " . date('M j', strtotime("{$year}-{$q['start']}")) . " – " . date('M j', strtotime("{$year}-{$q['end']}")) ,
                    'start' => "{$year}-{$q['start']}",
                    'end'   => "{$year}-{$q['end']}",
                ];
            }
        }

        return $periods;
    }
}
