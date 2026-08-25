<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

DB::purge();
DB::reconnect();

if (!Schema::hasTable('staff_messages')) {
    Schema::create('staff_messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('cascade');
        $table->text('message');
        $table->string('attachment_path')->nullable();
        $table->timestamp('read_at')->nullable();
        $table->timestamps();

        $table->index(['sender_id', 'recipient_id']);
        $table->index(['recipient_id', 'read_at']);
    });
    echo "staff_messages table created successfully!\n";
} else {
    echo "staff_messages table already exists.\n";
}

// Mark migration as ran in migrations table if not present
$migrationName = '2026_08_25_090000_create_staff_messages_table';
if (!DB::table('migrations')->where('migration', $migrationName)->exists()) {
    $batch = DB::table('migrations')->max('batch') + 1;
    DB::table('migrations')->insert([
        'migration' => $migrationName,
        'batch'     => $batch,
    ]);
    echo "Migration recorded in migrations table.\n";
}
