<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $mainOffice = Office::where('code', 'LIPA-MAIN')->first();

        // Superadmin
        User::create([
            'name'        => 'System Administrator',
            'email'       => 'superadmin@secure4ps.dswd.gov.ph',
            'username'    => 'superadmin',
            'password'    => Hash::make('Admin@1234!'),
            'role'        => 'superadmin',
            'office_id'   => $mainOffice?->id,
            'employee_id' => 'EMP-SA-001',
            'position'    => 'System Administrator',
            'is_active'   => true,
        ]);

        // Admin
        User::create([
            'name'        => 'Lipa City Admin',
            'email'       => 'admin@secure4ps.dswd.gov.ph',
            'username'    => 'lipaadmin',
            'password'    => Hash::make('Admin@1234!'),
            'role'        => 'admin',
            'office_id'   => $mainOffice?->id,
            'employee_id' => 'EMP-ADM-001',
            'position'    => 'Program Manager',
            'is_active'   => true,
        ]);

        // Compliance Verifier
        User::create([
            'name'        => 'Maria Santos',
            'email'       => 'verifier@secure4ps.dswd.gov.ph',
            'username'    => 'verifier1',
            'password'    => Hash::make('Verify@1234!'),
            'role'        => 'compliance_verifier',
            'office_id'   => $mainOffice?->id,
            'employee_id' => 'EMP-CV-001',
            'position'    => 'Compliance Verifier',
            'is_active'   => true,
        ]);

        // Field Officer
        User::create([
            'name'        => 'Juan dela Cruz',
            'email'       => 'officer@secure4ps.dswd.gov.ph',
            'username'    => 'officer1',
            'password'    => Hash::make('Officer@1234!'),
            'role'        => 'field_officer',
            'office_id'   => $mainOffice?->id,
            'employee_id' => 'EMP-FO-001',
            'position'    => 'Field Officer',
            'is_active'   => true,
        ]);
    }
}
