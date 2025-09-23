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
use App\Models\CustomerTransaction;
use App\Models\PackageDistribution;
use App\Models\PackageDistributionItem;
use App\Enums\PackageStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ComprehensiveTestDataSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating comprehensive test data for all scenarios...');
        
        // Clear existing data
        $this->clearExistingData();
        
        // Create base data
        $baseData = $this->createBaseData();
        
        // Create comprehensive customer scenarios
        $customers = $this->createCustomerScenarios($baseData);
        
        // Create package scenarios for each customer
        $this->createPackageScenarios($customers, $baseData);
        
        // Create historical distribution scenarios
        $this->createHistoricalDistributions($customers, $baseData);
        
        // Create workflow test scenarios
        $this->createWorkflowScenarios($customers, $baseData);
        
        $this->command->info('✅ Comprehensive test data created successfully!');
        $this->displayTestScenarios();
    }

    /**
     * Clear existing test data
     */
    private function clearExistingData(): void
    {
        $this->command->info('🧹 Clearing existing test data...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear distribution related data
        PackageDistributionItem::truncate();
        PackageDistribution::truncate();
        CustomerTransaction::truncate();
        
        // Clear packages (keep manifests, offices, shippers)
        Package::truncate();
        
        // Reset customer balances
        $customerRole = Role::where('name', 'customer')->first();
        if ($customerRole) {
            User::where('role_id', $customerRole->id)->update([
                'account_balance' => 0.00,
                'credit_balance' => 0.00,
            ]);
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Create base data needed for scenarios
     */
    private function createBaseData(): array
    {
        $this->command->info('📋 Creating base data...');
        
        // Get or create manifests
        $airManifest = Manifest::where('type', 'air')->first() ?? 
            Manifest::factory()->create(['type' => 'air', 'name' => 'Test Air Manifest']);
        
        $seaManifest = Manifest::where('type', 'sea')->first() ?? 
            Manifest::factory()->create(['type' => 'sea', 'name' => 'Test Sea Manifest']);
        
        // Get or create shippers and offices
        $shipper = Shipper::first() ?? Shipper::factory()->create(['name' => 'Test Shipper']);
        $office = Office::first() ?? Office::factory()->create(['name' => 'Test Office']);
        
        // Get admin user for transactions
        $adminRole = Role::where('name', 'admin')->first();
        $adminUser = User::where('role_id', $adminRole->id)->first() ?? 
            User::factory()->create([
                'role_id' => $adminRole->id,
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
            ]);

        return [
            'air_manifest' => $airManifest,
            'sea_manifest' => $seaManifest,
            'shipper' => $shipper,
            'office' => $office,
            'admin_user' => $adminUser,
        ];
    }

    /**
     * Create comprehensive customer scenarios
     */
    private function createCustomerScenarios(array $baseData): array
    {
        $this->command->info('👥 Creating customer scenarios...');
        
        $customerRole = Role::where('name', 'customer')->first();
        $customers = [];

        // Scenario 1: New Customer (Zero Balances)
        $customers['new_customer'] = User::updateOrCreate(
            ['email' => 'john.new@test.com'],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'John',
                'last_name' => 'NewCustomer',
                'account_balance' => 0.00,
                'credit_balance' => 0.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Scenario 2: Customer with Positive Account Balance
        $customers['positive_balance'] = User::updateOrCreate(
            ['email' => 'sarah.positive@test.com'],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'Sarah',
                'last_name' => 'PositiveBalance',
                'account_balance' => 500.00,
                'credit_balance' => 0.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $this->createTransaction($customers['positive_balance'], 'payment', 500.00, 'Initial account deposit', $baseData['admin_user']);

        // Scenario 3: Customer with Credit Balance Only
        $customers['credit_only'] = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'Mike',
            'last_name' => 'CreditOnly',
            'email' => 'mike.credit@test.com',
            'account_balance' => 0.00,
            'credit_balance' => 125.75,
        ]);
        $this->createTransaction($customers['credit_only'], 'credit', 125.75, 'Credit from previous overpayment', $baseData['admin_user'], 'credit_balance');

        // Scenario 4: Customer with Negative Balance (Owes Money)
        $customers['negative_balance'] = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'David',
            'last_name' => 'OwesMoneyCustomer',
            'email' => 'david.owes@test.com',
            'account_balance' => -150.50,
            'credit_balance' => 0.00,
        ]);
        $this->createTransaction($customers['negative_balance'], 'charge', 150.50, 'Outstanding package charges', $baseData['admin_user']);

        // Scenario 5: Customer with Both Account and Credit Balance
        $customers['mixed_balance'] = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'Lisa',
            'last_name' => 'MixedBalance',
            'email' => 'lisa.mixed@test.com',
            'account_balance' => 300.00,
            'credit_balance' => 75.25,
        ]);
        $this->createTransaction($customers['mixed_balance'], 'payment', 300.00, 'Account payment', $baseData['admin_user']);
        $this->createTransaction($customers['mixed_balance'], 'credit', 75.25, 'Overpayment credit', $baseData['admin_user'], 'credit_balance');

        // Scenario 6: High-Volume Customer
        $customers['high_volume'] = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'Robert',
            'last_name' => 'HighVolumeCustomer',
            'email' => 'robert.highvolume@test.com',
            'account_balance' => 1000.00,
            'credit_balance' => 200.00,
        ]);
        $this->createTransaction($customers['high_volume'], 'payment', 1000.00, 'Large account deposit', $baseData['admin_user']);
        $this->createTransaction($customers['high_volume'], 'credit', 200.00, 'Accumulated overpayment credits', $baseData['admin_user'], 'credit_balance');

        // Scenario 7: Customer with Transaction History
        $customers['transaction_history'] = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'Emma',
            'last_name' => 'TransactionHistory',
            'email' => 'emma.history@test.com',
            'account_balance' => 250.00,
            'credit_balance' => 50.00,
        ]);
        // Create multiple transactions for history
        $this->createTransaction($customers['transaction_history'], 'payment', 400.00, 'Initial payment', $baseData['admin_user']);
        $this->createTransaction($customers['transaction_history'], 'charge', 100.00, 'Package distribution #1', $baseData['admin_user']);
        $this->createTransaction($customers['transaction_history'], 'charge', 75.00, 'Package distribution #2', $baseData['admin_user']);
        $this->createTransaction($customers['transaction_history'], 'payment', 25.00, 'Partial payment', $baseData['admin_user']);
        $this->createTransaction($customers['transaction_history'], 'credit', 50.00, 'Overpayment from last distribution', $baseData['admin_user'], 'credit_balance');

        // Scenario 8: VIP Customer (Simba Powell - existing user)
        $existingCustomer = User::where('first_name', 'Simba')->where('last_name', 'Powell')->first();
        if ($existingCustomer) {
            $existingCustomer->update([
                'account_balance' => 2000.00,
                'credit_balance' => 500.00,
            ]);
            $customers['vip_customer'] = $existingCustomer;
            $this->createTransaction($customers['vip_customer'], 'payment', 2000.00, 'VIP account setup', $baseData['admin_user']);
            $this->createTransaction($customers['vip_customer'], 'credit', 500.00, 'VIP loyalty credit', $baseData['admin_user'], 'credit_balance');
        }

        return $customers;
    }

    /**
     * Create package scenarios for each customer
     */
    private function createPackageScenarios(array $customers, array $baseData): void
    {
        $this->command->info('📦 Creating package scenarios...');

        // New Customer: Single small package
        $this->createPackage($customers['new_customer'], $baseData, [
            'tracking_number' => 'NEW-001',
            'description' => 'Small Electronics - Phone Case',
            'weight' => 0.5,
            'status' => PackageStatus::READY,
            'freight_price' => 15.00,
            'clearance_fee' => 5.00,
            'storage_fee' => 2.00,
            'delivery_fee' => 3.00,
            // Total: $25.00
        ]);

        // Positive Balance Customer: Medium package
        $this->createPackage($customers['positive_balance'], $baseData, [
            'tracking_number' => 'POS-001',
            'description' => 'Clothing - Designer Dress',
            'weight' => 2.0,
            'status' => PackageStatus::READY,
            'freight_price' => 35.00,
            'clearance_fee' => 15.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 5.00,
            // Total: $60.00
        ]);

        // Credit Only Customer: Multiple packages
        $this->createPackage($customers['credit_only'], $baseData, [
            'tracking_number' => 'CRD-001',
            'description' => 'Books - Educational Set',
            'weight' => 3.5,
            'status' => PackageStatus::READY,
            'freight_price' => 25.00,
            'clearance_fee' => 8.00,
            'storage_fee' => 4.00,
            'delivery_fee' => 3.00,
            // Total: $40.00
        ]);
        $this->createPackage($customers['credit_only'], $baseData, [
            'tracking_number' => 'CRD-002',
            'description' => 'Home Decor - Picture Frames',
            'weight' => 2.2,
            'status' => PackageStatus::READY,
            'freight_price' => 20.00,
            'clearance_fee' => 6.00,
            'storage_fee' => 3.00,
            'delivery_fee' => 3.00,
            // Total: $32.00
        ]);

        // Negative Balance Customer: High-value package
        $this->createPackage($customers['negative_balance'], $baseData, [
            'tracking_number' => 'NEG-001',
            'description' => 'Electronics - Laptop Computer',
            'weight' => 5.0,
            'status' => PackageStatus::READY,
            'freight_price' => 60.00,
            'clearance_fee' => 40.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 8.00,
            'estimated_value' => 1200.00,
            // Total: $118.00
        ]);

        // Mixed Balance Customer: Various packages
        $this->createPackage($customers['mixed_balance'], $baseData, [
            'tracking_number' => 'MIX-001',
            'description' => 'Sports Equipment - Tennis Racket',
            'weight' => 1.8,
            'status' => PackageStatus::READY,
            'freight_price' => 30.00,
            'clearance_fee' => 12.00,
            'storage_fee' => 4.00,
            'delivery_fee' => 4.00,
            // Total: $50.00
        ]);
        $this->createPackage($customers['mixed_balance'], $baseData, [
            'tracking_number' => 'MIX-002',
            'description' => 'Beauty Products - Skincare Set',
            'weight' => 1.2,
            'status' => PackageStatus::CUSTOMS,
            'freight_price' => 25.00,
            'clearance_fee' => 0.00, // Not processed yet
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // High Volume Customer: Multiple packages with different statuses
        $statuses = [PackageStatus::READY, PackageStatus::READY, PackageStatus::CUSTOMS, PackageStatus::SHIPPED, PackageStatus::PROCESSING];
        for ($i = 1; $i <= 5; $i++) {
            $this->createPackage($customers['high_volume'], $baseData, [
                'tracking_number' => "HV-00{$i}",
                'description' => "Package {$i} - Various Items",
                'weight' => rand(10, 50) / 10, // 1.0 to 5.0
                'status' => $statuses[$i - 1],
                'freight_price' => rand(200, 800) / 10, // $20.00 to $80.00
                'clearance_fee' => $statuses[$i - 1] === PackageStatus::READY ? rand(50, 300) / 10 : 0,
                'storage_fee' => $statuses[$i - 1] === PackageStatus::READY ? rand(30, 100) / 10 : 0,
                'delivery_fee' => $statuses[$i - 1] === PackageStatus::READY ? rand(30, 80) / 10 : 0,
            ]);
        }

        // Transaction History Customer: Sea freight package
        $this->createPackage($customers['transaction_history'], $baseData, [
            'tracking_number' => 'SEA-001',
            'description' => 'Furniture - Dining Chair',
            'weight' => 15.0,
            'status' => PackageStatus::READY,
            'freight_price' => 80.00,
            'clearance_fee' => 25.00,
            'storage_fee' => 12.00,
            'delivery_fee' => 10.00,
            'container_type' => 'box',
            'length_inches' => 24,
            'width_inches' => 18,
            'height_inches' => 36,
            'cubic_feet' => 10.5,
        ], 'sea');

        // VIP Customer: Premium packages
        if (isset($customers['vip_customer'])) {
            $this->createPackage($customers['vip_customer'], $baseData, [
                'tracking_number' => 'VIP-001',
                'description' => 'Luxury Watch - Swiss Made',
                'weight' => 0.8,
                'status' => PackageStatus::READY,
                'freight_price' => 100.00,
                'clearance_fee' => 80.00,
                'storage_fee' => 15.00,
                'delivery_fee' => 10.00,
                'estimated_value' => 5000.00,
                // Total: $205.00
            ]);
            $this->createPackage($customers['vip_customer'], $baseData, [
                'tracking_number' => 'VIP-002',
                'description' => 'Designer Handbag - Limited Edition',
                'weight' => 1.5,
                'status' => PackageStatus::READY,
                'freight_price' => 75.00,
                'clearance_fee' => 60.00,
                'storage_fee' => 10.00,
                'delivery_fee' => 8.00,
                'estimated_value' => 3000.00,
                // Total: $153.00
            ]);
        }
    }

    /**
     * Create historical distribution scenarios
     */
    private function createHistoricalDistributions(array $customers, array $baseData): void
    {
        $this->command->info('📊 Creating historical distribution scenarios...');

        // Create some completed distributions to show history
        if (isset($customers['transaction_history'])) {
            // Create a historical package that was already distributed
            $historicalPackage = $this->createPackage($customers['transaction_history'], $baseData, [
                'tracking_number' => 'HIST-001',
                'description' => 'Electronics - Tablet (Delivered)',
                'weight' => 2.0,
                'status' => PackageStatus::DELIVERED,
                'freight_price' => 40.00,
                'clearance_fee' => 15.00,
                'storage_fee' => 5.00,
                'delivery_fee' => 5.00,
                // Total: $65.00
            ]);

            // Create historical distribution record
            $distribution = PackageDistribution::create([
                'receipt_number' => 'RCP' . now()->format('YmdHis') . '001',
                'customer_id' => $customers['transaction_history']->id,
                'distributed_by' => $baseData['admin_user']->id,
                'distributed_at' => now()->subDays(7),
                'total_amount' => 65.00,
                'amount_collected' => 70.00, // Overpayment
                'credit_applied' => 0.00,
                'payment_status' => 'paid',
                'receipt_path' => 'receipts/historical-001.pdf',
                'email_sent' => true,
                'email_sent_at' => now()->subDays(7),
            ]);

            // Create distribution item
            PackageDistributionItem::create([
                'distribution_id' => $distribution->id,
                'package_id' => $historicalPackage->id,
                'freight_price' => 40.00,
                'clearance_fee' => 15.00,
                'storage_fee' => 5.00,
                'delivery_fee' => 5.00,
                'total_cost' => 65.00,
            ]);
        }
    }

    /**
     * Create workflow test scenarios
     */
    private function createWorkflowScenarios(array $customers, array $baseData): void
    {
        $this->command->info('⚙️ Creating workflow test scenarios...');

        // Create packages in various workflow stages for testing
        $workflowCustomer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
            'first_name' => 'Workflow',
            'last_name' => 'TestCustomer',
            'email' => 'workflow.test@test.com',
            'account_balance' => 100.00,
            'credit_balance' => 25.00,
        ]);

        $workflowStatuses = [
            PackageStatus::PENDING => 'Pending Package - Just Received',
            PackageStatus::PROCESSING => 'Processing Package - Being Sorted',
            PackageStatus::SHIPPED => 'Shipped Package - In Transit',
            PackageStatus::CUSTOMS => 'Customs Package - Awaiting Clearance',
            PackageStatus::READY => 'Ready Package - Awaiting Pickup',
            PackageStatus::DELAYED => 'Delayed Package - Issue Occurred',
        ];

        $counter = 1;
        foreach ($workflowStatuses as $status => $description) {
            $this->createPackage($workflowCustomer, $baseData, [
                'tracking_number' => "WF-" . str_pad($counter, 3, '0', STR_PAD_LEFT),
                'description' => $description,
                'weight' => rand(10, 50) / 10,
                'status' => $status,
                'freight_price' => rand(150, 400) / 10,
                'clearance_fee' => $status === PackageStatus::READY ? rand(50, 150) / 10 : 0,
                'storage_fee' => $status === PackageStatus::READY ? rand(20, 60) / 10 : 0,
                'delivery_fee' => $status === PackageStatus::READY ? rand(20, 50) / 10 : 0,
            ]);
            $counter++;
        }
    }

    /**
     * Helper method to create a transaction
     */
    private function createTransaction(User $customer, string $type, float $amount, string $description, User $createdBy, string $balanceType = 'account_balance'): void
    {
        $balanceBefore = $balanceType === 'credit_balance' ? $customer->credit_balance : $customer->account_balance;
        $balanceAfter = $type === 'charge' ? $balanceBefore - $amount : $balanceBefore + $amount;

        $customer->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'reference_type' => 'seed_data',
            'reference_id' => null,
            'created_by' => $createdBy->id,
            'metadata' => [
                'scenario' => 'comprehensive_test_data',
                'balance_type' => $balanceType,
            ],
        ]);
    }

    /**
     * Helper method to create a package
     */
    private function createPackage(User $customer, array $baseData, array $packageData, string $manifestType = 'air'): Package
    {
        $manifest = $manifestType === 'sea' ? $baseData['sea_manifest'] : $baseData['air_manifest'];

        return Package::factory()->create(array_merge([
            'user_id' => $customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $baseData['shipper']->id,
            'office_id' => $baseData['office']->id,
        ], $packageData));
    }

    /**
     * Display test scenarios summary
     */
    private function displayTestScenarios(): void
    {
        $this->command->info('');
        $this->command->info('🎯 Test Scenarios Created:');
        $this->command->info('');
        
        $this->command->info('👥 Customer Balance Scenarios:');
        $this->command->info('  • New Customer (Zero balances) - 1 small package');
        $this->command->info('  • Positive Balance ($500) - 1 medium package');
        $this->command->info('  • Credit Only ($125.75) - 2 packages');
        $this->command->info('  • Negative Balance (-$150.50) - 1 high-value package');
        $this->command->info('  • Mixed Balance ($300 + $75.25 credit) - 2 packages');
        $this->command->info('  • High Volume ($1000 + $200 credit) - 5 packages');
        $this->command->info('  • Transaction History ($250 + $50 credit) - 1 sea package + history');
        $this->command->info('  • VIP Customer ($2000 + $500 credit) - 2 luxury packages');
        $this->command->info('');
        
        $this->command->info('📦 Package Scenarios:');
        $this->command->info('  • Small packages ($25 total cost)');
        $this->command->info('  • Medium packages ($50-60 total cost)');
        $this->command->info('  • High-value packages ($100+ total cost)');
        $this->command->info('  • Sea freight packages (with dimensions)');
        $this->command->info('  • Multiple packages per customer');
        $this->command->info('  • Various package statuses');
        $this->command->info('');
        
        $this->command->info('⚙️ Workflow Test Scenarios:');
        $this->command->info('  • Packages in all workflow stages (Pending → Delivered)');
        $this->command->info('  • Delayed packages for exception handling');
        $this->command->info('  • Historical distributions with receipts');
        $this->command->info('');
        
        $this->command->info('🧪 Testing Capabilities:');
        $this->command->info('  • Exact payment scenarios');
        $this->command->info('  • Overpayment handling');
        $this->command->info('  • Underpayment scenarios');
        $this->command->info('  • Credit balance application');
        $this->command->info('  • Negative balance recovery');
        $this->command->info('  • Mixed payment methods');
        $this->command->info('  • Bulk distributions');
        $this->command->info('  • Fee entry modal testing');
        $this->command->info('  • Dashboard balance display');
        $this->command->info('  • Transaction history verification');
        $this->command->info('');
        
        $this->command->info('🚀 Ready for comprehensive testing!');
    }
}