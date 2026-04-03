<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SECURE 4Ps - Core System Tables
     *
     * Tables created in this migration:
     *  1. offices              - DSWD office/barangay locations in Lipa City
     *  2. beneficiaries        - Household representative accounts (pre-qualified via Listahanan)
     *  3. beneficiary_cards    - Physical QR-coded ID cards issued to beneficiaries
     *  4. family_members       - Members of each beneficiary household
     *  5. proxies              - Up to 2 authorized proxies per beneficiary
     *  6. beneficiary_documents- Submitted documentary requirements
     *  7. compliance_conditions- Per-period compliance records (education, health, FDS)
     *  8. distribution_events  - Scheduled cash grant distribution events
     *  9. cash_grant_calculations - Computed grant amounts per beneficiary per event
     * 10. cash_grant_distributions - Actual distribution/claiming transactions
     * 11. notifications        - In-system notifications for all roles
     * 12. audit_logs           - System-wide audit trail (superadmin only)
     */

    public function up(): void
    {
        // 1. Offices / Barangays in Lipa City, Batangas
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 60)->unique()->nullable();
            $table->enum('type', ['main', 'satellite', 'barangay'])->default('barangay');
            $table->text('address')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->default('Lipa City');
            $table->string('province')->default('Batangas');
            $table->string('contact_number', 20)->nullable();
            $table->string('officer_in_charge')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Add FK to users->offices (after offices table is created)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('office_id')->references('id')->on('offices')->nullOnDelete();
        });

        // 2. Beneficiaries (one per household, pre-qualified via Listahanan)
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->nullable(); // linked portal account
            $table->string('unique_id', 20)->unique();                   // e.g. 4PS-LPA-000001
            $table->string('listahanan_id')->unique()->nullable();       // NHTS-PR reference

            // Household Representative Info
            $table->string('household_head_name');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix', 10)->nullable();
            $table->date('birthdate');
            $table->enum('sex', ['male', 'female']);
            $table->enum('civil_status', ['single', 'married', 'widowed', 'separated', 'live-in'])->default('married');
            $table->string('contact_number', 20)->nullable();

            // Address
            $table->string('house_no')->nullable();
            $table->string('street')->nullable();
            $table->string('purok')->nullable();
            $table->string('barangay');
            $table->string('city')->default('Lipa City');
            $table->string('province')->default('Batangas');
            $table->string('zip_code', 10)->default('4217');

            // Office/Location
            $table->unsignedBigInteger('office_id')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'suspended', 'graduated', 'delisted'])->default('active');
            $table->date('enrollment_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->text('remarks')->nullable();

            // Card / Photo
            $table->string('photo_path')->nullable();
            $table->string('card_path')->nullable();    // Generated PDF path

            // Compliance summary (cached)
            $table->boolean('is_compliant')->default(false);
            $table->timestamp('last_compliance_check')->nullable();

            // Created by superadmin
            $table->unsignedBigInteger('created_by');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('office_id')->references('id')->on('offices')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });

        // 3. Beneficiary Cards (QR-coded physical ID cards)
        Schema::create('beneficiary_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->string('card_number', 30)->unique();      // Printed on card
            $table->string('qr_code_data')->unique();         // QR code payload (unique string)
            $table->string('qr_code_image_path')->nullable(); // QR PNG file path
            $table->string('default_password_hash');          // Hashed default password
            $table->string('default_password_plain')->nullable(); // Shown once then nulled
            $table->boolean('is_active')->default(true);
            $table->boolean('is_first_login')->default(true);
            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->text('deactivation_reason')->nullable();
            $table->timestamps();

            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
        });

        // 4. Family Members (all household members)
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix', 10)->nullable();
            $table->date('birthdate');
            $table->enum('sex', ['male', 'female']);
            $table->enum('relationship', [
                'spouse', 'child', 'parent', 'sibling', 'grandchild',
                'grandparent', 'in-law', 'other'
            ]);
            $table->boolean('is_household_head')->default(false);

            // Education (for children 3-18)
            $table->boolean('is_school_age')->default(false);
            $table->enum('education_level', [
                'daycare', 'preschool', 'elementary', 'junior_high', 'senior_high', 'not_applicable'
            ])->default('not_applicable');
            $table->string('school_name')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('lrn', 20)->nullable(); // Learner Reference Number
            $table->decimal('attendance_rate', 5, 2)->nullable(); // 0.00 - 100.00

            // Health (for children 0-5)
            $table->boolean('is_under_five')->default(false);
            $table->boolean('is_fully_immunized')->nullable();
            $table->date('last_weighed_at')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->enum('nutritional_status', ['normal', 'underweight', 'severely_underweight', 'overweight', 'not_applicable'])->default('not_applicable');

            // Pregnancy
            $table->boolean('is_pregnant')->default(false);
            $table->date('expected_delivery_date')->nullable();
            $table->boolean('prenatal_compliant')->nullable();
            $table->boolean('postnatal_compliant')->nullable();
            $table->boolean('professional_delivery')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
        });

        // 5. Proxies (max 2 per beneficiary)
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix', 10)->nullable();
            $table->date('birthdate');
            $table->enum('sex', ['male', 'female']);
            $table->enum('relationship', [
                'spouse', 'child', 'parent', 'sibling', 'grandchild',
                'grandparent', 'in-law', 'neighbor', 'other'
            ]);
            $table->string('contact_number', 20)->nullable();
            $table->string('address')->nullable();

            // Required proxy documents
            $table->string('valid_id_path')->nullable();
            $table->string('birth_certificate_path')->nullable();
            $table->string('valid_id_type')->nullable();
            $table->string('valid_id_number')->nullable();

            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // 6. Beneficiary Documents
        Schema::create('beneficiary_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->unsignedBigInteger('family_member_id')->nullable();
            $table->enum('document_type', [
                'birth_certificate',
                'valid_id',
                'school_id',
                'report_card',
                'health_record',
                'vaccination_booklet',
                'medical_certificate',
                'barangay_certificate',
                'photo_1x1',
                'certificate_of_indigency',
                'prenatal_record',
                'other',
            ]);
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_type', 20)->nullable(); // pdf, jpg, png
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->date('validity_date')->nullable(); // for IDs with expiry
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('family_member_id')->references('id')->on('family_members')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        // 7. Compliance Conditions (per period per beneficiary)
        Schema::create('compliance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->unsignedBigInteger('family_member_id')->nullable();
            $table->unsignedBigInteger('verified_by');       // compliance verifier user id

            $table->string('period');                        // e.g. "2025-Q1", "2025-S1"
            $table->date('period_start');
            $table->date('period_end');

            // Education Conditions
            $table->boolean('edu_enrolled')->nullable();
            $table->decimal('edu_attendance_rate', 5, 2)->nullable();
            $table->boolean('edu_attendance_compliant')->nullable(); // >= 85%

            // Health Conditions
            $table->boolean('health_immunization_complete')->nullable();
            $table->boolean('health_weight_monitored')->nullable();
            $table->date('health_last_checkup')->nullable();
            $table->boolean('health_compliant')->nullable();

            // Pregnancy Conditions
            $table->boolean('pregnancy_prenatal_compliant')->nullable();
            $table->boolean('pregnancy_postnatal_compliant')->nullable();
            $table->boolean('pregnancy_professional_delivery')->nullable();
            $table->boolean('pregnancy_compliant')->nullable();

            // Family Development Session
            $table->boolean('fds_attended')->nullable();
            $table->date('fds_date')->nullable();
            $table->string('fds_venue')->nullable();
            $table->boolean('fds_compliant')->nullable();

            // Overall
            $table->boolean('is_fully_compliant')->default(false);
            $table->text('remarks')->nullable();
            $table->text('non_compliance_reasons')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['beneficiary_id', 'family_member_id', 'period']);
            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('family_member_id')->references('id')->on('family_members')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users');
        });

        // 8. Distribution Events (Scheduled claiming events)
        Schema::create('distribution_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('period');                         // e.g. "2025-Q1 (Jan-Feb)"
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('months_covered')->default(2);   // Always 2 months
            $table->date('distribution_date_start');
            $table->date('distribution_date_end');
            $table->time('distribution_time_start')->nullable();
            $table->time('distribution_time_end')->nullable();
            $table->unsignedBigInteger('office_id')->nullable();
            $table->string('venue');
            $table->string('venue_address')->nullable();
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('office_id')->references('id')->on('offices')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });

        // 9. Cash Grant Calculations (computed amounts per beneficiary per event)
        Schema::create('cash_grant_calculations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->unsignedBigInteger('distribution_event_id');
            $table->integer('months_covered')->default(2);

            // Health Grant: ₱750/month per household
            $table->boolean('health_grant_eligible')->default(false);
            $table->decimal('health_grant_amount', 10, 2)->default(0);

            // Education Grants (max 3 children)
            $table->integer('elementary_children_count')->default(0);
            $table->decimal('elementary_grant_amount', 10, 2)->default(0); // 300/child/month
            $table->integer('junior_high_children_count')->default(0);
            $table->decimal('junior_high_grant_amount', 10, 2)->default(0); // 500/child/month
            $table->integer('senior_high_children_count')->default(0);
            $table->decimal('senior_high_grant_amount', 10, 2)->default(0); // 700/child/month
            $table->decimal('education_grant_total', 10, 2)->default(0);

            // Rice Subsidy: ₱600/month per household
            $table->boolean('rice_subsidy_eligible')->default(false);
            $table->decimal('rice_subsidy_amount', 10, 2)->default(0);

            // Totals
            $table->decimal('total_grant_amount', 10, 2)->default(0);
            $table->enum('compute_status', ['pending', 'computed', 'approved', 'adjusted'])->default('pending');
            $table->unsignedBigInteger('computed_by')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->text('computation_notes')->nullable();
            $table->timestamps();

            $table->unique(['beneficiary_id', 'distribution_event_id']);
            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('distribution_event_id')->references('id')->on('distribution_events')->cascadeOnDelete();
            $table->foreign('computed_by')->references('id')->on('users')->nullOnDelete();
        });

        // 10. Cash Grant Distributions (actual claiming transaction)
        Schema::create('cash_grant_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->unsignedBigInteger('distribution_event_id');
            $table->unsignedBigInteger('cash_grant_calculation_id')->nullable();
            $table->string('transaction_reference', 40)->unique();

            // Claimer info
            $table->enum('claimed_by_type', ['beneficiary', 'proxy'])->default('beneficiary');
            $table->unsignedBigInteger('proxy_id')->nullable();

            // Amount
            $table->decimal('amount_released', 10, 2);
            $table->enum('payment_mode', ['cash', 'check', 'ewallet'])->default('cash');

            // Verification
            $table->unsignedBigInteger('released_by');      // field officer
            $table->enum('status', ['pending', 'claimed', 'unclaimed', 'returned'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->timestamp('claimed_at')->nullable();

            // Fraud prevention
            $table->string('claimer_photo_path')->nullable(); // Photo taken during claiming
            $table->string('qr_scan_event_id')->nullable();
            $table->string('device_info')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            $table->foreign('distribution_event_id')->references('id')->on('distribution_events')->cascadeOnDelete();
            $table->foreign('cash_grant_calculation_id')->references('id')->on('cash_grant_calculations')->nullOnDelete();
            $table->foreign('proxy_id')->references('id')->on('proxies')->nullOnDelete();
            $table->foreign('released_by')->references('id')->on('users');
        });

        // 11. Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');          // polymorphic: user or beneficiary
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // 12. Audit Logs (superadmin exclusive)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable();          // 'staff' or 'beneficiary'
            $table->string('event');                          // created, updated, deleted, login, etc.
            $table->string('auditable_type')->nullable();     // Model class name
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('tags')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('cash_grant_distributions');
        Schema::dropIfExists('cash_grant_calculations');
        Schema::dropIfExists('distribution_events');
        Schema::dropIfExists('compliance_records');
        Schema::dropIfExists('beneficiary_documents');
        Schema::dropIfExists('proxies');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('beneficiary_cards');
        Schema::dropIfExists('beneficiaries');
        Schema::dropIfExists('offices');
    }
};
