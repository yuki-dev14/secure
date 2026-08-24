<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;
use App\Models\ComplianceRecord;
use App\Models\NonComplianceRecord;

// 1. Remove old Q1/2026-P1 prototype seed compliance records if needed or keep them as historical P1
// 2. For period 2026-P4, create/sync authoritative ComplianceRecord entries for ALL beneficiaries

$period = '2026-P4';
$periodStart = '2026-07-01';
$periodEnd   = '2026-08-31';

$beneficiaries = Beneficiary::all();

foreach ($beneficiaries as $b) {
    // Find all confirmed non-compliance records for 2026-P4 for this beneficiary
    $ncRecords = NonComplianceRecord::where('beneficiary_id', $b->id)
        ->where('period', $period)
        ->where('status', 'confirmed')
        ->get();

    $hasEduNC    = $ncRecords->where('category', 'education')->count() > 0;
    $hasHealthNC = $ncRecords->where('category', 'health')->count() > 0;
    $isFullyCompliant = ! $hasEduNC && ! $hasHealthNC;

    ComplianceRecord::updateOrCreate(
        [
            'beneficiary_id' => $b->id,
            'period'         => $period,
        ],
        [
            'period_start'             => $periodStart,
            'period_end'               => $periodEnd,
            'verified_by'              => 1,
            'edu_attendance_compliant' => $hasEduNC ? false : true,
            'health_compliant'         => $hasHealthNC ? false : true,
            'fds_compliant'            => true,
            'is_fully_compliant'       => $isFullyCompliant,
            'remarks'                  => $isFullyCompliant
                ? 'Verified compliant for 2026-P4'
                : 'Flagged non-compliant in verification import for 2026-P4',
        ]
    );

    // Update beneficiary overall is_compliant status
    $b->update([
        'is_compliant'          => $isFullyCompliant,
        'last_compliance_check' => now(),
    ]);
}

echo "Successfully synced " . ComplianceRecord::where('period', $period)->count() . " compliance records for period {$period}.\n";
