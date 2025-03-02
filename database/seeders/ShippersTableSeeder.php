<?php

namespace Database\Seeders;

use App\Models\Shipper;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ShippersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('shippers')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        collect(['USPS', 'FedEX', 'UPS', 'Victoria Secrets', 'DHL'])->each(function ($shipper) {
            Shipper::create(['name' => $shipper]);
        });
    }
}
