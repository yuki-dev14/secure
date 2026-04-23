<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distribution Eligibility:
     * A beneficiary is only eligible to receive a cash grant for a quarter
     * if a ComplianceRecord exists for that quarter (period) AND is_fully_compliant = true.
     *
     * is_eligible     — whether the beneficiary qualifies for this event's grant
     * ineligibility_reason — human-readable reason when is_eligible = false
     */
    public function up(): void
    {
        Schema::table('cash_grant_calculations', function (Blueprint $table) {
            $table->boolean('is_eligible')->default(false)->after('compute_status');
            $table->string('ineligibility_reason')->nullable()->after('is_eligible');
        });
    }

    public function down(): void
    {
        Schema::table('cash_grant_calculations', function (Blueprint $table) {
            $table->dropColumn(['is_eligible', 'ineligibility_reason']);
        });
    }
};
