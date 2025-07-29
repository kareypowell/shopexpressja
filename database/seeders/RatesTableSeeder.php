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

        Rate::create(['weight' => 0.5, 'price' => 2.50, 'processing_fee' => 1, 'type' => 'air']);
        Rate::create(['weight' => 1, 'price' => 4.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 2, 'price' => 7.00, 'processing_fee' => 2.25, 'type' => 'air']);
        Rate::create(['weight' => 3, 'price' => 10.00, 'processing_fee' => 2.50, 'type' => 'air']);
        Rate::create(['weight' => 4, 'price' => 13.00, 'processing_fee' => 3.00, 'type' => 'air']);
        Rate::create(['weight' => 5, 'price' => 16.00, 'processing_fee' => 3.00, 'type' => 'air']);
        Rate::create(['weight' => 6, 'price' => 19.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 7, 'price' => 22.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 8, 'price' => 25.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 9, 'price' => 28.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 10, 'price' => 31.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 11, 'price' => 33.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 12, 'price' => 35.40, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 13, 'price' => 38.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 14, 'price' => 40.40, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 15, 'price' => 42.78, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 16, 'price' => 45.16, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 17, 'price' => 47.54, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 18, 'price' => 49.92, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 19, 'price' => 52.30, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 20, 'price' => 54.68, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 21, 'price' => 57.06, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 22, 'price' => 59.44, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 23, 'price' => 61.82, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 24, 'price' => 64.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 25, 'price' => 66.58, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 26, 'price' => 68.96, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 27, 'price' => 71.34, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 28, 'price' => 73.72, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 29, 'price' => 76.10, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 30, 'price' => 78.48, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 31, 'price' => 80.86, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 32, 'price' => 83.24, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 33, 'price' => 85.62, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 34, 'price' => 88.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 35, 'price' => 90.38, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 36, 'price' => 92.76, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 37, 'price' => 95.14, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 38, 'price' => 97.52, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 39, 'price' => 99.90, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 40, 'price' => 102.28, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 41, 'price' => 104.66, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 42, 'price' => 107.04, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 43, 'price' => 109.42, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 44, 'price' => 111.80, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 45, 'price' => 114.18, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 46, 'price' => 116.56, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 47, 'price' => 118.94, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 48, 'price' => 121.32, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 49, 'price' => 123.70, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 50, 'price' => 126.08, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 51, 'price' => 128.46, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 52, 'price' => 130.84, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 53, 'price' => 133.22, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 54, 'price' => 135.60, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 55, 'price' => 137.98, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 56, 'price' => 140.36, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 57, 'price' => 142.74, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 58, 'price' => 145.12, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 59, 'price' => 147.50, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 60, 'price' => 149.88, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 61, 'price' => 152.26, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 62, 'price' => 154.64, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 63, 'price' => 157.02, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 64, 'price' => 159.40, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 65, 'price' => 161.78, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 66, 'price' => 164.16, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 67, 'price' => 166.54, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 68, 'price' => 168.92, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 69, 'price' => 171.30, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 70, 'price' => 173.68, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 71, 'price' => 176.06, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 72, 'price' => 178.44, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 73, 'price' => 180.82, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 74, 'price' => 183.20, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 75, 'price' => 185.58, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 76, 'price' => 187.96, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 77, 'price' => 190.34, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 78, 'price' => 192.72, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 79, 'price' => 195.10, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 80, 'price' => 197.48, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 81, 'price' => 199.86, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 82, 'price' => 202.24, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 83, 'price' => 204.62, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 84, 'price' => 207.00, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 85, 'price' => 209.38, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 86, 'price' => 211.76, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 87, 'price' => 214.14, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 88, 'price' => 216.52, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 89, 'price' => 218.90, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 90, 'price' => 221.28, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 91, 'price' => 223.66, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 92, 'price' => 226.04, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 93, 'price' => 228.42, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 94, 'price' => 230.80, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 95, 'price' => 233.18, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 96, 'price' => 235.56, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 97, 'price' => 237.94, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 98, 'price' => 240.32, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 99, 'price' => 242.70, 'processing_fee' => 3.50, 'type' => 'air']);
        Rate::create(['weight' => 100, 'price' => 245.08, 'processing_fee' => 3.50, 'type' => 'air']);

        // Sea rates based on cubic feet ranges
        Rate::create(['min_cubic_feet' => 0.1, 'max_cubic_feet' => 1.0, 'price' => 8.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 1.1, 'max_cubic_feet' => 2.0, 'price' => 7.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 2.1, 'max_cubic_feet' => 3.0, 'price' => 7.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 3.1, 'max_cubic_feet' => 4.0, 'price' => 6.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 4.1, 'max_cubic_feet' => 5.0, 'price' => 6.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 5.1, 'max_cubic_feet' => 7.5, 'price' => 5.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 7.6, 'max_cubic_feet' => 10.0, 'price' => 5.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 10.1, 'max_cubic_feet' => 15.0, 'price' => 4.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 15.1, 'max_cubic_feet' => 20.0, 'price' => 4.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 20.1, 'max_cubic_feet' => 25.0, 'price' => 3.75, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 25.1, 'max_cubic_feet' => 30.0, 'price' => 3.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 30.1, 'max_cubic_feet' => 40.0, 'price' => 3.25, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 40.1, 'max_cubic_feet' => 50.0, 'price' => 3.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 50.1, 'max_cubic_feet' => 75.0, 'price' => 2.75, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 75.1, 'max_cubic_feet' => 100.0, 'price' => 2.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 100.1, 'max_cubic_feet' => 150.0, 'price' => 2.25, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 150.1, 'max_cubic_feet' => 200.0, 'price' => 2.00, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 200.1, 'max_cubic_feet' => 300.0, 'price' => 1.75, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 300.1, 'max_cubic_feet' => 500.0, 'price' => 1.50, 'processing_fee' => 2.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 500.1, 'max_cubic_feet' => 1000.0, 'price' => 1.25, 'processing_fee' => 2.00, 'type' => 'sea']);
    }
}
