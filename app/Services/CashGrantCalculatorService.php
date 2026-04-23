<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\CashGrantCalculation;
use App\Models\ComplianceRecord;
use App\Models\DistributionEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CashGrantCalculatorService
 *
 * Computes the total cash grant amount for a 4Ps beneficiary
 * given a specific distribution event (quarterly).
 *
 * Grant Rates (per RA 11310):
 * - Health Grant:    ₱750.00 / month / household
 * - Education Grant: ₱300.00 / child (Elementary) / month (10 mos/yr)
 *                    ₱500.00 / child (Junior High) / month (10 mos/yr)
 *                    ₱700.00 / child (Senior High) / month (10 mos/yr)
 *                    Maximum 3 children per household
 * - Rice Subsidy:    ₱600.00 / month / household
 *
 * Quarterly Release:
 * - Grants are released every quarter (Q1-Q4), 3 months per grant.
 *
 * Eligibility Rule:
 * - A beneficiary MUST have a ComplianceRecord for the event's quarter (period)
 *   with is_fully_compliant = true to receive any grant.
 * - If no qualifying record exists, all grant amounts are set to 0
 *   and is_eligible = false with an ineligibility_reason is recorded.
 */
class CashGrantCalculatorService
{
    // Grant rates per month
    const HEALTH_GRANT_PER_MONTH      = 750.00;
    const ELEMENTARY_GRANT_PER_MONTH  = 300.00;
    const JUNIOR_HIGH_GRANT_PER_MONTH = 500.00;
    const SENIOR_HIGH_GRANT_PER_MONTH = 700.00;
    const RICE_SUBSIDY_PER_MONTH      = 600.00;
    const MAX_EDUCATION_CHILDREN      = 3;

    /**
     * Check whether a beneficiary has a completed (is_fully_compliant) ComplianceRecord
     * for the given event's quarter (period string e.g. "2026-Q2").
     *
     * Returns null if eligible, or a string reason if not eligible.
     */
    public function getIneligibilityReason(Beneficiary $beneficiary, DistributionEvent $event): ?string
    {
        if ($beneficiary->status !== 'active') {
            return "Beneficiary account is not active (status: {$beneficiary->status}).";
        }

        // Look for a head-level (family_member_id = null) compliance record for this quarter
        $record = ComplianceRecord::where('beneficiary_id', $beneficiary->id)
            ->whereNull('family_member_id')
            ->where('period', $event->period)
            ->first();

        if (!$record) {
            return "No completion record found for quarter {$event->period}. The verifier must record completion before grants can be computed.";
        }

        if (!$record->is_fully_compliant) {
            return "Beneficiary was marked INCOMPLETE for quarter {$event->period} by the verifier.";
        }

        return null; // eligible
    }

    /**
     * Calculate and persist the cash grant for a beneficiary and distribution event.
     * If the beneficiary is ineligible for this quarter, records a zero-amount entry.
     */
    public function calculate(
        Beneficiary $beneficiary,
        DistributionEvent $event,
    ): CashGrantCalculation {
        return DB::transaction(function () use ($beneficiary, $event) {
            $months = $event->months_covered ?? 3; // Default: 1 quarter = 3 months

            // ── Quarterly Eligibility Check ───────────────────────────────────────
            $ineligibilityReason = $this->getIneligibilityReason($beneficiary, $event);
            $isEligible          = $ineligibilityReason === null;

            if (!$isEligible) {
                // Record an ineligible entry with zero amounts
                return CashGrantCalculation::updateOrCreate(
                    [
                        'beneficiary_id'        => $beneficiary->id,
                        'distribution_event_id' => $event->id,
                    ],
                    [
                        'months_covered'              => $months,
                        'is_eligible'                 => false,
                        'ineligibility_reason'        => $ineligibilityReason,
                        'health_grant_eligible'       => false,
                        'health_grant_amount'         => 0.00,
                        'elementary_children_count'   => 0,
                        'elementary_grant_amount'     => 0.00,
                        'junior_high_children_count'  => 0,
                        'junior_high_grant_amount'    => 0.00,
                        'senior_high_children_count'  => 0,
                        'senior_high_grant_amount'    => 0.00,
                        'education_grant_total'       => 0.00,
                        'rice_subsidy_eligible'       => false,
                        'rice_subsidy_amount'         => 0.00,
                        'total_grant_amount'          => 0.00,
                        'compute_status'              => 'computed',
                        'computed_by'                 => auth()->id(),
                        'computed_at'                 => now(),
                        'computation_notes'           => $ineligibilityReason,
                    ]
                );
            }

            // ── Health Grant ──────────────────────────────────────────────────────
            // Eligible beneficiaries (active + quarter complete) receive health grant
            $healthAmount = self::HEALTH_GRANT_PER_MONTH * $months;

            // ── Education Grant ───────────────────────────────────────────────────
            $eligibleChildren = $this->getEducationEligibleChildren($beneficiary);

            // Apply max-3-children rule: prioritize highest grant value
            $allSorted = $eligibleChildren->sortByDesc(fn($c) => match($c->education_level) {
                'senior_high' => 700,
                'junior_high' => 500,
                'elementary'  => 300,
                default       => 0,
            });
            $capped = $allSorted->take(self::MAX_EDUCATION_CHILDREN);

            $elemCount  = $capped->where('education_level', 'elementary')->count();
            $jrHiCount  = $capped->where('education_level', 'junior_high')->count();
            $srHiCount  = $capped->where('education_level', 'senior_high')->count();

            $elemAmount = $elemCount * self::ELEMENTARY_GRANT_PER_MONTH  * $months;
            $jrHiAmount = $jrHiCount * self::JUNIOR_HIGH_GRANT_PER_MONTH * $months;
            $srHiAmount = $srHiCount * self::SENIOR_HIGH_GRANT_PER_MONTH * $months;
            $eduTotal   = $elemAmount + $jrHiAmount + $srHiAmount;

            // ── Rice Subsidy ──────────────────────────────────────────────────────
            $riceAmount = self::RICE_SUBSIDY_PER_MONTH * $months;

            // ── Total ─────────────────────────────────────────────────────────────
            $total = $healthAmount + $eduTotal + $riceAmount;

            // ── Persist ───────────────────────────────────────────────────────────
            return CashGrantCalculation::updateOrCreate(
                [
                    'beneficiary_id'        => $beneficiary->id,
                    'distribution_event_id' => $event->id,
                ],
                [
                    'months_covered'              => $months,
                    'is_eligible'                 => true,
                    'ineligibility_reason'        => null,
                    'health_grant_eligible'       => true,
                    'health_grant_amount'         => $healthAmount,
                    'elementary_children_count'   => $elemCount,
                    'elementary_grant_amount'     => $elemAmount,
                    'junior_high_children_count'  => $jrHiCount,
                    'junior_high_grant_amount'    => $jrHiAmount,
                    'senior_high_children_count'  => $srHiCount,
                    'senior_high_grant_amount'    => $srHiAmount,
                    'education_grant_total'       => $eduTotal,
                    'rice_subsidy_eligible'       => true,
                    'rice_subsidy_amount'         => $riceAmount,
                    'total_grant_amount'          => $total,
                    'compute_status'              => 'computed',
                    'computed_by'                 => auth()->id(),
                    'computed_at'                 => now(),
                    'computation_notes'           => null,
                ]
            );
        });
    }

    /**
     * Compute grants for ALL active beneficiaries in a distribution event (batch).
     *
     * The eligibility check (quarterly completion record) is done inside calculate().
     * Ineligible beneficiaries get a zero-amount record with the reason stored.
     *
     * Returns summary stats: computed, eligible, ineligible, errors, total_amount.
     */
    public function batchCalculate(DistributionEvent $event): array
    {
        // Load ALL active beneficiaries — eligibility is evaluated per-quarter inside calculate()
        $beneficiaries = Beneficiary::active()
            ->with(['familyMembers'])
            ->get();

        $results = [
            'computed'     => 0,
            'eligible'     => 0,
            'ineligible'   => 0,
            'errors'       => 0,
            'total_amount' => 0.00,
        ];

        foreach ($beneficiaries as $beneficiary) {
            try {
                $calc = $this->calculate($beneficiary, $event);
                $results['computed']++;

                if ($calc->is_eligible) {
                    $results['eligible']++;
                    $results['total_amount'] += $calc->total_grant_amount;
                } else {
                    $results['ineligible']++;
                }
            } catch (\Throwable $e) {
                $results['errors']++;
                Log::error("Grant calculation failed for beneficiary #{$beneficiary->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Auto-compute grant for a single beneficiary when their compliance record changes.
     *
     * Finds the DistributionEvent that covers the given quarter period and
     * runs calculate() for this beneficiary only. If no matching active event
     * exists yet, silently does nothing (the batch compute on "Ongoing" will catch it).
     *
     * @return CashGrantCalculation|null  The calculation result, or null if no event found.
     */
    public function calculateForPeriod(Beneficiary $beneficiary, string $period): ?CashGrantCalculation
    {
        // Find the distribution event that covers this quarter
        $event = DistributionEvent::where('period', $period)
            ->whereIn('status', ['upcoming', 'ongoing'])   // only active events
            ->first();

        if (!$event) {
            // No active event for this quarter yet — grant will be computed when event goes Ongoing
            return null;
        }

        return $this->calculate($beneficiary, $event);
    }

    /**
     * Get children eligible for education grant:
     * - 3–18 years old (is_school_age = true)
     * - Enrolled in daycare/preschool/elementary/junior_high/senior_high
     * - 85% attendance rate (for elementary and high school)
     */
    private function getEducationEligibleChildren(Beneficiary $beneficiary)
    {
        return $beneficiary->familyMembers()
            ->where('is_school_age', true)
            ->whereIn('education_level', ['daycare', 'preschool', 'elementary', 'junior_high', 'senior_high'])
            ->where(function ($q) {
                $q->whereIn('education_level', ['daycare', 'preschool'])  // No attendance threshold for daycare/preschool
                  ->orWhere(function ($q2) {
                      $q2->whereIn('education_level', ['elementary', 'junior_high', 'senior_high'])
                         ->where('attendance_rate', '>=', 85.00);          // 85% rule
                  });
            })
            ->get();
    }

    /**
     * Get a formatted breakdown summary for display.
     */
    public function getBreakdownSummary(CashGrantCalculation $calc): array
    {
        return [
            'quarter'        => $calc->distributionEvent->period ?? '—',
            'months_covered' => $calc->months_covered,
            'is_eligible'    => $calc->is_eligible,
            'ineligibility_reason' => $calc->ineligibility_reason,
            'health' => [
                'eligible' => $calc->health_grant_eligible,
                'amount'   => $calc->health_grant_amount,
                'label'    => '₱'.number_format($calc->health_grant_amount, 2).' (₱750 × '.$calc->months_covered.' mos)',
            ],
            'education' => [
                'elementary'  => ['count' => $calc->elementary_children_count,  'amount' => $calc->elementary_grant_amount],
                'junior_high' => ['count' => $calc->junior_high_children_count, 'amount' => $calc->junior_high_grant_amount],
                'senior_high' => ['count' => $calc->senior_high_children_count, 'amount' => $calc->senior_high_grant_amount],
                'total'       => $calc->education_grant_total,
            ],
            'rice' => [
                'eligible' => $calc->rice_subsidy_eligible,
                'amount'   => $calc->rice_subsidy_amount,
                'label'    => '₱'.number_format($calc->rice_subsidy_amount, 2).' (₱600 × '.$calc->months_covered.' mos)',
            ],
            'total' => $calc->total_grant_amount,
        ];
    }

    /**
     * Compute the theoretical maximum grant for any household (for display purposes).
     * (Health + 3 SHS children + Rice Subsidy) × 3 months (1 quarter)
     */
    public static function theoreticalMaximum(int $months = 3): float
    {
        return (
            self::HEALTH_GRANT_PER_MONTH * $months +
            self::SENIOR_HIGH_GRANT_PER_MONTH * 3 * $months +
            self::RICE_SUBSIDY_PER_MONTH * $months
        );
    }
}
