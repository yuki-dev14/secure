<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SECURE 4Ps — Role Restructuring & Compliance Tables
     *
     * New tables:
     *  1. non_compliance_records  — Tracks beneficiaries/family members flagged as
     *                               non-compliant by School Reps (education) or Midwives (health).
     *                               Admin SWA processes these to zero out the relevant grant component.
     *
     *  2. fds_attendance          — QR-scanned FDS session attendance logged by
     *                               Barangay Assistants. Admin 4Ps aggregates these into
     *                               bimonthly compliance reports for Superadmin.
     */

    public function up(): void
    {
        // ── Non-Compliance Records (Admin SWA intake) ─────────────────────────
        // School Representatives submit education non-compliance.
        // Midwives submit health non-compliance.
        // Default assumption: all beneficiaries are compliant unless flagged here.
        Schema::create('non_compliance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->unsignedBigInteger('family_member_id')->nullable(); // specific child / pregnant woman

            // Which condition category was not met
            $table->enum('category', ['education', 'health']);

            // Who reported it
            $table->enum('source', ['school_rep', 'midwife']);
            $table->string('reporter_name')->nullable();       // Name of school rep / midwife
            $table->string('reporter_institution')->nullable(); // School name / health center

            // Specific non-compliance reason
            $table->string('reason');   // e.g. "Attendance below 85%", "Missed deworming", etc.
            $table->text('details')->nullable();

            // Bimonthly period this applies to
            $table->string('period', 20);       // e.g. "2026-P1" (Jan-Feb), "2026-P2" (Mar-Apr)
            $table->date('period_start');
            $table->date('period_end');

            // Grant impact
            $table->enum('grant_affected', [
                'health_grant',
                'education_elementary',
                'education_junior_high',
                'education_senior_high',
                'rice_subsidy',
            ]);

            // Processing status
            $table->enum('status', ['pending', 'confirmed', 'dismissed'])->default('pending');
            $table->unsignedBigInteger('processed_by')->nullable(); // Admin SWA user id
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_notes')->nullable();

            // Import tracking (for Google Forms / Excel uploads)
            $table->string('import_batch_id')->nullable();  // Groups rows from a single upload

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('family_member_id')->references('id')->on('family_members')->nullOnDelete();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();

            // Prevent duplicate flags for the same beneficiary+member+category+period
            $table->unique(
                ['beneficiary_id', 'family_member_id', 'category', 'period'],
                'nc_beneficiary_member_category_period_unique'
            );
        });

        // ── FDS Attendance (Barangay Assistant QR scanning) ───────────────────
        Schema::create('fds_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');

            // Session info
            $table->string('session_title')->nullable();     // e.g. "FDS Session — Anilao Feb 2026"
            $table->string('period', 20);                    // e.g. "2026-P1"
            $table->date('period_start');
            $table->date('period_end');
            $table->date('session_date');
            $table->string('venue')->nullable();

            // QR scan verification
            $table->boolean('qr_verified')->default(false);
            $table->timestamp('scanned_at')->nullable();
            $table->string('scanned_device')->nullable();

            // Who recorded it
            $table->unsignedBigInteger('recorded_by');       // Barangay Assistant user id
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users');

            // One attendance record per beneficiary per session date
            $table->unique(['beneficiary_id', 'session_date'], 'fds_beneficiary_session_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fds_attendance');
        Schema::dropIfExists('non_compliance_records');
    }
};
