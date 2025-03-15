<?php

namespace Database\Seeders;

use App\Models\Rate;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(AddressesTableSeeder::class);
        $this->call(ManifestsTableSeeder::class);
        $this->call(OfficesTableSeeder::class);
        $this->call(ShippersTableSeeder::class);
        $this->call(RatesTableSeeder::class);
        $this->call(RolesTableSeeder::class);
    }
}
