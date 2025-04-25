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

        Rate::create(['weight' => 0.5, 'price' => 1.50, 'processing_fee' => 1, 'type' => 'air']);
        Rate::create(['weight' => 1, 'price' => 3.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 2, 'price' => 6.00, 'processing_fee' => 2.25, 'type' => 'air']);
        Rate::create(['weight' => 3, 'price' => 9.00, 'processing_fee' => 2.50, 'type' => 'air']);
        Rate::create(['weight' => 4, 'price' => 12.00, 'processing_fee' => 3.00, 'type' => 'air']);
        Rate::create(['weight' => 5, 'price' => 15.00, 'processing_fee' => 3.00, 'type' => 'air']);
        Rate::create(['weight' => 6, 'price' => 18.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 7, 'price' => 21.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 8, 'price' => 24.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 9, 'price' => 27.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 10, 'price' => 30.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 11, 'price' => 32.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 12, 'price' => 34.40, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 13, 'price' => 37.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 14, 'price' => 39.40, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 15, 'price' => 41.78, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 16, 'price' => 44.16, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 17, 'price' => 46.54, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 18, 'price' => 48.92, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 19, 'price' => 51.30, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 20, 'price' => 53.68, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 21, 'price' => 56.06, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 22, 'price' => 58.44, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 23, 'price' => 60.82, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 24, 'price' => 63.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 25, 'price' => 65.58, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 26, 'price' => 67.96, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 27, 'price' => 70.34, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 28, 'price' => 72.72, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 29, 'price' => 75.10, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 30, 'price' => 77.48, 'processing_fee' => 3.50, 'type' => 'air']);

        Rate::create(['weight' => 1, 'price' => 1.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 2, 'price' => 3.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 3, 'price' => 4.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 4, 'price' => 6.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 5, 'price' => 7.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 6, 'price' => 9.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 7, 'price' => 10.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 8, 'price' => 12.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 9, 'price' => 13.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 10, 'price' => 15.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 11, 'price' => 16.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 12, 'price' => 17.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 13, 'price' => 18.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 14, 'price' => 19.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 15, 'price' => 20.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 16, 'price' => 21.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 17, 'price' => 22.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 18, 'price' => 23.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 19, 'price' => 24.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 20, 'price' => 25.00, 'processing_fee' => 2.00, 'type' => 'sea']);
    }
}
