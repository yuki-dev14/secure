<?php

namespace App\Imports;

use App\Models\Beneficiary;
use App\Models\FamilyMember;
use App\Models\NonComplianceRecord;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NonComplianceImport implements ToArray, WithHeadingRow
{
    private int $imported = 0;
    private int $skipped  = 0;

    public function __construct(
        private string  $period,
        private string  $periodStart,
        private string  $periodEnd,
        private string  $category,
        private string  $source,
        private ?string $reporterName,
        private ?string $reporterInstitution,
        private string  $importBatchId,
    ) {}

    public function array(array $rows): void
    {
        foreach ($rows as $row) {
            $uniqueId = trim($row['beneficiary_unique_id'] ?? '');

            if (empty($uniqueId)) {
                $this->skipped++;
                continue;
            }

            // Find beneficiary by unique_id
            $beneficiary = Beneficiary::where('unique_id', $uniqueId)->first();

            if (!$beneficiary) {
                $this->skipped++;
                continue;
            }

            // Optionally resolve family member by name
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

            $reason       = trim($row['reason'] ?? 'Non-compliant');
            $details      = trim($row['details'] ?? '');
            $grantAffected = trim($row['grant_affected'] ?? '');

            // Validate grant_affected
            $validGrants = ['health_grant', 'education_elementary', 'education_junior_high', 'education_senior_high', 'rice_subsidy'];
            if (!in_array($grantAffected, $validGrants)) {
                // Auto-determine from category
                $grantAffected = $this->category === 'education' ? 'education_elementary' : 'health_grant';
            }

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
                'reporter_name'        => $this->reporterName,
                'reporter_institution' => $this->reporterInstitution,
                'reason'               => $reason,
                'details'              => $details ?: null,
                'period'               => $this->period,
                'period_start'         => $this->periodStart,
                'period_end'           => $this->periodEnd,
                'grant_affected'       => $grantAffected,
                'status'               => 'pending',
                'import_batch_id'      => $this->importBatchId,
            ]);

            $this->imported++;
        }
    }

    public function getImportedCount(): int { return $this->imported; }
    public function getSkippedCount(): int  { return $this->skipped; }
}
