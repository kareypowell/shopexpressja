<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Manifest;

class ManifestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create a manifest
        Manifest::create([
            'name' => 'Test Manifest',
            'shipment_date' => now(),
            'reservation_number' => '123456',
            'flight_number' => 'AA123',
            'flight_destination' => 'MIA',
            'exchange_rate' => 150.00,
            'is_open' => true,
        ]);
    }
}
