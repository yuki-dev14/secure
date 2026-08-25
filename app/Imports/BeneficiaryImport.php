<?php

namespace App\Imports;

use App\Models\Beneficiary;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class BeneficiaryImport implements ToCollection, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public array $importedIds   = [];
    public array $skipped       = [];
    public int   $successCount  = 0;
    public int   $skipCount     = 0;

    private int $createdBy;

    public function __construct(int $createdBy)
    {
        $this->createdBy = $createdBy;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because row 1 is heading

            try {
                $this->processRow($row->toArray(), $rowNum);
            } catch (\Throwable $e) {
                $this->skipped[] = [
                    'row'    => $rowNum,
                    'reason' => $e->getMessage(),
                ];
                $this->skipCount++;
            }
        }
    }

    private function processRow(array $row, int $rowNum): void
    {
        // Skip completely empty rows
        $filled = array_filter($row, fn($v) => $v !== null && $v !== '');
        if (empty($filled)) return;

        // Required fields guard
        $firstName    = trim($row['first_name'] ?? '');
        $lastName     = trim($row['last_name']  ?? '');
        $birthdate    = $this->parseDate($row['birthdate'] ?? null);
        $sex          = strtolower(trim($row['sex'] ?? ''));
        $barangay     = trim($row['barangay'] ?? '');
        $listahananId = trim($row['listahanan_id'] ?? '') ?: null;

        $missing = [];
        if (!$firstName) $missing[] = 'first_name';
        if (!$lastName)  $missing[] = 'last_name';
        if (!$birthdate) $missing[] = 'birthdate (YYYY-MM-DD)';
        if (!in_array($sex, ['male', 'female'])) $missing[] = 'sex (male/female)';
        if (!$barangay)  $missing[] = 'barangay';

        if (!empty($missing)) {
            $nameStr = trim("{$firstName} {$lastName}") ?: 'N/A';
            $this->skipped[] = [
                'row'           => $rowNum,
                'listahanan_id' => $listahananId ?? 'N/A',
                'name'          => $nameStr,
                'reason'        => 'Missing required field(s): ' . implode(', ', $missing) . '.',
            ];
            $this->skipCount++;
            return;
        }

        // Skip duplicate Listahanan ID if provided
        if ($listahananId && Beneficiary::where('listahanan_id', $listahananId)->exists()) {
            $nameStr = trim("{$firstName} {$lastName}") ?: 'N/A';
            $this->skipped[] = [
                'row'           => $rowNum,
                'listahanan_id' => $listahananId,
                'name'          => $nameStr,
                'reason'        => "Duplicate record: Listahanan ID '{$listahananId}' already exists in the system database.",
            ];
            $this->skipCount++;
            return;
        }

        DB::transaction(function () use ($row, $rowNum, $firstName, $lastName, $birthdate, $sex, $barangay, $listahananId) {
            $uniqueId = Beneficiary::generateUniqueId();

            $beneficiary = Beneficiary::create([
                'unique_id'           => $uniqueId,
                'listahanan_id'       => $listahananId,
                'first_name'          => $firstName,
                'last_name'           => $lastName,
                'middle_name'         => trim($row['middle_name'] ?? '') ?: null,
                'suffix'              => trim($row['suffix'] ?? '') ?: null,
                'birthdate'           => $birthdate,
                'sex'                 => $sex,
                'civil_status'        => $this->parseCivilStatus($row['civil_status'] ?? ''),
                'contact_number'      => trim($row['contact_number'] ?? '') ?: null,
                'household_head_name' => $firstName . ' ' . $lastName,
                'house_no'            => trim($row['house_no'] ?? '') ?: null,
                'street'              => trim($row['street'] ?? '') ?: null,
                'purok'               => trim($row['purok'] ?? '') ?: null,
                'barangay'            => $barangay,
                'city'                => 'Lipa City',
                'province'            => 'Batangas',
                'zip_code'            => '4217',
                'enrollment_date'     => $this->parseDate($row['enrollment_date'] ?? null),
                'remarks'             => trim($row['remarks'] ?? '') ?: null,
                'status'              => 'active',
                'is_compliant'        => true,
                'created_by'          => $this->createdBy,
            ]);

            // Portal user
            $baseUsername = strtolower(str_replace('-', '', $uniqueId));
            $username     = $baseUsername;
            $counter      = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . '_' . $counter++;
            }

            $user = User::create([
                'name'                 => $firstName . ' ' . $lastName,
                'username'             => $username,
                'email'                => null,
                'password'             => Hash::make('temp'),
                'role'                 => 'beneficiary',
                'is_active'            => true,
                'must_change_password' => true,
            ]);
            $beneficiary->update(['user_id' => $user->id]);

            // Family members — max 3 children per household (RA 11310)
            for ($i = 1; $i <= 3; $i++) {
                $prefix = "member_{$i}_";
                $mFirst = trim($row["{$prefix}first_name"] ?? '');
                $mLast  = trim($row["{$prefix}last_name"]  ?? '');
                $mBirth = $this->parseDate($row["{$prefix}birthdate"] ?? null);
                $mSex   = strtolower(trim($row["{$prefix}sex"] ?? ''));
                $mRel   = strtolower(trim($row["{$prefix}relationship"] ?? ''));

                if (!$mFirst || !$mLast || !$mBirth) continue;

                $age        = Carbon::parse($mBirth)->diffInYears(now());
                $isSchool   = $age >= 3 && $age <= 18;
                $isUnderFive= $age <= 5;

                $educLevel  = trim($row["{$prefix}education_level"] ?? '');
                if (!$educLevel || !in_array($educLevel, ['daycare','preschool','elementary','junior_high','senior_high'])) {
                    $educLevel = $isSchool
                        ? ($age <= 5 ? 'daycare' : ($age <= 12 ? 'elementary' : 'junior_high'))
                        : 'not_applicable';
                }

                $beneficiary->allFamilyMembers()->create([
                    'first_name'      => $mFirst,
                    'last_name'       => $mLast,
                    'middle_name'     => trim($row["{$prefix}middle_name"] ?? '') ?: null,
                    'birthdate'       => $mBirth,
                    'sex'             => in_array($mSex, ['male','female']) ? $mSex : 'female',
                    'relationship'    => $mRel ?: 'child',
                    'education_level' => $educLevel,
                    'school_name'     => trim($row["{$prefix}school_name"] ?? '') ?: null,
                    'is_school_age'   => $isSchool,
                    'is_under_five'   => $isUnderFive,
                    'is_active'       => true,
                ]);
            }

            AuditLogService::created($beneficiary, $beneficiary->toArray());

            $this->importedIds[] = $uniqueId;
            $this->successCount++;
        });
    }

    private function parseDate(mixed $value): ?string
    {
        if (!$value) return null;

        // Excel stores dates as numeric serial numbers
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            } catch (\Throwable) {}
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseCivilStatus(mixed $value): string
    {
        $map = [
            'single'   => 'single',
            'married'  => 'married',
            'widowed'  => 'widowed',
            'widow'    => 'widowed',
            'separated'=> 'separated',
            'live-in'  => 'live-in',
            'live in'  => 'live-in',
            'cohabiting'=> 'live-in',
        ];
        return $map[strtolower(trim($value ?? ''))] ?? 'married';
    }
}
