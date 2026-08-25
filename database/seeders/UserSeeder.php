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
        User::updateOrCreate(
            ['email' => 'superadmin@secure4ps.dswd.gov.ph'],
            [
                'name'        => 'System Administrator',
                'username'    => 'superadmin',
                'password'    => Hash::make('Admin@1234!'),
                'role'        => 'superadmin',
                'office_id'   => $mainOffice?->id,
                'employee_id' => 'EMP-SA-001',
                'position'    => 'System Administrator',
                'is_active'   => true,
            ]
        );

        // Admin 4Ps (FDS / Attendance)
        User::updateOrCreate(
            ['email' => 'admin4ps@secure4ps.dswd.gov.ph'],
            [
                'name'        => 'Lipa City Admin 4Ps',
                'username'    => 'admin4ps',
                'password'    => Hash::make('Admin@1234!'),
                'role'        => 'admin_4ps',
                'office_id'   => $mainOffice?->id,
                'employee_id' => 'EMP-4PS-001',
                'position'    => '4Ps Program Officer',
                'is_active'   => true,
            ]
        );

        // Admin SWA (Health & Education)
        User::updateOrCreate(
            ['email' => 'adminswa@secure4ps.dswd.gov.ph'],
            [
                'name'        => 'Maria Santos',
                'username'    => 'adminswa',
                'password'    => Hash::make('Admin@1234!'),
                'role'        => 'admin_swa',
                'office_id'   => $mainOffice?->id,
                'employee_id' => 'EMP-SWA-001',
                'position'    => 'SWA Compliance Officer',
                'is_active'   => true,
            ]
        );

        // Default Barangay Assistant (Tibig Demo)
        User::updateOrCreate(
            ['email' => 'barangay@secure4ps.dswd.gov.ph'],
            [
                'name'              => 'Juan dela Cruz',
                'username'          => 'barangay1',
                'password'          => Hash::make('Officer@1234!'),
                'role'              => 'barangay_assistant',
                'assigned_barangay' => 'Tibig',
                'office_id'         => $mainOffice?->id,
                'employee_id'       => 'EMP-BA-001',
                'position'          => 'Barangay Assistant (Tibig)',
                'is_active'         => true,
            ]
        );

        // Barangay Assistants for all 10 Barangays
        $barangaysList = [
            'Anilao'               => ['username' => 'ba_anilao',       'email' => 'ba.anilao@secure4ps.dswd.gov.ph',       'name' => 'Barangay Assistant — Anilao'],
            'Bagong Pook'          => ['username' => 'ba_bagongpook',   'email' => 'ba.bagongpook@secure4ps.dswd.gov.ph',   'name' => 'Barangay Assistant — Bagong Pook'],
            'Balintawak'           => ['username' => 'ba_balintawak',   'email' => 'ba.balintawak@secure4ps.dswd.gov.ph',   'name' => 'Barangay Assistant — Balintawak'],
            'Dagatan'              => ['username' => 'ba_dagatan',      'email' => 'ba.dagatan@secure4ps.dswd.gov.ph',      'name' => 'Barangay Assistant — Dagatan'],
            'Lipa City Poblacion'  => ['username' => 'ba_poblacion',    'email' => 'ba.poblacion@secure4ps.dswd.gov.ph',    'name' => 'Barangay Assistant — Poblacion'],
            'Marawoy'              => ['username' => 'ba_marawoy',      'email' => 'ba.marawoy@secure4ps.dswd.gov.ph',      'name' => 'Barangay Assistant — Marawoy'],
            'Pinagkawitan'         => ['username' => 'ba_pinagkawitan', 'email' => 'ba.pinagkawitan@secure4ps.dswd.gov.ph', 'name' => 'Barangay Assistant — Pinagkawitan'],
            'Sico'                 => ['username' => 'ba_sico',         'email' => 'ba.sico@secure4ps.dswd.gov.ph',         'name' => 'Barangay Assistant — Sico'],
            'Tambo'                => ['username' => 'ba_tambo',        'email' => 'ba.tambo@secure4ps.dswd.gov.ph',        'name' => 'Barangay Assistant — Tambo'],
            'Tibig'                => ['username' => 'ba_tibig',        'email' => 'ba.tibig@secure4ps.dswd.gov.ph',        'name' => 'Barangay Assistant — Tibig'],
        ];

        $empIdx = 2;
        foreach ($barangaysList as $bgyName => $info) {
            User::updateOrCreate(
                ['email' => $info['email']],
                [
                    'name'              => $info['name'],
                    'username'          => $info['username'],
                    'password'          => Hash::make('Officer@1234!'),
                    'role'              => 'barangay_assistant',
                    'assigned_barangay' => $bgyName,
                    'office_id'         => $mainOffice?->id,
                    'employee_id'       => sprintf('EMP-BA-%03d', $empIdx++),
                    'position'          => "Barangay FDS Assistant ({$bgyName})",
                    'is_active'         => true,
                ]
            );
        }
    }
}
