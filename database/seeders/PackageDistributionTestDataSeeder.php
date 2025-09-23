<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Enums\PackageStatus;

class PackageDistributionTestDataSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('Creating test packages for distribution testing...');

        // Get required data
        $customerRole = Role::where('name', 'customer')->first();
        $customers = User::where('role_id', $customerRole->id)->limit(4)->get();
        $manifest = Manifest::first() ?? Manifest::factory()->create(['type' => 'air']);
        $shipper = Shipper::first() ?? Shipper::factory()->create();
        $office = Office::first() ?? Office::factory()->create();

        if ($customers->isEmpty()) {
            $this->command->error('No customers found. Please run user seeders first.');
            return;
        }

        // Create packages for each customer scenario
        foreach ($customers as $index => $customer) {
            $this->createPackagesForCustomer($customer, $manifest, $shipper, $office, $index + 1);
        }

        $this->command->info('Test packages created successfully!');
    }

    /**
     * Create packages for a specific customer
     */
    private function createPackagesForCustomer(User $customer, Manifest $manifest, Shipper $shipper, Office $office, int $scenario): void
    {
        switch ($scenario) {
            case 1:
                // Customer 1: Single package ready for pickup
                Package::factory()->create([
                    'user_id' => $customer->id,
                    'manifest_id' => $manifest->id,
                    'shipper_id' => $shipper->id,
                    'office_id' => $office->id,
                    'status' => PackageStatus::READY,
                    'tracking_number' => 'TEST-001-' . $customer->id,
                    'description' => 'Electronics - Laptop',
                    'weight' => 5.5,
                    'freight_price' => 45.00,
                    'clearance_fee' => 15.00,
                    'storage_fee' => 8.00,
                    'delivery_fee' => 5.00,
                    // Total: $73.00
                ]);
                break;

            case 2:
                // Customer 2: Multiple packages ready for pickup
                Package::factory()->create([
                    'user_id' => $customer->id,
                    'manifest_id' => $manifest->id,
                    'shipper_id' => $shipper->id,
                    'office_id' => $office->id,
                    'status' => PackageStatus::READY,
                    'tracking_number' => 'TEST-002A-' . $customer->id,
                    'description' => 'Clothing - Shoes',
                    'weight' => 2.0,
                    'freight_price' => 25.00,
                    'clearance_fee' => 8.00,
                    'storage_fee' => 3.00,
                    'delivery_fee' => 4.00,
                    // Total: $40.00
                ]);

                Package::factory()->create([
                    'user_id' => $customer->id,
                    'manifest_id' => $manifest->id,
                    'shipper_id' => $shipper->id,
                    'office_id' => $office->id,
                    'status' => PackageStatus::READY,
                    'tracking_number' => 'TEST-002B-' . $customer->id,
                    'description' => 'Books - Educational',
                    'weight' => 3.2,
                    'freight_price' => 30.00,
                    'clearance_fee' => 5.00,
                    'storage_fee' => 4.00,
                    'delivery_fee' => 3.00,
                    // Total: $42.00
                ]);
                break;

            case 3:
                // Customer 3: High-value package
                Package::factory()->create([
                    'user_id' => $customer->id,
                    'manifest_id' => $manifest->id,
                    'shipper_id' => $shipper->id,
                    'office_id' => $office->id,
                    'status' => PackageStatus::READY,
                    'tracking_number' => 'TEST-003-' . $customer->id,
                    'description' => 'Electronics - Smartphone',
                    'weight' => 1.5,
                    'freight_price' => 35.00,
                    'clearance_fee' => 25.00,
                    'storage_fee' => 6.00,
                    'delivery_fee' => 4.00,
                    'estimated_value' => 800.00,
                    // Total: $70.00
                ]);
                break;

            case 4:
                // Customer 4: Mixed status packages (some ready, some not)
                Package::factory()->create([
                    'user_id' => $customer->id,
                    'manifest_id' => $manifest->id,
                    'shipper_id' => $shipper->id,
                    'office_id' => $office->id,
                    'status' => PackageStatus::READY,
                    'tracking_number' => 'TEST-004A-' . $customer->id,
                    'description' => 'Home Goods - Kitchen Items',
                    'weight' => 4.0,
                    'freight_price' => 40.00,
                    'clearance_fee' => 12.00,
                    'storage_fee' => 5.00,
                    'delivery_fee' => 3.00,
                    // Total: $60.00
                ]);

                Package::factory()->create([
                    'user_id' => $customer->id,
                    'manifest_id' => $manifest->id,
                    'shipper_id' => $shipper->id,
                    'office_id' => $office->id,
                    'status' => PackageStatus::CUSTOMS,
                    'tracking_number' => 'TEST-004B-' . $customer->id,
                    'description' => 'Clothing - Winter Coat',
                    'weight' => 2.8,
                    'freight_price' => 28.00,
                    'clearance_fee' => 0.00, // Not yet processed
                    'storage_fee' => 0.00,
                    'delivery_fee' => 0.00,
                ]);
                break;
        }

        $readyPackages = Package::where('user_id', $customer->id)
            ->where('status', PackageStatus::READY)
            ->count();

        $this->command->info("Created packages for {$customer->full_name} ({$readyPackages} ready for pickup)");
    }
}