<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClearBeneficiaryData extends Command
{
    protected $signature   = 'demo:clear-beneficiaries';
    protected $description = 'Wipe all beneficiary data for a clean demo (keeps staff accounts)';

    public function handle(): void
    {
        if (!$this->confirm('⚠ This will DELETE ALL beneficiary records permanently. Continue?')) {
            $this->info('Cancelled.');
            return;
        }

        $this->info('Clearing beneficiary data...');

        DB::statement('SET session_replication_role = replica');

        // 1. Child/dependent tables first
        $count = [];
        $count['distributions']   = DB::table('cash_grant_distributions')->delete();
        $count['calculations']    = DB::table('cash_grant_calculations')->delete();
        $count['compliance']      = DB::table('compliance_records')->delete();
        $count['documents']       = DB::table('beneficiary_documents')->delete();
        $count['cards']           = DB::table('beneficiary_cards')->delete();
        $count['family_members']  = DB::table('family_members')->delete();
        $count['proxies']         = DB::table('proxies')->delete();

        // 2. Clear notifications for beneficiary users
        $beneficiaryUserIds = DB::table('beneficiaries')->pluck('user_id')->filter();
        $count['notifications'] = DB::table('notifications')
            ->whereIn('notifiable_id', $beneficiaryUserIds)
            ->delete();

        // 3. Delete beneficiary user accounts
        $count['users'] = DB::table('users')->where('role', 'beneficiary')->delete();

        // 4. Delete beneficiaries
        $count['beneficiaries'] = DB::table('beneficiaries')->delete();

        DB::statement('SET session_replication_role = DEFAULT');

        // 5. Reset PostgreSQL sequences to 1
        $sequences = [
            'beneficiaries'             => 'beneficiaries_id_seq',
            'family_members'            => 'family_members_id_seq',
            'beneficiary_cards'         => 'beneficiary_cards_id_seq',
            'beneficiary_documents'     => 'beneficiary_documents_id_seq',
            'compliance_records'        => 'compliance_records_id_seq',
            'cash_grant_calculations'   => 'cash_grant_calculations_id_seq',
            'cash_grant_distributions'  => 'cash_grant_distributions_id_seq',
            'proxies'                   => 'proxies_id_seq',
            'users'                     => 'users_id_seq',
        ];

        foreach ($sequences as $table => $seq) {
            try {
                DB::statement("ALTER SEQUENCE {$seq} RESTART WITH 1");
            } catch (\Throwable $e) {
                $this->warn("Could not reset sequence {$seq}: " . $e->getMessage());
            }
        }

        // 6. Clean up uploaded files
        try {
            $dirs = ['documents', 'photos', 'cards', 'qrcodes'];
            foreach ($dirs as $dir) {
                if (Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->deleteDirectory($dir);
                    Storage::disk('public')->makeDirectory($dir);
                }
            }
            $this->info('Uploaded files cleared.');
        } catch (\Throwable $e) {
            $this->warn('File cleanup skipped: ' . $e->getMessage());
        }

        // 7. Clear queued jobs
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();

        $this->newLine();
        $this->info('✅ Done! Summary:');
        $this->table(['Table', 'Rows Deleted'], collect($count)->map(fn($v, $k) => [$k, $v])->values()->toArray());
        $this->info('All sequences reset to 1. System is ready for a fresh demo.');
    }
}
