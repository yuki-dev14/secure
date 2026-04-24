<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetForDemo extends Command
{
    protected $signature   = 'demo:reset {--seed : Also seed fresh demo beneficiaries}';
    protected $description = 'Clear all operational data (beneficiaries, events, audits) but keep staff accounts, then seed demo data.';

    // 5 real Lipa City barangays, 2 households each = 10 beneficiaries
    private array $demoHouseholds = [
        [
            'barangay' => 'Anilao',
            'members'  => [
                ['first_name'=>'Maria',   'last_name'=>'Santos',     'sex'=>'female', 'birthdate'=>'1985-03-12'],
                ['first_name'=>'Jose',    'last_name'=>'Reyes',      'sex'=>'male',   'birthdate'=>'1990-07-25'],
            ],
        ],
        [
            'barangay' => 'Balintawak',
            'members'  => [
                ['first_name'=>'Cynthia', 'last_name'=>'Dela Cruz',  'sex'=>'female', 'birthdate'=>'1978-11-05'],
                ['first_name'=>'Roberto', 'last_name'=>'Garcia',     'sex'=>'male',   'birthdate'=>'1982-04-18'],
            ],
        ],
        [
            'barangay' => 'Banay Banay',
            'members'  => [
                ['first_name'=>'Lourdes', 'last_name'=>'Fernandez',  'sex'=>'female', 'birthdate'=>'1995-08-30'],
                ['first_name'=>'Eduardo', 'last_name'=>'Mendoza',    'sex'=>'male',   'birthdate'=>'1988-01-14'],
            ],
        ],
        [
            'barangay' => 'Bukal',
            'members'  => [
                ['first_name'=>'Teresa',  'last_name'=>'Lopez',      'sex'=>'female', 'birthdate'=>'1980-06-22'],
                ['first_name'=>'Antonio', 'last_name'=>'Martinez',   'sex'=>'male',   'birthdate'=>'1976-09-03'],
            ],
        ],
        [
            'barangay' => 'Dagatan',
            'members'  => [
                ['first_name'=>'Rosario', 'last_name'=>'Villanueva', 'sex'=>'female', 'birthdate'=>'1992-12-17'],
                ['first_name'=>'Alfredo', 'last_name'=>'Ramos',      'sex'=>'male',   'birthdate'=>'1984-05-09'],
            ],
        ],
    ];

    public function handle(): int
    {
        $this->warn('⚠  This will delete ALL beneficiaries, events, audits and related data.');
        $this->warn('   Staff accounts (superadmin, admin, verifier, field_officer) will be KEPT.');

        if (! $this->confirm('Are you sure you want to proceed?', false)) {
            $this->info('Aborted.');
            return 0;
        }

        $this->info('');
        $this->info('🧹 Clearing operational data…');

        DB::statement('SET session_replication_role = replica'); // disable FK checks in Postgres

        $tables = [
            'audit_logs',
            'notifications',
            'cash_grant_distributions',
            'cash_grant_calculations',
            'compliance_records',
            'beneficiary_documents',
            'proxies',
            'family_members',
            'beneficiary_cards',
            'distribution_events',
        ];

        foreach ($tables as $table) {
            DB::table($table)->delete();
            $this->line("  ✓ Cleared: {$table}");
        }

        // Delete beneficiary user accounts (portal users with role=beneficiary)
        DB::table('users')->where('role', 'beneficiary')->delete();
        $this->line('  ✓ Cleared: beneficiary portal users');

        // Delete beneficiaries themselves
        DB::table('beneficiaries')->delete();
        $this->line('  ✓ Cleared: beneficiaries');

        DB::statement('SET session_replication_role = DEFAULT'); // re-enable FK checks

        $this->info('');
        $this->info('✅ Operational data cleared.');
        $this->info('   Staff accounts preserved:');

        User::whereNotIn('role', ['beneficiary'])->each(function ($u) {
            $this->line("     • [{$u->role}] {$u->name} <{$u->email}>");
        });

        // ── Seed demo beneficiaries ──────────────────────────────────────────
        $this->info('');
        $this->info('🌱 Seeding demo beneficiaries (2 households × 5 barangays = 10 total)…');

        $counter = 1;
        foreach ($this->demoHouseholds as $barangayGroup) {
            foreach ($barangayGroup['members'] as $member) {
                $uid = Beneficiary::generateUniqueId();

                Beneficiary::create([
                    'unique_id'            => $uid,
                    'household_head_name'  => "{$member['first_name']} {$member['last_name']}",
                    'first_name'           => $member['first_name'],
                    'last_name'            => $member['last_name'],
                    'middle_name'          => null,
                    'sex'                  => $member['sex'],
                    'civil_status'         => 'single',
                    'birthdate'            => $member['birthdate'],
                    'barangay'             => $barangayGroup['barangay'],
                    'city'                 => 'Lipa City',
                    'province'             => 'Batangas',
                    'status'               => 'inactive',   // ← inactive so demo can activate them
                    'is_compliant'         => false,
                ]);

                $this->line("  ✓ [{$uid}] {$member['first_name']} {$member['last_name']} — {$barangayGroup['barangay']} (inactive)");
                $counter++;
            }
        }

        $this->info('');
        $this->info('🎉 Done! System is ready for your mock presentation.');
        $this->info('   10 inactive beneficiaries across 5 barangays are ready to activate.');

        return 0;
    }

    private function generateUID(int $n): string
    {
        return sprintf('4PS-LPA-%06d', $n);
    }
}
