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

        // Admin 4Ps (FDS / Attendance)
        User::create([
            'name'        => 'Lipa City Admin 4Ps',
            'email'       => 'admin4ps@secure4ps.dswd.gov.ph',
            'username'    => 'admin4ps',
            'password'    => Hash::make('Admin@1234!'),
            'role'        => 'admin_4ps',
            'office_id'   => $mainOffice?->id,
            'employee_id' => 'EMP-4PS-001',
            'position'    => '4Ps Program Officer',
            'is_active'   => true,
        ]);

        // Admin SWA (Health & Education)
        User::create([
            'name'        => 'Maria Santos',
            'email'       => 'adminswa@secure4ps.dswd.gov.ph',
            'username'    => 'adminswa',
            'password'    => Hash::make('Admin@1234!'),
            'role'        => 'admin_swa',
            'office_id'   => $mainOffice?->id,
            'employee_id' => 'EMP-SWA-001',
            'position'    => 'SWA Compliance Officer',
            'is_active'   => true,
        ]);

        // Barangay Assistant (FDS Officer)
        User::create([
            'name'        => 'Juan dela Cruz',
            'email'       => 'barangay@secure4ps.dswd.gov.ph',
            'username'    => 'barangay1',
            'password'    => Hash::make('Officer@1234!'),
            'role'        => 'barangay_assistant',
            'office_id'   => $mainOffice?->id,
            'employee_id' => 'EMP-BA-001',
            'position'    => 'Barangay Assistant (FDS)',
            'is_active'   => true,
        ]);
    }
}
