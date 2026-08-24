<?php

namespace App\Imports;

use App\Models\Beneficiary;
use App\Models\FamilyMember;
use App\Models\NonComplianceRecord;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ComplianceVerificationImport implements ToArray, WithHeadingRow
{
    private int $imported = 0;
    private int $skipped  = 0;
    private int $compliant = 0;

    public function __construct(
        private string  $period,
        private string  $periodStart,
        private string  $periodEnd,
        private string  $category,
        private string  $source,
        private string  $importBatchId,
    ) {}

    public function array(array $rows): void
    {
        foreach ($rows as $row) {
            $uniqueId = trim($row['beneficiary_unique_id'] ?? '');
            $status   = strtoupper(trim($row['compliance_status'] ?? 'COMPLIANT'));

            if (empty($uniqueId)) {
                $this->skipped++;
                continue;
            }

            // Only process NON_COMPLIANT rows
            if ($status !== 'NON_COMPLIANT') {
                $this->compliant++;
                continue;
            }

            // Find beneficiary by unique_id
            $beneficiary = Beneficiary::where('unique_id', $uniqueId)->first();

            if (!$beneficiary) {
                $this->skipped++;
                continue;
            }

            // Sync / Create ComplianceRecord for this period
            $compRecord = \App\Models\ComplianceRecord::firstOrNew([
                'beneficiary_id' => $beneficiary->id,
                'period'         => $this->period,
            ]);
            $compRecord->period_start = $this->periodStart;
            $compRecord->period_end   = $this->periodEnd;
            $compRecord->verified_by  = auth()->id() ?? 1;

            if ($status === 'NON_COMPLIANT') {
                if ($this->category === 'education') {
                    $compRecord->edu_attendance_compliant = false;
                } elseif ($this->category === 'health') {
                    $compRecord->health_compliant = false;
                }
                $compRecord->is_fully_compliant = false;
                $compRecord->save();
            } else {
                if (!$compRecord->exists) {
                    $compRecord->edu_attendance_compliant = true;
                    $compRecord->health_compliant         = true;
                    $compRecord->fds_compliant            = true;
                    $compRecord->is_fully_compliant       = true;
                    $compRecord->save();
                }
                $this->compliant++;
                continue;
            }

            // Resolve family member by name if provided
            $familyMemberId = null;
            $memberName = trim($row['family_member_name'] ?? '');
            if (!empty($memberName)) {
                $familyMember = FamilyMember::where('beneficiary_id', $beneficiary->id)
                    ->where(function ($q) use ($memberName) {
                        $parts = explode(' ', $memberName, 2);
                        $firstName = $parts[0] ?? '';
                        $lastName  = $parts[1] ?? '';

                        $q->where(function ($q2) use ($firstName, $lastName) {
                            $q2->where('first_name', 'ilike', "%{$firstName}%");
                            if (!empty($lastName)) {
                                $q2->where('last_name', 'ilike', "%{$lastName}%");
                            }
                        });
                    })
                    ->first();

                $familyMemberId = $familyMember?->id;
            }

            $reason = trim($row['reason'] ?? 'Non-compliant (verification result)');

            // Determine grant_affected based on category and education level
            $grantAffected = $this->resolveGrantAffected($row);

            // Check for duplicates (same beneficiary + member + category + period)
            $exists = NonComplianceRecord::where('beneficiary_id', $beneficiary->id)
                ->where('family_member_id', $familyMemberId)
                ->where('category', $this->category)
                ->where('period', $this->period)
                ->exists();

            if ($exists) {
                $this->skipped++;
                continue;
            }

            NonComplianceRecord::create([
                'beneficiary_id'       => $beneficiary->id,
                'family_member_id'     => $familyMemberId,
                'category'             => $this->category,
                'source'               => $this->source,
                'reporter_name'        => null,
                'reporter_institution' => null,
                'reason'               => $reason,
                'details'              => trim($row['details'] ?? '') ?: null,
                'period'               => $this->period,
                'period_start'         => $this->periodStart,
                'period_end'           => $this->periodEnd,
                'grant_affected'       => $grantAffected,
                'status'               => 'confirmed',  // Auto-confirmed — verifier is authoritative
                'processed_by'         => auth()->id(),
                'processed_at'         => now(),
                'processing_notes'     => 'Auto-confirmed via compliance verification import',
                'import_batch_id'      => $this->importBatchId,
            ]);

            // Update beneficiary compliance status
            $beneficiary->update([
                'is_compliant'          => false,
                'last_compliance_check' => now(),
            ]);

            $this->imported++;
        }
    }

    private function resolveGrantAffected(array $row): string
    {
        if ($this->category === 'health') {
            return 'health_grant';
        }

        // For education, determine based on education_level column if available
        $eduLevel = strtolower(trim($row['education_level'] ?? ''));

        return match (true) {
            str_contains($eduLevel, 'senior')  => 'education_senior_high',
            str_contains($eduLevel, 'junior')  => 'education_junior_high',
            str_contains($eduLevel, 'elem')    => 'education_elementary',
            default                            => 'education_elementary',
        };
    }

    public function getImportedCount(): int  { return $this->imported; }
    public function getSkippedCount(): int   { return $this->skipped; }
    public function getCompliantCount(): int { return $this->compliant; }
}
