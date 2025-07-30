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
        // Create air manifest
        Manifest::create([
            'name' => 'Air Manifest - Miami Flight',
            'shipment_date' => now(),
            'reservation_number' => '123456',
            'flight_number' => 'AA123',
            'flight_destination' => 'MIA',
            'exchange_rate' => 160.00,
            'type' => 'air',
            'is_open' => true,
        ]);

        // Create sea manifests with vessel information
        Manifest::create([
            'name' => 'Sea Manifest - Caribbean Express',
            'shipment_date' => now()->addDays(7),
            'reservation_number' => 'SEA001',
            'vessel_name' => 'Caribbean Express',
            'voyage_number' => 'CE-2025-001',
            'departure_port' => 'Port of Kingston, Jamaica',
            'arrival_port' => 'Port of Miami, Florida',
            'estimated_arrival_date' => now()->addDays(14),
            'exchange_rate' => 160.00,
            'type' => 'sea',
            'is_open' => true,
        ]);

        Manifest::create([
            'name' => 'Sea Manifest - Atlantic Voyager',
            'shipment_date' => now()->addDays(3),
            'reservation_number' => 'SEA002',
            'vessel_name' => 'Atlantic Voyager',
            'voyage_number' => 'AV-2025-015',
            'departure_port' => 'Port of Montego Bay, Jamaica',
            'arrival_port' => 'Port Everglades, Florida',
            'estimated_arrival_date' => now()->addDays(10),
            'exchange_rate' => 160.00,
            'type' => 'sea',
            'is_open' => true,
        ]);

        Manifest::create([
            'name' => 'Sea Manifest - Island Trader',
            'shipment_date' => now()->addDays(14),
            'reservation_number' => 'SEA003',
            'vessel_name' => 'Island Trader',
            'voyage_number' => 'IT-2025-008',
            'departure_port' => 'Port of Spanish Town, Jamaica',
            'arrival_port' => 'Port of Tampa, Florida',
            'estimated_arrival_date' => now()->addDays(21),
            'exchange_rate' => 160.00,
            'type' => 'sea',
            'is_open' => false,
        ]);

        // Additional air manifest for comparison
        Manifest::create([
            'name' => 'Air Manifest - Fort Lauderdale Flight',
            'shipment_date' => now()->addDays(1),
            'reservation_number' => '123789',
            'flight_number' => 'JB456',
            'flight_destination' => 'FLL',
            'exchange_rate' => 160.00,
            'type' => 'air',
            'is_open' => true,
        ]);
    }
}
