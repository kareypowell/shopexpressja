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
            ['name' => 'HQ', 'address' => 'Mandeville, Manchester'],
            ['name' => 'Branch 1', 'address' => 'Sample Addr'],
            ['name' => 'Branch 2', 'address' => 'Sample Addr'],
            ['name' => 'Branch 3', 'address' => 'Sample Addr'],
        ])->each(function ($office) {
            Office::create($office);
        });
    }
}
