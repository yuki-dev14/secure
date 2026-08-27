<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Beneficiary;
use Illuminate\Support\Facades\Storage;

echo "=== SEEDING REALISTIC AVATARS FOR BENEFICIARIES ===\n";

$storageDir = storage_path('app/public/beneficiaries/photos');
if (!file_exists($storageDir)) {
    mkdir($storageDir, 0777, true);
}

// Curated high quality avatars for female / male beneficiaries
$avatars = [
    'female' => [
        'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1567532939604-b6b5b0db2604?w=400&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80',
    ],
    'male' => [
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&auto=format&fit=crop&q=80',
    ]
];

$beneficiaries = Beneficiary::all();
$fCount = 0;
$mCount = 0;

foreach ($beneficiaries as $b) {
    $sex = strtolower($b->sex) === 'male' ? 'male' : 'female';
    $list = $avatars[$sex];
    $idx = ($sex === 'female') ? ($fCount % count($list)) : ($mCount % count($list));
    if ($sex === 'female') $fCount++; else $mCount++;

    $url = $list[$idx];

    try {
        $imgData = @file_get_contents($url);
        if ($imgData) {
            $filename = "beneficiaries/photos/photo_{$b->id}.jpg";
            Storage::disk('public')->put($filename, $imgData);
            $b->update(['photo_path' => $filename]);
            echo "Updated photo for {$b->full_name} ({$b->unique_id})\n";
        }
    } catch (\Throwable $e) {
        echo "Failed photo for {$b->full_name}: " . $e->getMessage() . "\n";
    }
}

echo "✅ ALL MOCK BENEFICIARIES UPDATED WITH REALISTIC PROFILE PHOTOS!\n";
