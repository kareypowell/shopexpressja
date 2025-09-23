<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Models\PackageDistribution;
use App\Models\PackageDistributionItem;
use App\Enums\PackageStatus;
use App\Services\PackageConsolidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConsolidatedPackageTestDataSeeder extends Seeder
{
    private PackageConsolidationService $consolidationService;

    public function __construct()
    {
        $this->consolidationService = app(PackageConsolidationService::class);
    }

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating consolidated package test data...');
        
        // Get base data needed for consolidation scenarios
        $baseData = $this->getBaseData();
        
        // Create consolidation-specific customers
        $customers = $this->createConsolidationCustomers($baseData);
        
        // Create consolidation scenarios
        $this->createConsolidationScenarios($customers, $baseData);
        
        // Create historical consolidation data
        $this->createHistoricalConsolidations($customers, $baseData);
        
        // Create workflow test scenarios
        $this->createConsolidationWorkflowScenarios($customers, $baseData);
        
        $this->command->info('✅ Consolidated package test data created successfully!');
        $this->displayConsolidationScenarios();
    }

    /**
     * Get base data needed for consolidation scenarios
     */
    private function getBaseData(): array
    {
        // Get or create manifests
        $airManifest = Manifest::where('type', 'air')->first() ?? 
            Manifest::factory()->create(['type' => 'air', 'name' => 'Consolidation Test Air Manifest']);
        
        $seaManifest = Manifest::where('type', 'sea')->first() ?? 
            Manifest::factory()->create(['type' => 'sea', 'name' => 'Consolidation Test Sea Manifest']);
        
        // Get or create shippers and offices
        $shipper = Shipper::first() ?? Shipper::factory()->create(['name' => 'Consolidation Test Shipper']);
        $office = Office::first() ?? Office::factory()->create(['name' => 'Consolidation Test Office']);
        
        // Get admin user for consolidation operations
        $adminRole = Role::where('name', 'admin')->first();
        $adminUser = User::where('role_id', $adminRole->id)->first() ?? 
            User::factory()->create([
                'role_id' => $adminRole->id,
                'first_name' => 'Consolidation',
                'last_name' => 'Admin',
                'email' => 'consolidation.admin@test.com',
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
     * Create customers specifically for consolidation testing
     */
    private function createConsolidationCustomers(array $baseData): array
    {
        $this->command->info('👥 Creating consolidation test customers...');
        
        $customerRole = Role::where('name', 'customer')->first();
        $customers = [];

        // Generate unique email suffix to avoid conflicts with existing users
        $timestamp = now()->format('YmdHis') . rand(1000, 9999);
        
        // Customer 1: Multiple packages ready for consolidation
        $customers['consolidation_ready'] = User::firstOrCreate(
            ['email' => "consolidation.ready.{$timestamp}@test.com"],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'Ready',
                'last_name' => 'ForConsolidation',
                'account_balance' => 500.00,
                'credit_balance' => 0.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Customer 2: Has existing consolidated packages
        $customers['has_consolidated'] = User::firstOrCreate(
            ['email' => "has.consolidated.{$timestamp}@test.com"],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'Has',
                'last_name' => 'ConsolidatedPackages',
                'account_balance' => 750.00,
                'credit_balance' => 100.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Customer 3: Mixed individual and consolidated packages
        $customers['mixed_packages'] = User::firstOrCreate(
            ['email' => "mixed.packages.{$timestamp}@test.com"],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'Mixed',
                'last_name' => 'PackageTypes',
                'account_balance' => 1000.00,
                'credit_balance' => 50.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Customer 4: Large volume consolidation customer
        $customers['high_volume_consolidation'] = User::firstOrCreate(
            ['email' => "highvolume.consolidation.{$timestamp}@test.com"],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'HighVolume',
                'last_name' => 'ConsolidationCustomer',
                'account_balance' => 2000.00,
                'credit_balance' => 200.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Customer 5: Consolidation workflow testing
        $customers['workflow_testing'] = User::firstOrCreate(
            ['email' => "workflow.consolidation.{$timestamp}@test.com"],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'Workflow',
                'last_name' => 'ConsolidationTesting',
                'account_balance' => 800.00,
                'credit_balance' => 75.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        return $customers;
    }

    /**
     * Create consolidation scenarios
     */
    private function createConsolidationScenarios(array $customers, array $baseData): void
    {
        $this->command->info('📦 Creating consolidation scenarios...');

        // Scenario 1: Customer with packages ready for consolidation
        $readyPackages = $this->createPackagesForConsolidation($customers['consolidation_ready'], $baseData, [
            [
                'tracking_number' => 'CONS-READY-001',
                'description' => 'Electronics - Phone Accessories',
                'weight' => 1.2,
                'status' => PackageStatus::READY,
                'freight_price' => 25.00,
                'clearance_fee' => 8.00,
                'storage_fee' => 3.00,
                'delivery_fee' => 4.00,
            ],
            [
                'tracking_number' => 'CONS-READY-002',
                'description' => 'Clothing - T-Shirts',
                'weight' => 2.1,
                'status' => PackageStatus::READY,
                'freight_price' => 30.00,
                'clearance_fee' => 12.00,
                'storage_fee' => 4.00,
                'delivery_fee' => 4.00,
            ],
            [
                'tracking_number' => 'CONS-READY-003',
                'description' => 'Books - Technical Manuals',
                'weight' => 3.5,
                'status' => PackageStatus::READY,
                'freight_price' => 35.00,
                'clearance_fee' => 15.00,
                'storage_fee' => 5.00,
                'delivery_fee' => 5.00,
            ],
        ]);

        // Scenario 2: Customer with existing consolidated packages
        $this->createExistingConsolidatedPackages($customers['has_consolidated'], $baseData);

        // Scenario 3: Mixed individual and consolidated packages
        $this->createMixedPackageScenarios($customers['mixed_packages'], $baseData);

        // Scenario 4: High volume consolidation
        $this->createHighVolumeConsolidationScenario($customers['high_volume_consolidation'], $baseData);

        // Scenario 5: Workflow testing packages
        $this->createWorkflowTestingPackages($customers['workflow_testing'], $baseData);
    }

    /**
     * Create packages for consolidation testing
     */
    private function createPackagesForConsolidation(User $customer, array $baseData, array $packageSpecs): array
    {
        $packages = [];
        
        foreach ($packageSpecs as $spec) {
            // Ensure unique tracking numbers by adding timestamp suffix if not already unique
            if (isset($spec['tracking_number'])) {
                $baseTrackingNumber = $spec['tracking_number'];
                $suffix = now()->format('His') . rand(100, 999);
                $spec['tracking_number'] = $baseTrackingNumber . '-' . $suffix;
            }
            
            $packages[] = Package::factory()->create(array_merge([
                'user_id' => $customer->id,
                'manifest_id' => $baseData['air_manifest']->id,
                'shipper_id' => $baseData['shipper']->id,
                'office_id' => $baseData['office']->id,
            ], $spec));
        }
        
        return $packages;
    }

    /**
     * Create existing consolidated packages
     */
    private function createExistingConsolidatedPackages(User $customer, array $baseData): void
    {
        // Create individual packages first
        $packages1 = $this->createPackagesForConsolidation($customer, $baseData, [
            [
                'tracking_number' => 'CONS-001',
                'description' => 'Home Decor - Vases',
                'weight' => 2.5,
                'status' => PackageStatus::READY,
                'freight_price' => 40.00,
                'clearance_fee' => 15.00,
                'storage_fee' => 6.00,
                'delivery_fee' => 5.00,
            ],
            [
                'tracking_number' => 'CONS-002',
                'description' => 'Kitchen Items - Utensils',
                'weight' => 1.8,
                'status' => PackageStatus::READY,
                'freight_price' => 28.00,
                'clearance_fee' => 10.00,
                'storage_fee' => 4.00,
                'delivery_fee' => 4.00,
            ],
        ]);

        // Consolidate them using the service
        $packageIds = collect($packages1)->pluck('id')->toArray();
        $consolidatedPackage1 = $this->consolidationService->consolidatePackages(
            $packageIds,
            $baseData['admin_user'],
            ['notes' => 'Test consolidation - Home items']
        );

        // Create another set for a second consolidation
        $packages2 = $this->createPackagesForConsolidation($customer, $baseData, [
            [
                'tracking_number' => 'CONS-003',
                'description' => 'Sports Equipment - Gear',
                'weight' => 4.2,
                'status' => PackageStatus::READY,
                'freight_price' => 55.00,
                'clearance_fee' => 20.00,
                'storage_fee' => 8.00,
                'delivery_fee' => 7.00,
            ],
            [
                'tracking_number' => 'CONS-004',
                'description' => 'Outdoor Equipment - Camping',
                'weight' => 3.1,
                'status' => PackageStatus::READY,
                'freight_price' => 45.00,
                'clearance_fee' => 18.00,
                'storage_fee' => 6.00,
                'delivery_fee' => 6.00,
            ],
            [
                'tracking_number' => 'CONS-005',
                'description' => 'Fitness Accessories',
                'weight' => 2.0,
                'status' => PackageStatus::READY,
                'freight_price' => 32.00,
                'clearance_fee' => 12.00,
                'storage_fee' => 4.00,
                'delivery_fee' => 4.00,
            ],
        ]);

        $packageIds2 = collect($packages2)->pluck('id')->toArray();
        $consolidatedPackage2 = $this->consolidationService->consolidatePackages(
            $packageIds2,
            $baseData['admin_user'],
            ['notes' => 'Test consolidation - Sports and outdoor items']
        );
    }

    /**
     * Create mixed package scenarios
     */
    private function createMixedPackageScenarios(User $customer, array $baseData): void
    {
        // Create some individual packages
        $this->createPackagesForConsolidation($customer, $baseData, [
            [
                'tracking_number' => 'MIX-IND-001',
                'description' => 'Individual Package - Watch',
                'weight' => 0.5,
                'status' => PackageStatus::READY,
                'freight_price' => 20.00,
                'clearance_fee' => 8.00,
                'storage_fee' => 2.00,
                'delivery_fee' => 3.00,
            ],
            [
                'tracking_number' => 'MIX-IND-002',
                'description' => 'Individual Package - Jewelry',
                'weight' => 0.3,
                'status' => PackageStatus::CUSTOMS,
                'freight_price' => 15.00,
                'clearance_fee' => 0.00,
                'storage_fee' => 0.00,
                'delivery_fee' => 0.00,
            ],
        ]);

        // Create packages for consolidation
        $packagesForConsolidation = $this->createPackagesForConsolidation($customer, $baseData, [
            [
                'tracking_number' => 'MIX-CONS-001',
                'description' => 'Beauty Products - Skincare',
                'weight' => 1.5,
                'status' => PackageStatus::READY,
                'freight_price' => 28.00,
                'clearance_fee' => 10.00,
                'storage_fee' => 3.00,
                'delivery_fee' => 4.00,
            ],
            [
                'tracking_number' => 'MIX-CONS-002',
                'description' => 'Beauty Products - Makeup',
                'weight' => 1.2,
                'status' => PackageStatus::READY,
                'freight_price' => 25.00,
                'clearance_fee' => 9.00,
                'storage_fee' => 3.00,
                'delivery_fee' => 3.00,
            ],
        ]);

        // Consolidate the beauty products
        $packageIds = collect($packagesForConsolidation)->pluck('id')->toArray();
        $this->consolidationService->consolidatePackages(
            $packageIds,
            $baseData['admin_user'],
            ['notes' => 'Beauty products consolidation']
        );
    }

    /**
     * Create high volume consolidation scenario
     */
    private function createHighVolumeConsolidationScenario(User $customer, array $baseData): void
    {
        // Create multiple sets of packages for different consolidations
        $consolidationSets = [
            'electronics' => [
                ['tracking_number' => 'HV-ELEC-001', 'description' => 'Electronics - Laptop Accessories', 'weight' => 2.0, 'freight_price' => 45.00],
                ['tracking_number' => 'HV-ELEC-002', 'description' => 'Electronics - Phone Cases', 'weight' => 1.5, 'freight_price' => 30.00],
                ['tracking_number' => 'HV-ELEC-003', 'description' => 'Electronics - Cables', 'weight' => 1.0, 'freight_price' => 25.00],
                ['tracking_number' => 'HV-ELEC-004', 'description' => 'Electronics - Chargers', 'weight' => 1.2, 'freight_price' => 28.00],
            ],
            'clothing' => [
                ['tracking_number' => 'HV-CLOTH-001', 'description' => 'Clothing - Shirts', 'weight' => 2.5, 'freight_price' => 35.00],
                ['tracking_number' => 'HV-CLOTH-002', 'description' => 'Clothing - Pants', 'weight' => 3.0, 'freight_price' => 40.00],
                ['tracking_number' => 'HV-CLOTH-003', 'description' => 'Clothing - Shoes', 'weight' => 2.8, 'freight_price' => 38.00],
            ],
            'books' => [
                ['tracking_number' => 'HV-BOOK-001', 'description' => 'Books - Technical', 'weight' => 4.0, 'freight_price' => 50.00],
                ['tracking_number' => 'HV-BOOK-002', 'description' => 'Books - Fiction', 'weight' => 3.5, 'freight_price' => 45.00],
                ['tracking_number' => 'HV-BOOK-003', 'description' => 'Books - Educational', 'weight' => 3.8, 'freight_price' => 48.00],
                ['tracking_number' => 'HV-BOOK-004', 'description' => 'Books - Reference', 'weight' => 4.2, 'freight_price' => 52.00],
                ['tracking_number' => 'HV-BOOK-005', 'description' => 'Books - Magazines', 'weight' => 2.0, 'freight_price' => 30.00],
            ],
        ];

        foreach ($consolidationSets as $category => $packageSpecs) {
            // Add common fields to each package spec
            $fullPackageSpecs = array_map(function ($spec) {
                return array_merge($spec, [
                    'status' => PackageStatus::READY,
                    'clearance_fee' => round($spec['freight_price'] * 0.3, 2),
                    'storage_fee' => round($spec['weight'] * 1.5, 2),
                    'delivery_fee' => round($spec['freight_price'] * 0.15, 2),
                ]);
            }, $packageSpecs);

            // Create packages
            $packages = $this->createPackagesForConsolidation($customer, $baseData, $fullPackageSpecs);
            
            // Consolidate them
            $packageIds = collect($packages)->pluck('id')->toArray();
            $this->consolidationService->consolidatePackages(
                $packageIds,
                $baseData['admin_user'],
                ['notes' => "High volume consolidation - {$category} items"]
            );
        }
    }

    /**
     * Create workflow testing packages
     */
    private function createWorkflowTestingPackages(User $customer, array $baseData): void
    {
        // Create packages in different statuses for workflow testing
        $workflowPackages = [
            // Packages ready for consolidation
            [
                'tracking_number' => 'WF-READY-001',
                'description' => 'Workflow Test - Ready Package 1',
                'weight' => 2.0,
                'status' => PackageStatus::READY,
                'freight_price' => 35.00,
                'clearance_fee' => 12.00,
                'storage_fee' => 4.00,
                'delivery_fee' => 4.00,
            ],
            [
                'tracking_number' => 'WF-READY-002',
                'description' => 'Workflow Test - Ready Package 2',
                'weight' => 1.8,
                'status' => PackageStatus::READY,
                'freight_price' => 32.00,
                'clearance_fee' => 10.00,
                'storage_fee' => 3.00,
                'delivery_fee' => 4.00,
            ],
            // Packages in different statuses (not ready for consolidation)
            [
                'tracking_number' => 'WF-PROC-001',
                'description' => 'Workflow Test - Processing Package',
                'weight' => 2.5,
                'status' => PackageStatus::PROCESSING,
                'freight_price' => 40.00,
                'clearance_fee' => 0.00,
                'storage_fee' => 0.00,
                'delivery_fee' => 0.00,
            ],
            [
                'tracking_number' => 'WF-CUST-001',
                'description' => 'Workflow Test - Customs Package',
                'weight' => 3.0,
                'status' => PackageStatus::CUSTOMS,
                'freight_price' => 45.00,
                'clearance_fee' => 0.00,
                'storage_fee' => 0.00,
                'delivery_fee' => 0.00,
            ],
        ];

        $packages = $this->createPackagesForConsolidation($customer, $baseData, $workflowPackages);
        
        // Consolidate only the ready packages
        $readyPackages = collect($packages)->filter(function ($package) {
            return $package->status === PackageStatus::READY;
        });
        
        if ($readyPackages->count() >= 2) {
            $packageIds = $readyPackages->pluck('id')->toArray();
            $this->consolidationService->consolidatePackages(
                $packageIds,
                $baseData['admin_user'],
                ['notes' => 'Workflow testing consolidation']
            );
        }
    }

    /**
     * Create historical consolidation data
     */
    private function createHistoricalConsolidations(array $customers, array $baseData): void
    {
        $this->command->info('📊 Creating historical consolidation data...');

        // Create a customer with historical consolidation activity
        $customerRole = Role::where('name', 'customer')->first();
        $timestamp = now()->format('YmdHis') . rand(1000, 9999);
        $historicalCustomer = User::firstOrCreate(
            ['email' => "historical.consolidation.{$timestamp}@test.com"],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'Historical',
                'last_name' => 'ConsolidationCustomer',
                'account_balance' => 300.00,
                'credit_balance' => 25.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create packages that were consolidated and then distributed
        $historicalPackages = $this->createPackagesForConsolidation($historicalCustomer, $baseData, [
            [
                'tracking_number' => 'HIST-001',
                'description' => 'Historical Package 1 - Delivered',
                'weight' => 2.0,
                'status' => PackageStatus::DELIVERED,
                'freight_price' => 35.00,
                'clearance_fee' => 12.00,
                'storage_fee' => 4.00,
                'delivery_fee' => 4.00,
            ],
            [
                'tracking_number' => 'HIST-002',
                'description' => 'Historical Package 2 - Delivered',
                'weight' => 1.5,
                'status' => PackageStatus::DELIVERED,
                'freight_price' => 28.00,
                'clearance_fee' => 10.00,
                'storage_fee' => 3.00,
                'delivery_fee' => 3.00,
            ],
        ]);

        // Create consolidated package manually to simulate historical data
        $uniqueSuffix = now()->format('His') . rand(1000, 9999);
        $consolidatedPackage = ConsolidatedPackage::create([
            'consolidated_tracking_number' => 'CONS-' . now()->subDays(10)->format('Ymd') . '-' . $uniqueSuffix,
            'customer_id' => $historicalCustomer->id,
            'created_by' => $baseData['admin_user']->id,
            'total_weight' => 3.5,
            'total_quantity' => 2,
            'total_freight_price' => 63.00,
            'total_clearance_fee' => 22.00,
            'total_storage_fee' => 7.00,
            'total_delivery_fee' => 7.00,
            'status' => PackageStatus::DELIVERED,
            'consolidated_at' => now()->subDays(10),
            'is_active' => true,
            'notes' => 'Historical consolidation - delivered',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(5),
        ]);

        // Update packages to be part of this consolidation
        foreach ($historicalPackages as $package) {
            $package->update([
                'consolidated_package_id' => $consolidatedPackage->id,
                'is_consolidated' => true,
                'consolidated_at' => now()->subDays(10),
            ]);
        }

        // Create consolidation history entries
        ConsolidationHistory::create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'consolidated',
            'performed_by' => $baseData['admin_user']->id,
            'details' => [
                'package_count' => 2,
                'total_weight' => 3.5,
                'total_cost' => 99.00,
                'package_ids' => collect($historicalPackages)->pluck('id')->toArray(),
                'tracking_numbers' => collect($historicalPackages)->pluck('tracking_number')->toArray(),
            ],
            'performed_at' => now()->subDays(10),
        ]);

        ConsolidationHistory::create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'status_changed',
            'performed_by' => $baseData['admin_user']->id,
            'details' => [
                'old_status' => PackageStatus::READY,
                'new_status' => PackageStatus::DELIVERED,
                'package_count' => 2,
                'reason' => 'Package distribution completed',
            ],
            'performed_at' => now()->subDays(5),
        ]);

        // Create a distribution record for this historical consolidation
        $uniqueReceiptSuffix = now()->format('His') . rand(1000, 9999);
        $distribution = PackageDistribution::create([
            'receipt_number' => 'RCP' . now()->subDays(5)->format('Ymd') . $uniqueReceiptSuffix,
            'customer_id' => $historicalCustomer->id,
            'distributed_by' => $baseData['admin_user']->id,
            'distributed_at' => now()->subDays(5),
            'total_amount' => 99.00,
            'amount_collected' => 100.00,
            'credit_applied' => 0.00,
            'account_balance_applied' => 0.00,
            'payment_status' => 'paid',
            'receipt_path' => 'receipts/consolidated-historical-001.pdf',
            'email_sent' => true,
            'email_sent_at' => now()->subDays(5),
            'notes' => 'Historical consolidated package distribution',
        ]);

        // Create distribution items for each package in the consolidation
        foreach ($historicalPackages as $package) {
            PackageDistributionItem::create([
                'distribution_id' => $distribution->id,
                'package_id' => $package->id,
                'freight_price' => $package->freight_price,
                'clearance_fee' => $package->clearance_fee,
                'storage_fee' => $package->storage_fee,
                'delivery_fee' => $package->delivery_fee,
                'total_cost' => $package->freight_price + $package->clearance_fee + $package->storage_fee + $package->delivery_fee,
            ]);
        }
    }

    /**
     * Create consolidation workflow scenarios
     */
    private function createConsolidationWorkflowScenarios(array $customers, array $baseData): void
    {
        $this->command->info('⚙️ Creating consolidation workflow scenarios...');

        // Create a customer for testing unconsolidation
        $customerRole = Role::where('name', 'customer')->first();
        $timestamp = now()->format('YmdHis') . rand(1000, 9999);
        $unconsolidationCustomer = User::firstOrCreate(
            ['email' => "unconsolidation.test.{$timestamp}@test.com"],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'Unconsolidation',
                'last_name' => 'TestCustomer',
                'account_balance' => 400.00,
                'credit_balance' => 0.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create packages and consolidate them
        $packagesForUnconsolidation = $this->createPackagesForConsolidation($unconsolidationCustomer, $baseData, [
            [
                'tracking_number' => 'UNCONS-001',
                'description' => 'Package for Unconsolidation Test 1',
                'weight' => 2.2,
                'status' => PackageStatus::READY,
                'freight_price' => 38.00,
                'clearance_fee' => 14.00,
                'storage_fee' => 5.00,
                'delivery_fee' => 5.00,
            ],
            [
                'tracking_number' => 'UNCONS-002',
                'description' => 'Package for Unconsolidation Test 2',
                'weight' => 1.8,
                'status' => PackageStatus::READY,
                'freight_price' => 32.00,
                'clearance_fee' => 11.00,
                'storage_fee' => 4.00,
                'delivery_fee' => 4.00,
            ],
            [
                'tracking_number' => 'UNCONS-003',
                'description' => 'Package for Unconsolidation Test 3',
                'weight' => 2.5,
                'status' => PackageStatus::READY,
                'freight_price' => 42.00,
                'clearance_fee' => 16.00,
                'storage_fee' => 6.00,
                'delivery_fee' => 6.00,
            ],
        ]);

        // Consolidate them
        $packageIds = collect($packagesForUnconsolidation)->pluck('id')->toArray();
        $consolidatedForUnconsolidation = $this->consolidationService->consolidatePackages(
            $packageIds,
            $baseData['admin_user'],
            ['notes' => 'Test consolidation for unconsolidation testing']
        );

        // Create some additional test scenarios with different statuses
        $this->createStatusTestScenarios($baseData);
    }

    /**
     * Create test scenarios for different consolidation statuses
     */
    private function createStatusTestScenarios(array $baseData): void
    {
        $customerRole = Role::where('name', 'customer')->first();
        
        // Customer for status testing
        $timestamp = now()->format('YmdHis') . rand(1000, 9999);
        $statusCustomer = User::firstOrCreate(
            ['email' => "status.test.{$timestamp}@test.com"],
            [
                'role_id' => $customerRole->id,
                'first_name' => 'Status',
                'last_name' => 'TestCustomer',
                'account_balance' => 600.00,
                'credit_balance' => 0.00,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create consolidations in different statuses
        $statusScenarios = [
            PackageStatus::PROCESSING => 'Processing consolidation',
            PackageStatus::SHIPPED => 'Shipped consolidation',
            PackageStatus::CUSTOMS => 'Customs consolidation',
            PackageStatus::READY => 'Ready consolidation',
        ];

        $counter = 1;
        foreach ($statusScenarios as $status => $description) {
            $packages = $this->createPackagesForConsolidation($statusCustomer, $baseData, [
                [
                    'tracking_number' => "STATUS-{$counter}-001",
                    'description' => "{$description} - Package 1",
                    'weight' => 2.0,
                    'status' => $status,
                    'freight_price' => 35.00,
                    'clearance_fee' => $status === PackageStatus::READY ? 12.00 : 0.00,
                    'storage_fee' => $status === PackageStatus::READY ? 4.00 : 0.00,
                    'delivery_fee' => $status === PackageStatus::READY ? 4.00 : 0.00,
                ],
                [
                    'tracking_number' => "STATUS-{$counter}-002",
                    'description' => "{$description} - Package 2",
                    'weight' => 1.5,
                    'status' => $status,
                    'freight_price' => 28.00,
                    'clearance_fee' => $status === PackageStatus::READY ? 10.00 : 0.00,
                    'storage_fee' => $status === PackageStatus::READY ? 3.00 : 0.00,
                    'delivery_fee' => $status === PackageStatus::READY ? 3.00 : 0.00,
                ],
            ]);

            // Consolidate packages
            $packageIds = collect($packages)->pluck('id')->toArray();
            $consolidatedPackage = $this->consolidationService->consolidatePackages(
                $packageIds,
                $baseData['admin_user'],
                ['notes' => $description]
            );

            $counter++;
        }
    }

    /**
     * Display consolidation scenarios summary
     */
    private function displayConsolidationScenarios(): void
    {
        $this->command->info('');
        $this->command->info('🎯 Consolidation Test Scenarios Created:');
        $this->command->info('');
        
        $this->command->info('👥 Consolidation Customer Scenarios:');
        $this->command->info('  • Ready for Consolidation - 3 packages ready to be consolidated');
        $this->command->info('  • Has Consolidated Packages - 2 existing consolidations (2 + 3 packages)');
        $this->command->info('  • Mixed Package Types - Individual packages + 1 consolidation');
        $this->command->info('  • High Volume Consolidation - 3 consolidations (Electronics, Clothing, Books)');
        $this->command->info('  • Workflow Testing - Mixed status packages with 1 consolidation');
        $this->command->info('  • Historical Customer - 1 delivered consolidation with distribution history');
        $this->command->info('  • Unconsolidation Testing - 1 consolidation ready for unconsolidation');
        $this->command->info('  • Status Testing - Consolidations in different statuses');
        $this->command->info('');
        
        $this->command->info('📦 Consolidation Package Scenarios:');
        $this->command->info('  • Individual packages ready for consolidation');
        $this->command->info('  • Existing consolidated packages in various statuses');
        $this->command->info('  • Mixed individual and consolidated packages');
        $this->command->info('  • High volume consolidations (4-5 packages each)');
        $this->command->info('  • Historical consolidated packages (delivered status)');
        $this->command->info('  • Packages in different workflow stages');
        $this->command->info('');
        
        $this->command->info('📊 Consolidation History & Audit:');
        $this->command->info('  • Consolidation action history');
        $this->command->info('  • Status change history');
        $this->command->info('  • Distribution history for consolidated packages');
        $this->command->info('  • Unconsolidation scenarios');
        $this->command->info('');
        
        $this->command->info('🧪 Testing Capabilities:');
        $this->command->info('  • Package consolidation workflow');
        $this->command->info('  • Consolidated package distribution');
        $this->command->info('  • Consolidated package notifications');
        $this->command->info('  • Search within consolidated packages');
        $this->command->info('  • Consolidation toggle functionality');
        $this->command->info('  • Unconsolidation workflow');
        $this->command->info('  • Status synchronization');
        $this->command->info('  • Audit trail verification');
        $this->command->info('  • Performance with large consolidations');
        $this->command->info('  • Mixed package type handling');
        $this->command->info('');
        
        $this->command->info('🚀 Ready for comprehensive consolidation testing!');
    }
}