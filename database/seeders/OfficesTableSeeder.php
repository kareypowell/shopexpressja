<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use App\Models\Office;

class OfficesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('offices')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        collect([
            ['name' => 'Mandeville', 'address' => 'Shop #24b Reliance Plaza, Mandeville, Manchester'],
            ['name' => 'Junction', 'address' => 'Lot 28, Main Street, Junction, St. Elizabeth'],
            ['name' => 'Santa Cruz', 'address' => 'Ashwood, Santa Cruz, St. Elizabeth'],
        ])->each(function ($office) {
            Office::create($office);
        });
    }
}
