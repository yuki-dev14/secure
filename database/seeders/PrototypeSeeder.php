<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\BeneficiaryCard;
use App\Models\CashGrantCalculation;
use App\Models\CashGrantDistribution;
use App\Models\ComplianceRecord;
use App\Models\DistributionEvent;
use App\Models\FamilyMember;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrototypeSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('role', 'superadmin')->first();
        $verifier   = User::where('role', 'compliance_verifier')->first();
        $officer    = User::where('role', 'field_officer')->first();
        $office     = Office::first();

        // ── 10 Realistic Lipa City Households ──────────────────────────────────
        $households = [
            [
                'beneficiary' => [
                    'first_name'    => 'Maria',
                    'last_name'     => 'Dela Cruz',
                    'middle_name'   => 'Santos',
                    'birthdate'     => '1988-04-12',
                    'sex'           => 'female',
                    'civil_status'  => 'married',
                    'contact_number'=> '09171234001',
                    'house_no'      => '12',
                    'street'        => 'Mabini St.',
                    'purok'         => '3',
                    'barangay'      => 'Anilao',
                    'listahanan_id' => 'LH-2024-000101',
                    'status'        => 'active',
                    'is_compliant'  => true,
                    'enrollment_date'=> '2023-01-15',
                ],
                'members' => [
                    ['first_name'=>'Juan','last_name'=>'Dela Cruz','birthdate'=>'2014-06-20','sex'=>'male','relationship'=>'child','is_school_age'=>true,'education_level'=>'elementary','grade_level'=>'Grade 5','school_name'=>'Anilao Elementary School','attendance_rate'=>92.5,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Ana','last_name'=>'Dela Cruz','birthdate'=>'2017-09-03','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'elementary','grade_level'=>'Grade 2','school_name'=>'Anilao Elementary School','attendance_rate'=>88.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Carlos','last_name'=>'Dela Cruz','birthdate'=>'2021-03-10','sex'=>'male','relationship'=>'child','is_school_age'=>false,'education_level'=>null,'grade_level'=>null,'school_name'=>null,'attendance_rate'=>null,'is_under_five'=>true,'is_fully_immunized'=>true],
                ],
                'compliant'  => true,
                'compliance' => ['edu_rate'=>92.5,'health'=>true,'fds'=>true],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Rosario',
                    'last_name'     => 'Villanueva',
                    'middle_name'   => 'Reyes',
                    'birthdate'     => '1991-07-22',
                    'sex'           => 'female',
                    'civil_status'  => 'single',
                    'contact_number'=> '09281234002',
                    'house_no'      => '45',
                    'street'        => 'Rizal Ave.',
                    'purok'         => '1',
                    'barangay'      => 'Marawoy',
                    'listahanan_id' => 'LH-2024-000102',
                    'status'        => 'active',
                    'is_compliant'  => true,
                    'enrollment_date'=> '2023-02-01',
                ],
                'members' => [
                    ['first_name'=>'Lito','last_name'=>'Villanueva','birthdate'=>'2012-11-14','sex'=>'male','relationship'=>'child','is_school_age'=>true,'education_level'=>'junior_high','grade_level'=>'Grade 8','school_name'=>'Lipa City National HS','attendance_rate'=>91.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Rosa','last_name'=>'Villanueva','birthdate'=>'2016-05-28','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'elementary','grade_level'=>'Grade 4','school_name'=>'Marawoy Elem School','attendance_rate'=>95.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                ],
                'compliant'  => true,
                'compliance' => ['edu_rate'=>93.0,'health'=>true,'fds'=>true],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Eduardo',
                    'last_name'     => 'Bautista',
                    'middle_name'   => 'Fernandez',
                    'birthdate'     => '1985-12-05',
                    'sex'           => 'male',
                    'civil_status'  => 'married',
                    'contact_number'=> '09391234003',
                    'house_no'      => '7',
                    'street'        => 'Luna St.',
                    'purok'         => '2',
                    'barangay'      => 'Balintawak',
                    'listahanan_id' => 'LH-2024-000103',
                    'status'        => 'active',
                    'is_compliant'  => false,
                    'enrollment_date'=> '2023-01-20',
                ],
                'members' => [
                    ['first_name'=>'Grace','last_name'=>'Bautista','birthdate'=>'2010-03-17','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'senior_high','grade_level'=>'Grade 11','school_name'=>'Lipa City Science HS','attendance_rate'=>78.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Mark','last_name'=>'Bautista','birthdate'=>'2013-08-25','sex'=>'male','relationship'=>'child','is_school_age'=>true,'education_level'=>'junior_high','grade_level'=>'Grade 7','school_name'=>'Balintawak NHS','attendance_rate'=>72.0,'is_under_five'=>false,'is_fully_immunized'=>false],
                ],
                'compliant'  => false,
                'compliance' => ['edu_rate'=>75.0,'health'=>false,'fds'=>true],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Teresita',
                    'last_name'     => 'Manalo',
                    'middle_name'   => 'Cruz',
                    'birthdate'     => '1993-02-18',
                    'sex'           => 'female',
                    'civil_status'  => 'married',
                    'contact_number'=> '09501234004',
                    'house_no'      => '23',
                    'street'        => 'Bonifacio St.',
                    'purok'         => '4',
                    'barangay'      => 'Dagatan',
                    'listahanan_id' => 'LH-2024-000104',
                    'status'        => 'active',
                    'is_compliant'  => true,
                    'enrollment_date'=> '2023-03-10',
                ],
                'members' => [
                    ['first_name'=>'Kristine','last_name'=>'Manalo','birthdate'=>'2019-07-04','sex'=>'female','relationship'=>'child','is_school_age'=>false,'education_level'=>null,'grade_level'=>null,'school_name'=>null,'attendance_rate'=>null,'is_under_five'=>true,'is_fully_immunized'=>true],
                    ['first_name'=>'Patrick','last_name'=>'Manalo','birthdate'=>'2022-01-30','sex'=>'male','relationship'=>'child','is_school_age'=>false,'education_level'=>null,'grade_level'=>null,'school_name'=>null,'attendance_rate'=>null,'is_under_five'=>true,'is_fully_immunized'=>true],
                ],
                'compliant'  => true,
                'compliance' => ['edu_rate'=>null,'health'=>true,'fds'=>true],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Renato',
                    'last_name'     => 'Salazar',
                    'middle_name'   => 'Gomez',
                    'birthdate'     => '1979-09-30',
                    'sex'           => 'male',
                    'civil_status'  => 'married',
                    'contact_number'=> '09611234005',
                    'house_no'      => '88',
                    'street'        => 'Aguinaldo Road',
                    'purok'         => '5',
                    'barangay'      => 'Tambo',
                    'listahanan_id' => 'LH-2024-000105',
                    'status'        => 'active',
                    'is_compliant'  => true,
                    'enrollment_date'=> '2023-01-08',
                ],
                'members' => [
                    ['first_name'=>'Jessa','last_name'=>'Salazar','birthdate'=>'2009-04-22','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'senior_high','grade_level'=>'Grade 12','school_name'=>'STI Lipa','attendance_rate'=>96.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Ryan','last_name'=>'Salazar','birthdate'=>'2012-12-01','sex'=>'male','relationship'=>'child','is_school_age'=>true,'education_level'=>'junior_high','grade_level'=>'Grade 9','school_name'=>'Lipa City National HS','attendance_rate'=>89.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Sophia','last_name'=>'Salazar','birthdate'=>'2015-06-15','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'elementary','grade_level'=>'Grade 6','school_name'=>'Tambo Elem School','attendance_rate'=>90.5,'is_under_five'=>false,'is_fully_immunized'=>true],
                ],
                'compliant'  => true,
                'compliance' => ['edu_rate'=>91.8,'health'=>true,'fds'=>true],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Lourdes',
                    'last_name'     => 'Aquino',
                    'middle_name'   => 'Buenaventura',
                    'birthdate'     => '1995-03-14',
                    'sex'           => 'female',
                    'civil_status'  => 'single',
                    'contact_number'=> '09721234006',
                    'house_no'      => '3',
                    'street'        => 'Del Pilar St.',
                    'purok'         => '2',
                    'barangay'      => 'Lipa City Poblacion',
                    'listahanan_id' => 'LH-2024-000106',
                    'status'        => 'active',
                    'is_compliant'  => false,
                    'enrollment_date'=> '2023-04-05',
                ],
                'members' => [
                    ['first_name'=>'Junjun','last_name'=>'Aquino','birthdate'=>'2016-10-09','sex'=>'male','relationship'=>'child','is_school_age'=>true,'education_level'=>'elementary','grade_level'=>'Grade 4','school_name'=>'Poblacion Elem School','attendance_rate'=>81.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                ],
                'compliant'  => false,
                'compliance' => ['edu_rate'=>81.0,'health'=>true,'fds'=>false],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Arnaldo',
                    'last_name'     => 'Ramos',
                    'middle_name'   => 'Ignacio',
                    'birthdate'     => '1982-06-25',
                    'sex'           => 'male',
                    'civil_status'  => 'married',
                    'contact_number'=> '09831234007',
                    'house_no'      => '56',
                    'street'        => 'Maharlika Highway',
                    'purok'         => '1',
                    'barangay'      => 'Pinagkawitan',
                    'listahanan_id' => 'LH-2024-000107',
                    'status'        => 'active',
                    'is_compliant'  => true,
                    'enrollment_date'=> '2023-02-14',
                ],
                'members' => [
                    ['first_name'=>'Nikki','last_name'=>'Ramos','birthdate'=>'2011-02-14','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'junior_high','grade_level'=>'Grade 8','school_name'=>'Pinagkawitan NHS','attendance_rate'=>93.5,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Oliver','last_name'=>'Ramos','birthdate'=>'2020-08-19','sex'=>'male','relationship'=>'child','is_school_age'=>false,'education_level'=>null,'grade_level'=>null,'school_name'=>null,'attendance_rate'=>null,'is_under_five'=>true,'is_fully_immunized'=>true],
                ],
                'compliant'  => true,
                'compliance' => ['edu_rate'=>93.5,'health'=>true,'fds'=>true],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Cecilia',
                    'last_name'     => 'Mendoza',
                    'middle_name'   => 'Abad',
                    'birthdate'     => '1989-11-03',
                    'sex'           => 'female',
                    'civil_status'  => 'widowed',
                    'contact_number'=> '09941234008',
                    'house_no'      => '14',
                    'street'        => 'Quezon Ave.',
                    'purok'         => '3',
                    'barangay'      => 'Bagong Pook',
                    'listahanan_id' => 'LH-2024-000108',
                    'status'        => 'active',
                    'is_compliant'  => true,
                    'enrollment_date'=> '2023-01-30',
                ],
                'members' => [
                    ['first_name'=>'Rina','last_name'=>'Mendoza','birthdate'=>'2013-05-07','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'junior_high','grade_level'=>'Grade 7','school_name'=>'Bagong Pook NHS','attendance_rate'=>87.5,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Joel','last_name'=>'Mendoza','birthdate'=>'2018-02-22','sex'=>'male','relationship'=>'child','is_school_age'=>false,'education_level'=>null,'grade_level'=>null,'school_name'=>null,'attendance_rate'=>null,'is_under_five'=>true,'is_fully_immunized'=>true],
                    ['first_name'=>'Lea','last_name'=>'Mendoza','birthdate'=>'2023-09-11','sex'=>'female','relationship'=>'child','is_school_age'=>false,'education_level'=>null,'grade_level'=>null,'school_name'=>null,'attendance_rate'=>null,'is_under_five'=>true,'is_fully_immunized'=>false],
                ],
                'compliant'  => true,
                'compliance' => ['edu_rate'=>87.5,'health'=>true,'fds'=>true],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Feliciano',
                    'last_name'     => 'Torres',
                    'middle_name'   => 'Navarro',
                    'birthdate'     => '1975-08-17',
                    'sex'           => 'male',
                    'civil_status'  => 'married',
                    'contact_number'=> '09051234009',
                    'house_no'      => '99',
                    'street'        => 'Gen. Luna St.',
                    'purok'         => '6',
                    'barangay'      => 'Sico',
                    'listahanan_id' => 'LH-2024-000109',
                    'status'        => 'active',
                    'is_compliant'  => false,
                    'enrollment_date'=> '2023-03-20',
                ],
                'members' => [
                    ['first_name'=>'Cathy','last_name'=>'Torres','birthdate'=>'2008-07-30','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'senior_high','grade_level'=>'Grade 12','school_name'=>'Lipa City College HS','attendance_rate'=>70.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Dennis','last_name'=>'Torres','birthdate'=>'2011-11-11','sex'=>'male','relationship'=>'child','is_school_age'=>true,'education_level'=>'junior_high','grade_level'=>'Grade 9','school_name'=>'Sico NHS','attendance_rate'=>65.0,'is_under_five'=>false,'is_fully_immunized'=>false],
                    ['first_name'=>'Nadia','last_name'=>'Torres','birthdate'=>'2014-04-04','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'elementary','grade_level'=>'Grade 6','school_name'=>'Sico Elem School','attendance_rate'=>82.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                ],
                'compliant'  => false,
                'compliance' => ['edu_rate'=>72.3,'health'=>false,'fds'=>false],
            ],
            [
                'beneficiary' => [
                    'first_name'    => 'Dolores',
                    'last_name'     => 'Pascual',
                    'middle_name'   => 'Espiritu',
                    'birthdate'     => '1987-01-09',
                    'sex'           => 'female',
                    'civil_status'  => 'married',
                    'contact_number'=> '09161234010',
                    'house_no'      => '31',
                    'street'        => 'F. Aguilar St.',
                    'purok'         => '2',
                    'barangay'      => 'Tibig',
                    'listahanan_id' => 'LH-2024-000110',
                    'status'        => 'active',
                    'is_compliant'  => true,
                    'enrollment_date'=> '2023-02-28',
                ],
                'members' => [
                    ['first_name'=>'Jenny','last_name'=>'Pascual','birthdate'=>'2010-12-25','sex'=>'female','relationship'=>'child','is_school_age'=>true,'education_level'=>'senior_high','grade_level'=>'Grade 11','school_name'=>'Tibig National HS','attendance_rate'=>94.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                    ['first_name'=>'Kevin','last_name'=>'Pascual','birthdate'=>'2015-09-14','sex'=>'male','relationship'=>'child','is_school_age'=>true,'education_level'=>'elementary','grade_level'=>'Grade 5','school_name'=>'Tibig Elem School','attendance_rate'=>90.0,'is_under_five'=>false,'is_fully_immunized'=>true],
                ],
                'compliant'  => true,
                'compliance' => ['edu_rate'=>92.0,'health'=>true,'fds'=>true],
            ],
        ];

        // ── Distribution Event for Q1 2026 ─────────────────────────────────────
        $event = DistributionEvent::create([
            'title'                    => 'Q1 2026 Cash Grant Distribution',
            'period'                   => 'January–February 2026',
            'period_start'             => '2026-01-01',
            'period_end'               => '2026-02-28',
            'months_covered'           => 2,
            'distribution_date_start'  => '2026-04-14',
            'distribution_date_end'    => '2026-04-15',
            'distribution_time_start'  => '08:00:00',
            'distribution_time_end'    => '17:00:00',
            'office_id'                => $office?->id ?? 1,
            'venue'                    => 'Lipa City SWDO Main Office',
            'venue_address'            => 'Marawoy, Lipa City, Batangas',
            'status'                   => 'ongoing',
            'notes'                    => 'Bimonthly cash grant release for Q1 2026 — Jan to Feb period.',
            'created_by'               => $superadmin?->id ?? 1,
        ]);

        $txCounter = 1000;

        foreach ($households as $index => $data) {
            // ── Create User Account ─────────────────────────────────────────────
            $uniqueId = Beneficiary::generateUniqueId();
            $slug     = Str::lower($data['beneficiary']['first_name'].'-'.Str::slug($data['beneficiary']['last_name']));
            $suffix   = substr(md5($slug . now()->timestamp . $index), 0, 6);

            $user = User::create([
                'name'              => $data['beneficiary']['first_name'].' '.$data['beneficiary']['last_name'],
                'email'             => $slug.'.'.$suffix.'@4ps.lipa.gov.ph',
                'username'          => $slug.'-'.$suffix,
                'password'          => Hash::make('Beneficiary@1234!'),
                'role'              => 'beneficiary',
                'is_active'         => true,
                'must_change_password' => false,
            ]);

            // ── Create Beneficiary ──────────────────────────────────────────────
            $beneficiaryData = array_merge($data['beneficiary'], [
                'household_head_name' => $data['beneficiary']['first_name'].' '.$data['beneficiary']['last_name'],
                'user_id'    => $user->id,
                'unique_id'  => $uniqueId,
                'office_id'  => $office?->id ?? 1,
                'city'       => 'Lipa City',
                'province'   => 'Batangas',
                'zip_code'   => '4217',
                'created_by' => $superadmin?->id ?? 1,
                'last_compliance_check' => now()->subDays(rand(3, 20)),
            ]);
            $beneficiary = Beneficiary::create($beneficiaryData);

            // ── Create Family Members ───────────────────────────────────────────
            foreach ($data['members'] as $m) {
                // Remove null enum values — DB has defaults for these
                $memberData = array_filter($m, fn($v) => $v !== null);
                FamilyMember::create(array_merge([
                    'beneficiary_id'     => $beneficiary->id,
                    'last_name'          => $beneficiary->last_name,
                    'is_household_head'  => false,
                    'is_active'          => true,
                    'weight_kg'          => rand(180, 550) / 10,
                    'height_cm'          => rand(500, 1700) / 10,
                    'nutritional_status' => 'normal',
                    'education_level'    => 'not_applicable', // default, overridden below if set
                ], $memberData));
            }

            // ── Create Compliance Record (P1 2026) ──────────────────────────────
            $c = $data['compliance'];
            $eduRate      = $c['edu_rate'];
            $eduCompliant = $eduRate !== null ? ($eduRate >= 85) : null;
            $overallOk    = $data['compliant'];

            $verifiedBy = $verifier?->id ?? $superadmin?->id ?? 1;

            ComplianceRecord::create([
                'beneficiary_id'           => $beneficiary->id,
                'family_member_id'         => null,
                'verified_by'              => $verifiedBy,
                'period'                   => '2026-P1',
                'period_start'             => '2026-01-01',
                'period_end'               => '2026-02-28',
                'edu_enrolled'             => $eduRate !== null,
                'edu_attendance_rate'      => $eduRate,
                'edu_attendance_compliant' => $eduCompliant,
                'health_immunization_complete' => true,
                'health_weight_monitored'  => true,
                'health_last_checkup'      => '2026-02-10',
                'health_compliant'         => $c['health'],
                'fds_attended'             => $c['fds'],
                'fds_date'                 => '2026-02-15',
                'fds_venue'                => 'Barangay Hall',
                'fds_compliant'            => $c['fds'],
                'is_fully_compliant'       => $overallOk,
                'remarks'                  => $overallOk
                    ? 'All conditions met for Q1 2026.'
                    : 'Household did not meet the required conditions for Q1 2026.',
                'non_compliance_reasons'   => !$overallOk
                    ? ($eduRate !== null && $eduRate < 85 ? 'Attendance below 85% threshold. ' : '').
                      (!$c['health'] ? 'Health conditions not met. ' : '').
                      (!$c['fds'] ? 'FDS session not attended.' : '')
                    : null,
            ]);

            // ── Update beneficiary is_compliant ─────────────────────────────────
            $beneficiary->update([
                'is_compliant'          => $overallOk,
                'last_compliance_check' => now()->subDays(rand(1, 7)),
            ]);

            // ── Grant Computation (only for compliant beneficiaries) ────────────
            if ($overallOk) {
                $months       = 2;
                $healthGrant  = 750 * $months;
                $riceSubsidy  = 600 * $months;

                // Count eligible school-age children (max 3)
                $members    = $data['members'];
                $elementary = min(3, count(array_filter($members, fn($m) => ($m['education_level'] ?? '') === 'elementary' && ($m['attendance_rate'] ?? 0) >= 85)));
                $juniorHigh = min(3 - $elementary, count(array_filter($members, fn($m) => ($m['education_level'] ?? '') === 'junior_high' && ($m['attendance_rate'] ?? 0) >= 85)));
                $seniorHigh = min(3 - $elementary - $juniorHigh, count(array_filter($members, fn($m) => ($m['education_level'] ?? '') === 'senior_high' && ($m['attendance_rate'] ?? 0) >= 85)));

                $elemGrant   = $elementary * 300 * $months;
                $juniorGrant = $juniorHigh * 500 * $months;
                $seniorGrant = $seniorHigh * 700 * $months;
                $eduTotal    = $elemGrant + $juniorGrant + $seniorGrant;
                $total       = $healthGrant + $eduTotal + $riceSubsidy;

                $calc = CashGrantCalculation::create([
                    'beneficiary_id'           => $beneficiary->id,
                    'distribution_event_id'    => $event->id,
                    'months_covered'           => $months,
                    'health_grant_eligible'    => true,
                    'health_grant_amount'      => $healthGrant,
                    'elementary_children_count'=> $elementary,
                    'elementary_grant_amount'  => $elemGrant,
                    'junior_high_children_count'=> $juniorHigh,
                    'junior_high_grant_amount' => $juniorGrant,
                    'senior_high_children_count'=> $seniorHigh,
                    'senior_high_grant_amount' => $seniorGrant,
                    'education_grant_total'    => $eduTotal,
                    'rice_subsidy_eligible'    => true,
                    'rice_subsidy_amount'      => $riceSubsidy,
                    'total_grant_amount'       => $total,
                    'compute_status'           => 'computed',
                    'computed_by'              => $superadmin?->id ?? 1,
                    'computed_at'              => now()->subDays(5),
                    'computation_notes'        => 'Auto-computed for Q1 2026 prototype.',
                ]);

                // ── Mark some as already claimed (first 5 compliant) ────────────
                $claimedSoFar = CashGrantDistribution::where('distribution_event_id', $event->id)->where('status', 'claimed')->count();
                if ($claimedSoFar < 5) {
                    $txRef = 'TXN-Q1-2026-'.str_pad($txCounter++, 4, '0', STR_PAD_LEFT);
                    CashGrantDistribution::create([
                        'beneficiary_id'           => $beneficiary->id,
                        'distribution_event_id'    => $event->id,
                        'cash_grant_calculation_id'=> $calc->id,
                        'transaction_reference'    => $txRef,
                        'claimed_by_type'          => 'beneficiary',
                        'amount_released'          => $total,
                        'payment_mode'             => 'cash',
                        'released_by'              => $officer?->id ?? $superadmin?->id ?? 1,
                        'status'                   => 'claimed',
                        'claimed_at'               => now()->subDays(rand(0, 2)),
                        'verification_notes'       => 'QR code verified. Identity confirmed.',
                        'ip_address'               => '127.0.0.1',
                        'device_info'              => 'Tablet / Chrome',
                    ]);
                }
            }
        }

        $this->command->info('✅ Prototype seeder complete!');
        $this->command->info('   → 10 households created across 8 barangays');
        $this->command->info('   → 7 compliant, 3 non-compliant');
        $this->command->info('   → 1 distribution event (Q1 2026, ongoing)');
        $this->command->info('   → 7 grant computations, 5 claims already released');
        $this->command->info('   → Compliance records for all 10 households (Period 1 2026)');
    }
}
