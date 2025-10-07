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

        //Rate::create(['weight' => 0.5, 'price' => 2.50, 'processing_fee' => 1, 'type' => 'air']);
        Rate::create(['weight' => 1, 'price' => 3.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 2, 'price' => 5.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 3, 'price' => 7.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 4, 'price' => 9.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 5, 'price' => 11.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 6, 'price' => 13.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 7, 'price' => 15.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 8, 'price' => 17.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 9, 'price' => 19.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 10, 'price' => 21.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 11, 'price' => 23.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 12, 'price' => 25.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 13, 'price' => 27.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 14, 'price' => 29.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 15, 'price' => 31.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 16, 'price' => 33.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 17, 'price' => 35.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 18, 'price' => 37.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 19, 'price' => 39.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 20, 'price' => 41.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 21, 'price' => 43.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 22, 'price' => 45.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 23, 'price' => 47.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 24, 'price' => 49.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 25, 'price' => 51.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 26, 'price' => 53.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 27, 'price' => 55.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 28, 'price' => 57.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 29, 'price' => 59.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 30, 'price' => 61.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 31, 'price' => 63.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 32, 'price' => 65.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 33, 'price' => 67.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 34, 'price' => 69.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 35, 'price' => 71.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 36, 'price' => 73.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 37, 'price' => 75.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 38, 'price' => 77.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 39, 'price' => 79.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 40, 'price' => 81.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 41, 'price' => 83.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 42, 'price' => 85.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 43, 'price' => 87.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 44, 'price' => 89.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 45, 'price' => 91.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 46, 'price' => 93.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 47, 'price' => 95.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 48, 'price' => 97.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 49, 'price' => 99.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 50, 'price' => 101.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 51, 'price' => 103.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 52, 'price' => 105.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 53, 'price' => 107.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 54, 'price' => 109.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 55, 'price' => 111.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 56, 'price' => 113.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 57, 'price' => 115.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 58, 'price' => 117.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 59, 'price' => 119.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 60, 'price' => 121.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 61, 'price' => 123.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 62, 'price' => 125.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 63, 'price' => 127.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 64, 'price' => 129.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 65, 'price' => 131.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 66, 'price' => 133.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 67, 'price' => 135.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 68, 'price' => 137.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 69, 'price' => 139.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 70, 'price' => 141.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 71, 'price' => 143.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 72, 'price' => 145.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 73, 'price' => 147.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 74, 'price' => 149.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 75, 'price' => 151.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 76, 'price' => 153.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 77, 'price' => 155.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 78, 'price' => 157.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 79, 'price' => 159.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 80, 'price' => 161.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 81, 'price' => 163.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 82, 'price' => 165.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 83, 'price' => 167.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 84, 'price' => 169.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 85, 'price' => 171.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 86, 'price' => 173.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 87, 'price' => 175.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 88, 'price' => 177.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 89, 'price' => 179.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 90, 'price' => 181.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 91, 'price' => 183.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 92, 'price' => 185.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 93, 'price' => 187.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 94, 'price' => 189.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 95, 'price' => 191.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 96, 'price' => 193.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 97, 'price' => 195.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 98, 'price' => 197.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 99, 'price' => 199.00, 'processing_fee' => 1.25, 'type' => 'air']);
        Rate::create(['weight' => 100, 'price' => 201.00, 'processing_fee' => 1.25, 'type' => 'air']);

        // Sea rates based on cubic feet ranges - comprehensive coverage for testing
        // Small packages (boxes, small barrels) - Processing fee $3.50 for 0.1-100 cubic feet
        Rate::create(['min_cubic_feet' => 0.1, 'max_cubic_feet' => 0.5, 'price' => 9.00, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 0.6, 'max_cubic_feet' => 1.0, 'price' => 8.50, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 1.1, 'max_cubic_feet' => 1.5, 'price' => 8.00, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 1.6, 'max_cubic_feet' => 2.0, 'price' => 7.75, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 2.1, 'max_cubic_feet' => 2.5, 'price' => 7.50, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 2.6, 'max_cubic_feet' => 3.0, 'price' => 7.25, 'processing_fee' => 3.50, 'type' => 'sea']);
        
        // Medium packages (medium boxes, standard barrels)
        Rate::create(['min_cubic_feet' => 3.1, 'max_cubic_feet' => 4.0, 'price' => 7.00, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 4.1, 'max_cubic_feet' => 5.0, 'price' => 6.75, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 5.1, 'max_cubic_feet' => 6.0, 'price' => 6.50, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 6.1, 'max_cubic_feet' => 7.5, 'price' => 6.25, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 7.6, 'max_cubic_feet' => 10.0, 'price' => 6.00, 'processing_fee' => 3.50, 'type' => 'sea']);
        
        // Large packages (large boxes, big barrels, small pallets)
        Rate::create(['min_cubic_feet' => 10.1, 'max_cubic_feet' => 12.5, 'price' => 5.75, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 12.6, 'max_cubic_feet' => 15.0, 'price' => 5.50, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 15.1, 'max_cubic_feet' => 17.5, 'price' => 5.25, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 17.6, 'max_cubic_feet' => 20.0, 'price' => 5.00, 'processing_fee' => 3.50, 'type' => 'sea']);
        
        // Extra large packages (standard pallets)
        Rate::create(['min_cubic_feet' => 20.1, 'max_cubic_feet' => 25.0, 'price' => 4.75, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 25.1, 'max_cubic_feet' => 30.0, 'price' => 4.50, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 30.1, 'max_cubic_feet' => 35.0, 'price' => 4.25, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 35.1, 'max_cubic_feet' => 40.0, 'price' => 4.00, 'processing_fee' => 3.50, 'type' => 'sea']);
        
        // Large pallets and bulk shipments
        Rate::create(['min_cubic_feet' => 40.1, 'max_cubic_feet' => 50.0, 'price' => 3.75, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 50.1, 'max_cubic_feet' => 60.0, 'price' => 3.50, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 60.1, 'max_cubic_feet' => 75.0, 'price' => 3.25, 'processing_fee' => 3.50, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 75.1, 'max_cubic_feet' => 100.0, 'price' => 3.00, 'processing_fee' => 3.50, 'type' => 'sea']);
        
        // Commercial bulk shipments - Processing fee $10.00 for over 100 cubic feet
        Rate::create(['min_cubic_feet' => 100.1, 'max_cubic_feet' => 125.0, 'price' => 2.75, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 125.1, 'max_cubic_feet' => 150.0, 'price' => 2.50, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 150.1, 'max_cubic_feet' => 200.0, 'price' => 2.25, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 200.1, 'max_cubic_feet' => 250.0, 'price' => 2.00, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 250.1, 'max_cubic_feet' => 300.0, 'price' => 1.90, 'processing_fee' => 10.00, 'type' => 'sea']);
        
        // Large commercial shipments
        Rate::create(['min_cubic_feet' => 300.1, 'max_cubic_feet' => 400.0, 'price' => 1.80, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 400.1, 'max_cubic_feet' => 500.0, 'price' => 1.70, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 500.1, 'max_cubic_feet' => 750.0, 'price' => 1.60, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 750.1, 'max_cubic_feet' => 1000.0, 'price' => 1.50, 'processing_fee' => 10.00, 'type' => 'sea']);
        
        // Very large commercial/industrial shipments
        Rate::create(['min_cubic_feet' => 1000.1, 'max_cubic_feet' => 1500.0, 'price' => 1.40, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 1500.1, 'max_cubic_feet' => 2000.0, 'price' => 1.30, 'processing_fee' => 10.00, 'type' => 'sea']);
        Rate::create(['min_cubic_feet' => 2000.1, 'max_cubic_feet' => 5000.0, 'price' => 1.20, 'processing_fee' => 10.00, 'type' => 'sea']);
    }
}
