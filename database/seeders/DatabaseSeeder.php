<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use App\Services\BeneficiaryCardService;
use App\Services\QrCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OfficeSeeder::class,
            UserSeeder::class,
        ]);
    }
}
