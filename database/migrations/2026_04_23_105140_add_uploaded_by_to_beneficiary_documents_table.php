<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('beneficiary_documents', function (Blueprint $table) {
            // Who uploaded this document (staff user id)
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('validity_date');
            // Source: 'admin' = physically submitted to DSWD office and uploaded by staff
            //         'beneficiary' = self-uploaded via portal
            $table->enum('source', ['admin', 'beneficiary'])->default('beneficiary')->after('uploaded_by');

            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beneficiary_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['uploaded_by', 'source']);
        });
    }
};
