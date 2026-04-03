<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\CashGrantCalculation;
use App\Models\DistributionEvent;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;

/**
 * CashGrantCalculatorService
 *
 * Computes the total cash grant amount for a 4Ps beneficiary
 * given a specific distribution event.
 *
 * Grant Rates (per RA 11310):
 * - Health Grant:    ₱750.00 / month / household
 * - Education Grant: ₱300.00 / child (Elementary) / month (10 mos/yr)
 *                    ₱500.00 / child (Junior High) / month (10 mos/yr)
 *                    ₱700.00 / child (Senior High) / month (10 mos/yr)
 *                    Maximum 3 children per household
 * - Rice Subsidy:    ₱600.00 / month / household
 *
 * Grants are released every 2 months.
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
    const RELEASE_INTERVAL_MONTHS     = 2;     // Grants released bimonthly

    /**
     * Calculate and persist the cash grant for a beneficiary and distribution event.
     * Returns the CashGrantCalculation model.
     */
    public function calculate(
        Beneficiary $beneficiary,
        DistributionEvent $event,
    ): CashGrantCalculation {
        return DB::transaction(function () use ($beneficiary, $event) {
            $months = $event->months_covered ?? self::RELEASE_INTERVAL_MONTHS;

            // ── Health Grant ─────────────────────────────────────────────────────
            $healthEligible = $beneficiary->is_compliant;
            $healthAmount   = $healthEligible
                ? self::HEALTH_GRANT_PER_MONTH * $months
                : 0.00;

            // ── Education Grant ───────────────────────────────────────────────────
            $eligibleChildren = $this->getEducationEligibleChildren($beneficiary);

            $elementary = $eligibleChildren->where('education_level', 'elementary');
            $juniorHigh = $eligibleChildren->where('education_level', 'junior_high');
            $seniorHigh = $eligibleChildren->where('education_level', 'senior_high');

            // Apply max-3-children rule: prioritize highest grant value
            $allSorted = $eligibleChildren->sortByDesc(fn($c) => $c->education_grant_amount);
            $capped    = $allSorted->take(self::MAX_EDUCATION_CHILDREN);

            $elemCount    = $capped->where('education_level', 'elementary')->count();
            $jrHiCount    = $capped->where('education_level', 'junior_high')->count();
            $srHiCount    = $capped->where('education_level', 'senior_high')->count();

            $elemAmount   = $elemCount  * self::ELEMENTARY_GRANT_PER_MONTH  * $months;
            $jrHiAmount   = $jrHiCount  * self::JUNIOR_HIGH_GRANT_PER_MONTH * $months;
            $srHiAmount   = $srHiCount  * self::SENIOR_HIGH_GRANT_PER_MONTH * $months;
            $eduTotal     = $elemAmount + $jrHiAmount + $srHiAmount;

            // ── Rice Subsidy ──────────────────────────────────────────────────────
            $riceEligible = $beneficiary->is_compliant && $beneficiary->status === 'active';
            $riceAmount   = $riceEligible
                ? self::RICE_SUBSIDY_PER_MONTH * $months
                : 0.00;

            // ── Total ─────────────────────────────────────────────────────────────
            $total = $healthAmount + $eduTotal + $riceAmount;

            // ── Persist ───────────────────────────────────────────────────────────
            /** @var CashGrantCalculation $calc */
            $calc = CashGrantCalculation::updateOrCreate(
                [
                    'beneficiary_id'       => $beneficiary->id,
                    'distribution_event_id' => $event->id,
                ],
                [
                    'months_covered'          => $months,
                    'health_grant_eligible'   => $healthEligible,
                    'health_grant_amount'     => $healthAmount,
                    'elementary_children_count' => $elemCount,
                    'elementary_grant_amount'   => $elemAmount,
                    'junior_high_children_count' => $jrHiCount,
                    'junior_high_grant_amount'   => $jrHiAmount,
                    'senior_high_children_count' => $srHiCount,
                    'senior_high_grant_amount'   => $srHiAmount,
                    'education_grant_total'    => $eduTotal,
                    'rice_subsidy_eligible'    => $riceEligible,
                    'rice_subsidy_amount'      => $riceAmount,
                    'total_grant_amount'       => $total,
                    'compute_status'           => 'computed',
                    'computed_by'              => auth()->id(),
                    'computed_at'              => now(),
                ]
            );

            return $calc;
        });
    }

    /**
     * Compute all beneficiaries for a distribution event (batch).
     */
    public function batchCalculate(DistributionEvent $event): array
    {
        $beneficiaries = Beneficiary::active()->compliant()->with('familyMembers')->get();
        $results = ['computed' => 0, 'errors' => 0, 'total_amount' => 0.00];

        foreach ($beneficiaries as $beneficiary) {
            try {
                $calc = $this->calculate($beneficiary, $event);
                $results['computed']++;
                $results['total_amount'] += $calc->total_grant_amount;
            } catch (\Throwable $e) {
                $results['errors']++;
                \Log::error("Grant calculation failed for beneficiary #{$beneficiary->id}: ".$e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get children eligible for education grant:
     * - 3-18 years old
     * - Enrolled in daycare/preschool/elementary/junior_high/senior_high
     * - 85% attendance rate (for elementary and high school)
     */
    private function getEducationEligibleChildren(Beneficiary $beneficiary)
    {
        return $beneficiary->familyMembers()
            ->where('is_school_age', true)
            ->whereIn('education_level', ['daycare', 'preschool', 'elementary', 'junior_high', 'senior_high'])
            ->where(function ($q) {
                $q->whereIn('education_level', ['daycare', 'preschool'])   // attendance not required for daycare/preschool
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
            'period'           => $calc->distributionEvent->period ?? '—',
            'months_covered'   => $calc->months_covered,
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
     * Compute the theoretical maximum grant for any household.
     * (Health + 3 SHS children + Rice Subsidy) × 2 months
     */
    public static function theoreticalMaximum(int $months = 2): float
    {
        return (
            self::HEALTH_GRANT_PER_MONTH * $months +
            self::SENIOR_HIGH_GRANT_PER_MONTH * 3 * $months +
            self::RICE_SUBSIDY_PER_MONTH * $months
        );
    }
}
