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

        // Barangay Assistants for all Lipa City Barangays
        $barangaysList = [
            'Anilao'               => ['username' => 'ba_anilao',         'email' => 'ba.anilao@secure4ps.dswd.gov.ph',         'name' => 'Barangay Assistant — Anilao'],
            'Antipolo del Norte'   => ['username' => 'ba_antipolo_norte', 'email' => 'ba.antipolo@secure4ps.dswd.gov.ph',       'name' => 'Barangay Assistant — Antipolo del Norte'],
            'Bagong Pook'          => ['username' => 'ba_bagongpook',     'email' => 'ba.bagongpook@secure4ps.dswd.gov.ph',     'name' => 'Barangay Assistant — Bagong Pook'],
            'Balintawak'           => ['username' => 'ba_balintawak',     'email' => 'ba.balintawak@secure4ps.dswd.gov.ph',     'name' => 'Barangay Assistant — Balintawak'],
            'Banaybanay'           => ['username' => 'ba_banaybanay',     'email' => 'ba.banaybanay@secure4ps.dswd.gov.ph',     'name' => 'Barangay Assistant — Banaybanay'],
            'Bolbok'               => ['username' => 'ba_bolbok',         'email' => 'ba.bolbok@secure4ps.dswd.gov.ph',         'name' => 'Barangay Assistant — Bolbok'],
            'Dagatan'              => ['username' => 'ba_dagatan',        'email' => 'ba.dagatan@secure4ps.dswd.gov.ph',        'name' => 'Barangay Assistant — Dagatan'],
            'Inosloban'            => ['username' => 'ba_inosloban',      'email' => 'ba.inosloban@secure4ps.dswd.gov.ph',      'name' => 'Barangay Assistant — Inosloban'],
            'Kayumanggi'           => ['username' => 'ba_kayumanggi',     'email' => 'ba.kayumanggi@secure4ps.dswd.gov.ph',     'name' => 'Barangay Assistant — Kayumanggi'],
            'Lipa City Poblacion'  => ['username' => 'ba_poblacion',      'email' => 'ba.poblacion@secure4ps.dswd.gov.ph',      'name' => 'Barangay Assistant — Poblacion'],
            'Lodlod'               => ['username' => 'ba_lodlod',         'email' => 'ba.lodlod@secure4ps.dswd.gov.ph',         'name' => 'Barangay Assistant — Lodlod'],
            'Marawoy'              => ['username' => 'ba_marawoy',        'email' => 'ba.marawoy@secure4ps.dswd.gov.ph',        'name' => 'Barangay Assistant — Marawoy'],
            'Mataas na Lupa'       => ['username' => 'ba_mataas_lupa',    'email' => 'ba.mataaslupa@secure4ps.dswd.gov.ph',    'name' => 'Barangay Assistant — Mataas na Lupa'],
            'Pinagkawitan'         => ['username' => 'ba_pinagkawitan',   'email' => 'ba.pinagkawitan@secure4ps.dswd.gov.ph',   'name' => 'Barangay Assistant — Pinagkawitan'],
            'Sabang'               => ['username' => 'ba_sabang',         'email' => 'ba.sabang@secure4ps.dswd.gov.ph',         'name' => 'Barangay Assistant — Sabang'],
            'Sico'                 => ['username' => 'ba_sico',           'email' => 'ba.sico@secure4ps.dswd.gov.ph',           'name' => 'Barangay Assistant — Sico'],
            'Tambo'                => ['username' => 'ba_tambo',          'email' => 'ba.tambo@secure4ps.dswd.gov.ph',          'name' => 'Barangay Assistant — Tambo'],
            'Tibig'                => ['username' => 'ba_tibig',          'email' => 'ba.tibig@secure4ps.dswd.gov.ph',          'name' => 'Barangay Assistant — Tibig'],
        ];

        foreach ($barangaysList as $bgyName => $info) {
            $existingUser = User::where('email', $info['email'])->first();
            $empId = $existingUser?->employee_id ?? ('EMP-BA-' . strtoupper(substr(md5($info['username']), 0, 6)));

            User::updateOrCreate(
                ['email' => $info['email']],
                [
                    'name'              => $info['name'],
                    'username'          => $info['username'],
                    'password'          => Hash::make('Officer@1234!'),
                    'role'              => 'barangay_assistant',
                    'assigned_barangay' => $bgyName,
                    'office_id'         => $mainOffice?->id,
                    'employee_id'       => $empId,
                    'position'          => "Barangay FDS Assistant ({$bgyName})",
                    'is_active'         => true,
                ]
            );
        }
    }
}
