<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;

class AddressesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create a primary address
        Address::create([
            'street_address' => '1910 SW 100th Terrace #D',
            'city' => 'Miramar',
            'state' => 'Florida',
            'zip_code' => '33025',
            'country' => 'United States',
            'is_primary' => true,
        ]);
    }
}
