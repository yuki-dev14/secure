<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$beneficiaries = \App\Models\Beneficiary::where('barangay', 'Mataas na Lupa')->with(['user', 'cards', 'card'])->get();

foreach ($beneficiaries as $b) {
    echo sprintf(
        "ID: %s | User: %s | must_change_pw: %s | card_is_first: %s | card_pw_changed_at: %s\n",
        $b->unique_id,
        $b->user?->email,
        $b->user?->must_change_password ? 'YES' : 'NO',
        $b->card?->is_first_login ? 'YES' : 'NO',
        $b->card?->password_changed_at ? $b->card->password_changed_at : 'NULL'
    );
}
