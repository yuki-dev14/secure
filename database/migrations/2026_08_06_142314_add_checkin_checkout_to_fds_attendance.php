<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entry/Exit QR scanning for FDS attendance.
     *
     * - checked_in_at / checked_out_at: timestamps for entry and exit scans
     * - is_complete: only true when both scans are recorded
     * - Also adds 'assigned_barangay' to users table for barangay assistants
     * - Adds 'reported_to_superadmin' flag for Admin4Ps reporting flow
     */
    public function up(): void
    {
        Schema::table('fds_attendance', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('scanned_at');
            $table->string('checked_in_device')->nullable()->after('checked_in_at');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_device');
            $table->string('checked_out_device')->nullable()->after('checked_out_at');
            $table->boolean('is_complete')->default(false)->after('checked_out_device');
        });

        // Backfill existing records: treat old scanned_at as check-in, mark incomplete
        \DB::table('fds_attendance')
            ->whereNotNull('scanned_at')
            ->whereNull('checked_in_at')
            ->update([
                'checked_in_at' => \DB::raw('scanned_at'),
                'checked_in_device' => \DB::raw('scanned_device'),
            ]);

        // Add assigned_barangay to users for barangay assistant role
        Schema::table('users', function (Blueprint $table) {
            $table->string('assigned_barangay')->nullable()->after('role');
        });

        // Add reported flag to track Admin4Ps → Superadmin reporting
        Schema::table('fds_attendance', function (Blueprint $table) {
            $table->boolean('is_reported')->default(false)->after('is_complete');
            $table->timestamp('reported_at')->nullable()->after('is_reported');
            $table->unsignedBigInteger('reported_by')->nullable()->after('reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('fds_attendance', function (Blueprint $table) {
            $table->dropColumn([
                'checked_in_at', 'checked_in_device',
                'checked_out_at', 'checked_out_device',
                'is_complete', 'is_reported', 'reported_at', 'reported_by',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('assigned_barangay');
        });
    }
};
