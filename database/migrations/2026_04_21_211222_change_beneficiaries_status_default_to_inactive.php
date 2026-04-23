<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * LISTAHAN Registration Workflow:
     * All beneficiaries imported/added manually default to 'inactive'.
     * They become 'active' only after the admin approves their application
     * and verifies their physical documentary requirements at the city hall.
     *
     * Note: PostgreSQL does not support ALTER COLUMN TYPE with CHECK constraints
     * via Blueprint->change() for enum-simulated columns. We use raw SQL to
     * only modify the column DEFAULT, leaving the existing CHECK constraint intact.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE beneficiaries ALTER COLUMN status SET DEFAULT 'inactive'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE beneficiaries ALTER COLUMN status SET DEFAULT 'active'");
    }
};
