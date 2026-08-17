<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compliance Verification Batches — tracks the Excel files sent to
     * School Representatives (education) and Midwives (health) for
     * beneficiary compliance verification, and their import status.
     */
    public function up(): void
    {
        Schema::create('compliance_verification_batches', function (Blueprint $table) {
            $table->id();

            // Period and category
            $table->string('period', 20);           // e.g. "2026-P4"
            $table->enum('category', ['education', 'health']);

            // Recipient details
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();

            // Counts
            $table->unsignedInteger('beneficiary_count')->default(0);
            $table->unsignedInteger('non_compliant_count')->nullable();

            // Who sent it
            $table->unsignedBigInteger('sent_by');
            $table->timestamp('sent_at')->nullable();

            // Import tracking
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->timestamp('imported_at')->nullable();

            // Status
            $table->enum('status', ['sent', 'imported'])->default('sent');

            // File storage
            $table->string('file_path')->nullable();

            $table->timestamps();

            $table->foreign('sent_by')->references('id')->on('users');
            $table->foreign('imported_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_verification_batches');
    }
};
