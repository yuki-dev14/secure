<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'Anilao', 'Anilao-Labac', 'Antipolo del Norte', 'Antipolo del Sur',
            'Bagong Pook', 'Balintawak', 'Banaybanay', 'Banjo East', 'Banjo West',
            'Bañadero', 'Bolbok', 'Buhay na Tubig', 'Bukal', 'Bulacnin', 'Bulaklak',
            'Calamias', 'Cumba', 'Dagatan', 'Duhatan', 'Lalakay', 'Lumbang',
            'Mabini', 'Malagonlong', 'Malitlit', 'Marawoy', 'Mataas na Lupa',
            'Munting Pulo', 'Pagolingin East', 'Pagolingin West', 'Pangao', 'Pinagkawitan',
            'Pinagtongulan', 'Plaridel', 'Poblacion Barangay 1', 'Poblacion Barangay 2',
            'Poblacion Barangay 3', 'Poblacion Barangay 4', 'Poblacion Barangay 5',
            'Poblacion Barangay 6', 'Poblacion Barangay 7', 'Poblacion Barangay 8',
            'Poblacion Barangay 9', 'Poblacion Barangay 10',
            'Pusil', 'Ректо', 'Rizal', 'Sabang', 'San Benito', 'San Carlos',
            'San Celestino', 'San Francisco', 'San Guillermo', 'San Jose',
            'San Lucas', 'San Miguel', 'San Polycarpio', 'San Sebastian', 'Santor',
            'Sico', 'Tabangao Aplaya', 'Tabangao Ambulong', 'Talisay', 'Tambo',
            'Tangob', 'Tanguay', 'Tibig', 'Tipacan',
        ];

        // Main DSWD Office
        $mainOffice = Office::create([
            'name'              => 'DSWD Lipa City Social Welfare and Development Office',
            'code'              => 'LIPA-MAIN',
            'type'              => 'main',
            'address'           => 'City Hall Compound, C.M. Recto Avenue, Lipa City, Batangas',
            'barangay'          => 'Poblacion Barangay 1',
            'city'              => 'Lipa City',
            'province'          => 'Batangas',
            'contact_number'    => '043-756-0000',
            'officer_in_charge' => 'City Social Welfare and Development Officer',
            'is_active'         => true,
        ]);

        // Barangay offices
        foreach ($barangays as $barangay) {
            // Keep code ≤ 60 chars: BGY- prefix + max 56 chars of slug
            $slug = strtoupper(str_replace([' ', '-', '.'], '_', $barangay));
            $slug = substr($slug, 0, 55);
            Office::create([
                'name'     => "Barangay Social Welfare Center - {$barangay}",
                'code'     => "BGY-{$slug}",
                'type'     => 'barangay',
                'barangay' => $barangay,
                'city'     => 'Lipa City',
                'province' => 'Batangas',
                'is_active'=> true,
            ]);
        }
    }
}
