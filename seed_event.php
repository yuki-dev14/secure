<?php
// Quick seed: create a test distribution event
$admin = App\Models\User::where('role', 'admin')->first();
App\Models\DistributionEvent::create([
    'title'                   => 'Q1 2026 Cash Grant Distribution',
    'period'                  => 'January-February 2026',
    'period_start'            => '2026-01-01',
    'period_end'              => '2026-02-28',
    'months_covered'          => 2,
    'distribution_date_start' => '2026-03-28',
    'distribution_date_end'   => '2026-03-30',
    'distribution_time_start' => '08:00',
    'distribution_time_end'   => '17:00',
    'venue'                   => 'Lipa City DSWD Office',
    'venue_address'           => 'JP Laurel Highway, Lipa City, Batangas',
    'status'                  => 'ongoing',
    'notes'                   => 'Bring valid ID and your 4Ps ID card. Queue by barangay assignment.',
    'created_by'              => $admin->id,
]);
echo "Event created\n";
