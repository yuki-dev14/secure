<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;
use App\Models\User;

$beneficiaryBarangays = Beneficiary::distinct()->pluck('barangay')->filter()->sort()->values();
$assistantBarangays = User::where('role', 'barangay_assistant')->pluck('assigned_barangay')->filter()->unique()->toArray();

echo "ALL BENEFICIARY BARANGAYS:\n";
foreach ($beneficiaryBarangays as $bgy) {
    $hasAssistant = User::where('role', 'barangay_assistant')->where('assigned_barangay', $bgy)->exists();
    $status = $hasAssistant ? "✅ HAS ASSISTANT" : "❌ MISSING ASSISTANT";
    echo " - {$bgy}: {$status}\n";
}
