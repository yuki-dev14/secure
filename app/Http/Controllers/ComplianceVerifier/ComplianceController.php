<?php

namespace App\Http\Controllers\ComplianceVerifier;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\ComplianceRecord;
use App\Notifications\ComplianceResultNotification;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
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
            'family_member_id'              => 'nullable|exists:family_members,id',
            'period'                        => 'required|string|max:30',
            'period_start'                  => 'required|date',
            'period_end'                    => 'required|date|after:period_start',

            // Education
            'edu_enrolled'                  => 'nullable|boolean',
            'edu_attendance_rate'           => 'nullable|numeric|min:0|max:100',
            'edu_attendance_compliant'      => 'nullable|boolean',

            // Health
            'health_immunization_complete'  => 'nullable|boolean',
            'health_weight_monitored'       => 'nullable|boolean',
            'health_last_checkup'           => 'nullable|date',
            'health_compliant'              => 'nullable|boolean',

            // Pregnancy
            'pregnancy_prenatal_compliant'  => 'nullable|boolean',
            'pregnancy_postnatal_compliant' => 'nullable|boolean',
            'pregnancy_professional_delivery'=> 'nullable|boolean',
            'pregnancy_compliant'           => 'nullable|boolean',

            // FDS
            'fds_attended'                  => 'nullable|boolean',
            'fds_date'                      => 'nullable|date',
            'fds_venue'                     => 'nullable|string|max:200',
            'fds_compliant'                 => 'nullable|boolean',

            'remarks'                       => 'nullable|string',
            'non_compliance_reasons'        => 'nullable|string',
        ]);

        // Auto-compute attendance compliance
        if (isset($validated['edu_attendance_rate'])) {
            $validated['edu_attendance_compliant'] = $validated['edu_attendance_rate'] >= 85;
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

        return back()->with('success', 'Compliance record saved successfully.');
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

        return back()->with('success', 'Compliance record updated.');
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

    private function getAvailablePeriods(): array
    {
        $year   = now()->year;
        $periods = [];

        for ($q = 1; $q <= 6; $q++) {
            $start  = now()->startOfYear()->addMonths(($q - 1) * 2);
            $end    = $start->copy()->addMonths(2)->subDay();
            $periods[] = [
                'value' => "{$year}-P{$q}",
                'label' => "Period {$q}: {$start->format('M')} – {$end->format('M Y')}",
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ];
        }

        return $periods;
    }
}
