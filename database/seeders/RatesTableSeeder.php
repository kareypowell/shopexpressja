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

        Rate::create(['weight' => 1, 'rate' => 3.30, 'processing_fee' => 1.50, 'type' => 'air']);
        Rate::create(['weight' => 2, 'rate' => 6.00, 'processing_fee' => 2.50, 'type' => 'air']);
        Rate::create(['weight' => 3, 'rate' => 9.00, 'processing_fee' => 3.00, 'type' => 'air']);
        Rate::create(['weight' => 4, 'rate' => 12.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 5, 'rate' => 15.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 6, 'rate' => 18.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 7, 'rate' => 21.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 8, 'rate' => 24.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 9, 'rate' => 27.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 10, 'rate' => 30.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 11, 'rate' => 32.30, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 12, 'rate' => 35.40, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 13, 'rate' => 37.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 14, 'rate' => 40.30, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 15, 'rate' => 42.75, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 16, 'rate' => 45.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 17, 'rate' => 47.65, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 18, 'rate' => 50.10, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 19, 'rate' => 52.55, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 20, 'rate' => 55.00, 'processing_fee' => 3.50, 'type' => 'air']);

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
        Rate::create(['weight' => 11, 'rate' => 16.30, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 12, 'rate' => 17.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 13, 'rate' => 18.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 14, 'rate' => 20.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 15, 'rate' => 21.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 16, 'rate' => 22.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 17, 'rate' => 23.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 18, 'rate' => 24.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 19, 'rate' => 25.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['weight' => 20, 'rate' => 26.00, 'processing_fee' => 2.00, 'type' => 'sea']);
    }
}
