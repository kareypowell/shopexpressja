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

        collect([
            'USPS', 'FedEX', 'UPS', 'Victoria Secrets', 'DHL', 'Amazon', 'Other', 
            'Shein', 'Fashionova', 'Zara', 'Boohoo', 'Forever 21', 'H&M', 'ASOS',
            'Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance', 'Converse', 'Vans', 
            'Sketchers', 'Crocs',
        ])->each(function ($shipper) {
            Shipper::create(['name' => $shipper]);
        });
    }
}
