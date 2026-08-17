<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update the users.role check constraint to include the new RA 11310 roles.
     */
    public function up(): void
    {
        // Drop the existing constraint
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        // Add the updated constraint with new roles
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('superadmin', 'admin', 'admin_4ps', 'admin_swa', 'barangay_assistant', 'beneficiary'))");
    }

    /**
     * Revert to the original constraint.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('superadmin', 'admin', 'compliance_verifier', 'field_officer', 'beneficiary'))");
    }
};
