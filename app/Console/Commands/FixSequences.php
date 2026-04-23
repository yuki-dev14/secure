<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    protected $signature   = 'demo:fix-sequences';
    protected $description = 'Reset all PostgreSQL sequences to the correct value after manual data changes';

    public function handle(): void
    {
        $tables = [
            'users'                    => 'users_id_seq',
            'beneficiaries'            => 'beneficiaries_id_seq',
            'family_members'           => 'family_members_id_seq',
            'beneficiary_cards'        => 'beneficiary_cards_id_seq',
            'beneficiary_documents'    => 'beneficiary_documents_id_seq',
            'compliance_records'       => 'compliance_records_id_seq',
            'cash_grant_calculations'  => 'cash_grant_calculations_id_seq',
            'cash_grant_distributions' => 'cash_grant_distributions_id_seq',
            'proxies'                  => 'proxies_id_seq',
        ];

        $results = [];

        foreach ($tables as $table => $seq) {
            $maxId = DB::table($table)->max('id') ?? 0;
            $next  = $maxId + 1;
            try {
                DB::statement("ALTER SEQUENCE {$seq} RESTART WITH {$next}");
                $results[] = [$table, $maxId, $next, '✅'];
            } catch (\Throwable $e) {
                $results[] = [$table, $maxId, $next, '❌ ' . $e->getMessage()];
            }
        }

        $this->table(['Table', 'Max ID', 'Next ID', 'Status'], $results);
        $this->info('Sequences fixed. You can now register new beneficiaries.');
    }
}
