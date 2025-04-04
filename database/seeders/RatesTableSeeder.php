<?php

namespace Database\Seeders;

use App\Models\Rate;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('rates')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Rate::create(['weight' => 0.5, 'rate' => 1.50, 'processing_fee' => 1, 'type' => 'air']);
        Rate::create(['weight' => 1, 'rate' => 3.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 2, 'rate' => 6.00, 'processing_fee' => 2.25, 'type' => 'air']);
        Rate::create(['weight' => 3, 'rate' => 9.00, 'processing_fee' => 2.50, 'type' => 'air']);
        Rate::create(['weight' => 4, 'rate' => 12.00, 'processing_fee' => 3.00, 'type' => 'air']);
        Rate::create(['weight' => 5, 'rate' => 15.00, 'processing_fee' => 3.00, 'type' => 'air']);
        Rate::create(['weight' => 6, 'rate' => 18.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 7, 'rate' => 21.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 8, 'rate' => 24.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 9, 'rate' => 27.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 10, 'rate' => 30.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 11, 'rate' => 32.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 12, 'rate' => 34.40, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 13, 'rate' => 37.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 14, 'rate' => 39.40, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 15, 'rate' => 41.78, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 16, 'rate' => 44.16, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 17, 'rate' => 46.54, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 18, 'rate' => 48.92, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 19, 'rate' => 51.30, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 20, 'rate' => 53.68, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 21, 'rate' => 56.06, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 22, 'rate' => 58.44, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 23, 'rate' => 60.82, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 24, 'rate' => 63.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 25, 'rate' => 65.58, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 26, 'rate' => 67.96, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 27, 'rate' => 70.34, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 28, 'rate' => 72.72, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 29, 'rate' => 75.10, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 30, 'rate' => 77.48, 'processing_fee' => 3.50, 'type' => 'air']);

        Rate::create(['weight' => 1, 'rate' => 1.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 2, 'rate' => 3.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 3, 'rate' => 4.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 4, 'rate' => 6.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 5, 'rate' => 7.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 6, 'rate' => 9.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 7, 'rate' => 10.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 8, 'rate' => 12.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 9, 'rate' => 13.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 10, 'rate' => 15.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 11, 'rate' => 16.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 12, 'rate' => 17.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 13, 'rate' => 18.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 14, 'rate' => 19.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 15, 'rate' => 20.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 16, 'rate' => 21.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 17, 'rate' => 22.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 18, 'rate' => 23.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 19, 'rate' => 24.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 20, 'rate' => 25.00, 'processing_fee' => 2.00, 'type' => 'sea']);
    }
}
