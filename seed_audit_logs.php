<?php
// Seed sample audit log entries for testing the viewer

use App\Models\AuditLog;
use App\Models\User;

$users = User::all();
$adminId  = $users->where('role', 'admin')->first()?->id;
$officerId = $users->where('role', 'field_officer')->first()?->id;
$superadminId = $users->where('role', 'superadmin')->first()?->id;

$entries = [
    ['event' => 'login',                    'user_id' => $superadminId, 'user_type' => 'superadmin', 'description' => 'Superadmin logged in successfully', 'ip_address' => '192.168.1.100'],
    ['event' => 'login',                    'user_id' => $adminId,      'user_type' => 'admin',      'description' => 'Admin logged in successfully',      'ip_address' => '192.168.1.101'],
    ['event' => 'login_failed',             'user_id' => null,          'user_type' => 'guest',      'description' => 'Failed login attempt for email: unknown@test.com', 'ip_address' => '10.0.0.55'],
    ['event' => 'grant_released',           'user_id' => $officerId,    'user_type' => 'field_officer', 'description' => 'Released ₱2,700.00 to 4PS-LPA-000001 — TXN-TEST-20260328160155', 'ip_address' => '192.168.1.102',
        'auditable_type' => 'App\Models\CashGrantDistribution', 'auditable_id' => 1,
        'new_values' => ['amount_released' => 2700.00, 'status' => 'claimed', 'claimed_by_type' => 'beneficiary']],
    ['event' => 'compliance_recorded',      'user_id' => $adminId,      'user_type' => 'verifier',   'description' => 'Compliance recorded for 4PS-LPA-000001 — Period: 2026-P1', 'ip_address' => '192.168.1.103',
        'auditable_type' => 'App\Models\ComplianceRecord', 'auditable_id' => 1,
        'old_values' => ['is_fully_compliant' => false], 'new_values' => ['edu_attendance_rate' => 92.5, 'health_compliant' => true, 'fds_compliant' => true, 'is_fully_compliant' => true]],
    ['event' => 'double_claim_attempt',     'user_id' => $officerId,    'user_type' => 'field_officer', 'description' => 'Duplicate claim blocked for 4PS-LPA-000001 — already claimed in event #1', 'ip_address' => '10.0.0.77',
        'auditable_type' => 'App\Models\Beneficiary', 'auditable_id' => 2, 'tags' => 'fraud,security'],
    ['event' => 'beneficiary_created',      'user_id' => $superadminId, 'user_type' => 'superadmin', 'description' => 'New beneficiary 4PS-LPA-000002 created', 'ip_address' => '192.168.1.100',
        'auditable_type' => 'App\Models\Beneficiary', 'auditable_id' => 2,
        'new_values' => ['unique_id' => '4PS-LPA-000002', 'barangay' => 'Sampaguita', 'status' => 'active']],
    ['event' => 'distribution_event_created','user_id' => $adminId,     'user_type' => 'admin',      'description' => 'Distribution event Q1 2026 created', 'ip_address' => '192.168.1.101'],
    ['event' => 'logout',                   'user_id' => $adminId,      'user_type' => 'admin',      'description' => 'Admin logged out',                   'ip_address' => '192.168.1.101'],
    ['event' => 'qr_scanned',              'user_id' => $officerId,    'user_type' => 'field_officer', 'description' => 'QR card scanned for verification: 4PS-LPA-000001', 'ip_address' => '192.168.1.102'],
];

foreach ($entries as $i => $entry) {
    AuditLog::create(array_merge([
        'url'        => 'http://127.0.0.1:8000/test',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'created_at' => now()->subMinutes(count($entries) - $i),
    ], $entry));
}

echo "✅ Created " . count($entries) . " audit log entries.\n";
