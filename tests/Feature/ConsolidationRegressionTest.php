<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\PackageDistribution;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use App\Models\Rate;
use App\Enums\PackageStatus;
use App\Services\PackageConsolidationService;
use App\Services\PackageDistributionService;
use App\Services\PackageNotificationService;
use App\Services\DashboardAnalyticsService;
use App\Http\Livewire\Package as PackageComponent;
use App\Http\Livewire\PackageDistribution as PackageDistributionComponent;
use App\Http\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsolidationRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerUser;
    protected $consolidationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 1000.00
        ]);

        // Initialize services
        $this->consolidationService = app(PackageConsolidationService::class);
    }

    /** @test */
    public function individual_package_management_still_works_with_consolidation_feature()
    {
        // Create individual packages (not consolidated)
        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();
        $manifest = Manifest::factory()->create([
            'office_id' => $office->id,
            'shipper_id' => $shipper->id
        ]);

        $individualPackages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'manifest_id' => $manifest->id,
            'office_id' => $office->id,
            'status' => PackageStatus::READY,
            'weight' => 2.0,
            'freight_price' => 25.00
        ]);

        // Test that individual packages work normally
        $this->actingAs($this->adminUser);

        // Test package listing
        $component = Livewire::test(PackageComponent::class)
            ->assertSee($individualPackages->first()->tracking_number)
            ->assertSee($individualPackages->first()->weight . ' lbs')
            ->assertSee('$' . $individualPackages->first()->freight_price);

        // Test package status updates
        $firstPackage = $individualPackages->first();
        $firstPackage->update(['status' => PackageStatus::SHIPPED]);

        $this->assertEquals(PackageStatus::SHIPPED, $firstPackage->fresh()->status);

        // Test package distribution
        $distributionService = app(PackageDistributionService::class);
        $readyPackages = $individualPackages->where('status', PackageStatus::READY);

        if ($readyPackages->count() > 0) {
            $readyPackages->each(function ($package) {
                $package->update(['status' => PackageStatus::READY_FOR_PICKUP]);
            });

            $distributionResult = $distributionService->distributePackages(
                $readyPackages->pluck('id')->toArray(),
                $readyPackages->sum('freight_price'),
                $this->adminUser
            );

            $this->assertTrue($distributionResult['success']);
        }

        // Verify individual packages are not affected by consolidation features
        $individualPackages->each(function ($package) {
            $package->refresh();
            $this->assertNull($package->consolidated_package_id);
            $this->assertFalse($package->is_consolidated);
        });
    }

    /** @test */
    public function dashboard_analytics_work_correctly_with_mixed_packages()
    {
        // Create mix of individual and consolidated packages
        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();
        $manifest = Manifest::factory()->create([
            'office_id' => $office->id,
            'shipper_id' => $shipper->id
        ]);

        // Individual packages
        $individualPackages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'manifest_id' => $manifest->id,
            'status' => PackageStatus::DELIVERED,
            'freight_price' => 30.00
        ]);

        // Packages to be consolidated
        $packagesToConsolidate = Package::factory()->count(4)->create([
            'user_id' => $this->customerUser->id,
            'manifest_id' => $manifest->id,
            'status' => PackageStatus::READY,
            'freight_price' => 25.00
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate some packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packagesToConsolidate->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Mark consolidated package as delivered
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::DELIVERED,
            $this->adminUser
        );

        // Test dashboard analytics
        $analyticsService = app(DashboardAnalyticsService::class);
        $metrics = $analyticsService->getPackageMetrics();

        // Should count all packages (individual + consolidated)
        $this->assertEquals(7, $metrics['total_packages']); // 3 individual + 4 consolidated
        $this->assertEquals(7, $metrics['delivered_packages']); // All delivered

        // Revenue should include both types
        $expectedRevenue = (3 * 30.00) + (4 * 25.00); // Individual + consolidated
        $this->assertEquals($expectedRevenue, $metrics['total_revenue']);

        // Test dashboard component
        $this->actingAs($this->customerUser);
        $dashboardComponent = Livewire::test(Dashboard::class)
            ->assertSee('7') // Total packages
            ->assertSee('$' . $expectedRevenue); // Total revenue
    }

    /** @test */
    public function package_search_works_with_both_individual_and_consolidated_packages()
    {
        // Create packages with specific tracking numbers
        $individualPackage = Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'tracking_number' => 'INDIVIDUAL-001',
            'status' => PackageStatus::READY,
            'description' => 'Individual test package'
        ]);

        $packagesToConsolidate = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY,
            'tracking_number' => function () {
                static $counter = 1;
                return 'CONSOLIDATED-' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
            }
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packagesToConsolidate->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Test search functionality
        $component = Livewire::test(PackageComponent::class);

        // Search for individual package
        $component->set('search', 'INDIVIDUAL-001')
                  ->assertSee('INDIVIDUAL-001')
                  ->assertSee('Individual test package');

        // Search for consolidated package by individual tracking number
        $component->set('search', 'CONSOLIDATED-001')
                  ->assertSee('CONSOLIDATED-001');

        // Search for consolidated package by consolidated tracking number
        $component->set('search', $consolidatedPackage->consolidated_tracking_number)
                  ->assertSee($consolidatedPackage->consolidated_tracking_number);

        // Clear search should show all packages
        $component->set('search', '')
                  ->assertSee('INDIVIDUAL-001')
                  ->assertSee('CONSOLIDATED-001')
                  ->assertSee('CONSOLIDATED-002');
    }

    /** @test */
    public function package_distribution_component_works_with_mixed_packages()
    {
        // Create individual and consolidated packages ready for pickup
        $individualPackage = Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY_FOR_PICKUP,
            'freight_price' => 35.00
        ]);

        $packagesToConsolidate = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY,
            'freight_price' => 20.00
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages and mark ready for pickup
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packagesToConsolidate->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::READY_FOR_PICKUP,
            $this->adminUser
        );

        // Test distribution component
        $component = Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customerUser->id)
            ->call('loadCustomerPackages');

        // Should show both individual and consolidated packages
        $component->assertSee($individualPackage->tracking_number)
                  ->assertSee($consolidatedPackage->consolidated_tracking_number)
                  ->assertSee('$35.00') // Individual package cost
                  ->assertSee('$40.00'); // Consolidated package total cost

        // Test distribution of mixed packages
        $component->set('selectedPackages', [
                      $individualPackage->id,
                      $consolidatedPackage->id
                  ])
                  ->set('amountCollected', 75.00)
                  ->call('distributePackages');

        $component->assertSee('Packages distributed successfully');

        // Verify both types were distributed
        $individualPackage->refresh();
        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::DELIVERED, $individualPackage->status);
        $this->assertEquals(PackageStatus::DELIVERED, $consolidatedPackage->status);
    }

    /** @test */
    public function manifest_workflow_works_with_mixed_packages()
    {
        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();
        $manifest = Manifest::factory()->create([
            'office_id' => $office->id,
            'shipper_id' => $shipper->id
        ]);

        // Create individual packages in manifest
        $individualPackages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'manifest_id' => $manifest->id,
            'office_id' => $office->id,
            'status' => PackageStatus::PROCESSING,
            'freight_price' => 30.00
        ]);

        // Create packages to consolidate in same manifest
        $packagesToConsolidate = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'manifest_id' => $manifest->id,
            'office_id' => $office->id,
            'status' => PackageStatus::PROCESSING,
            'freight_price' => 25.00
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate some packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packagesToConsolidate->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Test manifest totals include both individual and consolidated
        $manifestPackages = Package::where('manifest_id', $manifest->id)->get();
        $manifestTotal = $manifestPackages->sum('freight_price');
        $expectedTotal = (2 * 30.00) + (3 * 25.00); // Individual + consolidated

        $this->assertEquals($expectedTotal, $manifestTotal);

        // Test manifest status updates affect both types
        $manifestPackages->each(function ($package) {
            $package->update(['status' => PackageStatus::IN_TRANSIT]);
        });

        // Verify individual packages updated
        $individualPackages->each(function ($package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::IN_TRANSIT, $package->status);
        });

        // Verify consolidated package updated
        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::IN_TRANSIT, $consolidatedPackage->status);
    }

    /** @test */
    public function notification_system_works_with_mixed_packages()
    {
        // Create individual package
        $individualPackage = Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY
        ]);

        // Create packages to consolidate
        $packagesToConsolidate = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packagesToConsolidate->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];

        $notificationService = app(PackageNotificationService::class);

        // Test individual package notification
        $individualResult = $notificationService->sendStatusNotification(
            $individualPackage,
            PackageStatus::SHIPPED,
            $this->adminUser
        );

        $this->assertTrue($individualResult['success']);

        // Test consolidated package notification
        $consolidatedResult = $notificationService->sendConsolidatedStatusNotification(
            $consolidatedPackage,
            PackageStatus::SHIPPED
        );

        $this->assertTrue($consolidatedResult['success']);

        // Verify both notification types work independently
        $individualPackage->update(['status' => PackageStatus::SHIPPED]);
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::SHIPPED,
            $this->adminUser
        );

        $this->assertEquals(PackageStatus::SHIPPED, $individualPackage->fresh()->status);
        $this->assertEquals(PackageStatus::SHIPPED, $consolidatedPackage->fresh()->status);
    }

    /** @test */
    public function customer_balance_calculations_work_with_mixed_packages()
    {
        // Create individual package
        $individualPackage = Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY_FOR_PICKUP,
            'freight_price' => 40.00,
            'clearance_fee' => 5.00,
            'storage_fee' => 2.00,
            'delivery_fee' => 8.00
        ]);

        // Create packages to consolidate
        $packagesToConsolidate = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY,
            'freight_price' => 30.00,
            'clearance_fee' => 4.00,
            'storage_fee' => 1.50,
            'delivery_fee' => 6.00
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packagesToConsolidate->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::READY_FOR_PICKUP,
            $this->adminUser
        );

        $distributionService = app(PackageDistributionService::class);

        // Distribute individual package
        $individualCost = $individualPackage->freight_price + $individualPackage->clearance_fee + 
                         $individualPackage->storage_fee + $individualPackage->delivery_fee;

        $individualResult = $distributionService->distributePackages(
            [$individualPackage->id],
            $individualCost,
            $this->adminUser,
            ['use_account_balance' => true]
        );

        $this->assertTrue($individualResult['success']);

        // Check balance after individual distribution
        $this->customerUser->refresh();
        $expectedBalance = 1000.00 - $individualCost;
        $this->assertEquals($expectedBalance, $this->customerUser->account_balance);

        // Distribute consolidated package
        $consolidatedCost = $consolidatedPackage->total_freight_price + 
                           $consolidatedPackage->total_clearance_fee + 
                           $consolidatedPackage->total_storage_fee + 
                           $consolidatedPackage->total_delivery_fee;

        $consolidatedResult = $distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            $consolidatedCost,
            $this->adminUser,
            ['use_account_balance' => true]
        );

        $this->assertTrue($consolidatedResult['success']);

        // Check final balance
        $this->customerUser->refresh();
        $finalExpectedBalance = $expectedBalance - $consolidatedCost;
        $this->assertEquals($finalExpectedBalance, $this->customerUser->account_balance);
    }

    /** @test */
    public function existing_api_endpoints_work_with_consolidation_feature()
    {
        // Create individual package
        $individualPackage = Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY
        ]);

        // Create consolidated packages
        $packagesToConsolidate = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate packages
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packagesToConsolidate->pluck('id')->toArray(),
            $this->adminUser
        );

        // Test package listing API (if exists)
        $response = $this->get('/api/packages');
        if ($response->status() !== 404) { // Only test if API exists
            $response->assertStatus(200);
            $data = $response->json();
            
            // Should include both individual and consolidated packages
            $this->assertGreaterThanOrEqual(3, count($data['packages'] ?? $data));
        }

        // Test customer packages API (if exists)
        $this->actingAs($this->customerUser);
        $response = $this->get('/api/customer/packages');
        if ($response->status() !== 404) { // Only test if API exists
            $response->assertStatus(200);
            $data = $response->json();
            
            // Should show customer's packages including consolidated ones
            $this->assertGreaterThanOrEqual(3, count($data['packages'] ?? $data));
        }
    }

    /** @test */
    public function existing_reports_include_consolidation_data()
    {
        // Create data for reporting
        $individualPackages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::DELIVERED,
            'freight_price' => 35.00,
            'created_at' => now()->subDays(5)
        ]);

        $packagesToConsolidate = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => PackageStatus::READY,
            'freight_price' => 25.00,
            'created_at' => now()->subDays(3)
        ]);

        $this->actingAs($this->adminUser);

        // Consolidate and deliver
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packagesToConsolidate->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $consolidationResult['consolidated_package'];
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::DELIVERED,
            $this->adminUser
        );

        // Test weekly report includes both types
        $weeklyPackages = Package::where('created_at', '>=', now()->subWeek())
            ->where('status', PackageStatus::DELIVERED)
            ->get();

        $this->assertEquals(5, $weeklyPackages->count()); // 2 individual + 3 consolidated

        // Test revenue report includes both types
        $weeklyRevenue = Package::where('created_at', '>=', now()->subWeek())
            ->where('status', PackageStatus::DELIVERED)
            ->sum('freight_price');

        $expectedRevenue = (2 * 35.00) + (3 * 25.00);
        $this->assertEquals($expectedRevenue, $weeklyRevenue);

        // Test customer report includes consolidated packages
        $customerPackages = Package::where('user_id', $this->customerUser->id)
            ->where('status', PackageStatus::DELIVERED)
            ->get();

        $this->assertEquals(5, $customerPackages->count());
    }
}