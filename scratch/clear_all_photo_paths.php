<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;

echo "=== RESETTING ALL BENEFICIARY PHOTO PATHS TO NULL (DEFAULT GREY AVATAR ICON) ===\n";

$updated = Beneficiary::query()->update(['photo_path' => null]);

echo "Reset {$updated} beneficiary photo_paths to null.\n";
