<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Quarterly distribution model:
     * One quarter = 3 months (Q1: Jan-Mar, Q2: Apr-Jun, Q3: Jul-Sep, Q4: Oct-Dec)
     * Update the DB-level default for months_covered from 2 to 3.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE distribution_events ALTER COLUMN months_covered SET DEFAULT 3");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE distribution_events ALTER COLUMN months_covered SET DEFAULT 2");
    }
};
