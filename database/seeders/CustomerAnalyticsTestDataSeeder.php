<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use App\Models\Package;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class CustomerAnalyticsTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ensure customer role exists (role_id = 3)
        $customerRole = Role::firstOrCreate(['id' => 3], ['name' => 'customer']);

        // Create test customers with varied registration dates over the last 90 days
        $customers = [];
        $parishes = ['Kingston', 'St. Andrew', 'St. Catherine', 'Clarendon', 'Manchester', 'St. Elizabeth', 'Westmoreland', 'Hanover', 'St. James', 'Trelawny', 'St. Ann', 'St. Mary', 'Portland', 'St. Thomas'];
        $countries = ['Jamaica', 'Jamaica', 'Jamaica', 'Jamaica', 'Jamaica', 'USA', 'Canada', 'UK']; // Mostly Jamaica

        for ($i = 1; $i <= 50; $i++) {
            // Create customers with varied registration dates
            $registrationDate = Carbon::now()->subDays(rand(1, 90));
            $email = 'testcustomer' . $i . '_' . time() . '@analytics.test';
            
            // Skip if user already exists
            if (User::where('email', $email)->exists()) {
                continue;
            }
            
            $customer = User::create([
                'first_name' => 'TestCustomer' . $i,
                'last_name' => 'Analytics' . $i,
                'email' => $email,
                'password' => Hash::make('password'),
                'role_id' => 3,
                'email_verified_at' => rand(0, 10) > 2 ? $registrationDate->addHours(rand(1, 48)) : null, // 80% verified
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
                'deleted_at' => rand(0, 100) > 95 ? Carbon::now()->subDays(rand(1, 30)) : null, // 5% suspended
            ]);

            // Create profile for each customer
            Profile::create([
                'user_id' => $customer->id,
                'account_number' => 'TACC' . time() . str_pad($i, 3, '0', STR_PAD_LEFT),
                'tax_number' => rand(100000000, 999999999),
                'telephone_number' => '+1876' . rand(1000000, 9999999),
                'parish' => $parishes[array_rand($parishes)],
                'street_address' => rand(1, 999) . ' Test Street',
                'city_town' => 'Test City ' . $i,
                'country' => $countries[array_rand($countries)],
                'pickup_location' => rand(1, 3), // Office IDs: 1=Mandeville, 2=Junction, 3=Santa Cruz
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            $customers[] = $customer;
        }

        // Get existing IDs for foreign keys
        $manifestIds = \App\Models\Manifest::pluck('id')->toArray();
        $shipperIds = \App\Models\Shipper::pluck('id')->toArray();
        $officeIds = \App\Models\Office::pluck('id')->toArray();

        // Create packages for customers with varied dates and amounts
        $statuses = ['delivered', 'in_transit', 'ready_for_pickup', 'delayed'];
        $packageTypes = ['Electronics', 'Clothing', 'Books', 'Toys', 'Home Goods', 'Sports Equipment'];

        foreach ($customers as $customer) {
            // Skip creating packages for suspended customers
            if ($customer->deleted_at) {
                continue;
            }

            // Create 1-15 packages per customer
            $packageCount = rand(1, 15);
            
            for ($j = 1; $j <= $packageCount; $j++) {
                // Package dates should be after customer registration
                $packageDate = Carbon::parse($customer->created_at)->addDays(rand(1, 60));
                
                // Skip if package date is in the future
                if ($packageDate->isFuture()) {
                    continue;
                }

                Package::create([
                    'user_id' => $customer->id,
                    'manifest_id' => $manifestIds[array_rand($manifestIds)],
                    'shipper_id' => $shipperIds[array_rand($shipperIds)],
                    'office_id' => $officeIds[array_rand($officeIds)],
                    'tracking_number' => 'TRK' . $customer->id . str_pad($j, 3, '0', STR_PAD_LEFT),
                    'description' => $packageTypes[array_rand($packageTypes)],
                    'weight' => rand(1, 50) + (rand(0, 99) / 100), // 1.00 to 50.99 lbs
                    'estimated_value' => rand(10, 500),
                    'freight_price' => rand(15, 100) + (rand(0, 99) / 100),
                    'customs_duty' => rand(0, 50) + (rand(0, 99) / 100),
                    'storage_fee' => rand(0, 25) + (rand(0, 99) / 100),
                    'delivery_fee' => rand(5, 30) + (rand(0, 99) / 100),
                    'status' => $statuses[array_rand($statuses)],
                    'created_at' => $packageDate,
                    'updated_at' => $packageDate->addDays(rand(0, 10)),
                ]);
            }
        }

        $this->command->info('Created ' . count($customers) . ' test customers with profiles and packages for analytics testing.');
    }
}