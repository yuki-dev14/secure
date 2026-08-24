<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CashGrantCalculation;
use App\Models\DistributionEvent;

// Delete calculations linked to events with period "January–February 2026" or duplicate events
$legacyEvents = DistributionEvent::where('period', 'LIKE', '%January%')->get();

foreach ($legacyEvents as $ev) {
    CashGrantCalculation::where('distribution_event_id', $ev->id)->delete();
    $ev->delete();
}

// Remove duplicate calculations for the same beneficiary and distribution_event_id (keep latest)
$allCalcs = CashGrantCalculation::orderBy('id', 'desc')->get();
$seen = [];

foreach ($allCalcs as $calc) {
    $key = $calc->beneficiary_id . '-' . $calc->distribution_event_id;
    if (isset($seen[$key])) {
        $calc->delete();
    } else {
        $seen[$key] = true;
    }
}

echo "Legacy grants cleaned. Remaining grant calculations: " . CashGrantCalculation::count() . "\n";
