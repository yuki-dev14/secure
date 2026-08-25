<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$period = '2026-P4';
$periodData = ['start' => '2026-07-01', 'end' => '2026-08-31'];

try {
    $event = App\Models\DistributionEvent::firstOrCreate(
        ['period' => $period],
        [
            'title'                   => "Grant Distribution {$period}",
            'notes'                   => "Auto-created for grant computation — period {$period}",
            'period_start'            => $periodData['start'],
            'period_end'              => $periodData['end'],
            'distribution_date_start' => $periodData['start'],
            'distribution_date_end'   => $periodData['end'],
            'venue'                   => 'Lipa City SWDO Office',
            'venue_address'           => 'Marawoy, Lipa City, Batangas',
            'status'                  => 'upcoming',
            'months_covered'          => 2,
            'created_by'              => 1,
        ]
    );

    echo "SUCCESS: Distribution Event ID: {$event->id} | Title: {$event->title} | Start: {$event->period_start->toDateString()}\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
