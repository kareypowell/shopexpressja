<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PackageConsolidationService;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageConsolidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $consolidationService;
    protected $customer;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->consolidationService = new PackageConsolidationService();
        
        // Create test users
        $this->customer = User::factory()->create();
        $this->admin = User::factory()->create();
    }

    /** @test */
    public function it_can_consolidate_packages_successfully()
    {
        // Create packages for the same customer
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'weight' => 10.5,
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 15.00,
            'delivery_fee' => 10.00,
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        $result = $this->consolidationService->consolidatePackages($packageIds, $this->admin);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('consolidated_package', $result);
        $this->assertEquals('Packages consolidated successfully', $result['message']);

        // Verify consolidated package was created
        $consolidatedPackage = $result['consolidated_package'];
        $this->assertInstanceOf(ConsolidatedPackage::class, $consolidatedPackage);
        $this->assertEquals($this->customer->id, $consolidatedPackage->customer_id);
        $this->assertEquals($this->admin->id, $consolidatedPackage->created_by);
        $this->assertTrue($consolidatedPackage->is_active);

        // Verify totals are calculated correctly
        $this->assertEquals(31.5, $consolidatedPackage->total_weight); // 3 * 10.5
        $this->assertEquals(3, $consolidatedPackage->total_quantity);
        $this->assertEquals(300.00, $consolidatedPackage->total_freight_price); // 3 * 100
        $this->assertEquals(75.00, $consolidatedPackage->total_customs_duty); // 3 * 25
        $this->assertEquals(45.00, $consolidatedPackage->total_storage_fee); // 3 * 15
        $this->assertEquals(30.00, $consolidatedPackage->total_delivery_fee); // 3 * 10

        // Verify individual packages are updated
        $packages->each(function ($package) use ($consolidatedPackage) {
            $package->refresh();
            $this->assertEquals($consolidatedPackage->id, $package->consolidated_package_id);
            $this->assertTrue($package->is_consolidated);
            $this->assertNotNull($package->consolidated_at);
        });
    }

    /** @test */
    public function it_generates_unique_consolidated_tracking_numbers()
    {
        $packages1 = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);
        $packages2 = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        $result1 = $this->consolidationService->consolidatePackages(
            $packages1->pluck('id')->toArray(), 
            $this->admin
        );
        $result2 = $this->consolidationService->consolidatePackages(
            $packages2->pluck('id')->toArray(), 
            $this->admin
        );

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);

        $trackingNumber1 = $result1['consolidated_package']->consolidated_tracking_number;
        $trackingNumber2 = $result2['consolidated_package']->consolidated_tracking_number;

        $this->assertNotEquals($trackingNumber1, $trackingNumber2);
        $this->assertStringStartsWith('CONS-', $trackingNumber1);
        $this->assertStringStartsWith('CONS-', $trackingNumber2);
    }

    /** @test */
    public function it_fails_to_consolidate_packages_from_different_customers()
    {
        $customer2 = User::factory()->create();
        
        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $customer2->id,
            'status' => PackageStatus::READY,
        ]);

        $result = $this->consolidationService->consolidatePackages(
            [$package1->id, $package2->id], 
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('same customer', $result['message']);
    }

    /** @test */
    public function it_fails_to_consolidate_less_than_two_packages()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        $result = $this->consolidationService->consolidatePackages([$package->id], $this->admin);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('At least 2 packages', $result['message']);
    }

    /** @test */
    public function it_fails_to_consolidate_already_consolidated_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create(['customer_id' => $this->customer->id]);
        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => PackageStatus::READY,
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        $result = $this->consolidationService->consolidatePackages(
            [$package1->id, $package2->id], 
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already consolidated', $result['message']);
    }

    /** @test */
    public function it_can_unconsolidate_packages_successfully()
    {
        // First consolidate packages
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(), 
            $this->admin
        );
        
        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Now unconsolidate
        $result = $this->consolidationService->unconsolidatePackages($consolidatedPackage, $this->admin);

        $this->assertTrue($result['success']);
        $this->assertEquals('Packages unconsolidated successfully', $result['message']);

        // Verify consolidated package is marked inactive
        $consolidatedPackage->refresh();
        $this->assertFalse($consolidatedPackage->is_active);
        $this->assertNotNull($consolidatedPackage->unconsolidated_at);

        // Verify individual packages are restored
        $packages->each(function ($package) {
            $package->refresh();
            $this->assertNull($package->consolidated_package_id);
            $this->assertFalse($package->is_consolidated);
            $this->assertNull($package->consolidated_at);
        });
    }

    /** @test */
    public function it_fails_to_unconsolidate_delivered_packages()
    {
        // Create consolidated package with delivered status
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => PackageStatus::DELIVERED,
        ]);
        
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => PackageStatus::DELIVERED,
        ]);

        $result = $this->consolidationService->unconsolidatePackages($consolidatedPackage, $this->admin);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be unconsolidated', $result['message']);
    }

    /** @test */
    public function it_validates_consolidation_correctly()
    {
        // Test valid consolidation
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        $result = $this->consolidationService->validateConsolidation($packages->pluck('id')->toArray());
        $this->assertTrue($result['valid']);

        // Test invalid consolidation - different customers
        $customer2 = User::factory()->create();
        $package1 = Package::factory()->create(['user_id' => $this->customer->id]);
        $package2 = Package::factory()->create(['user_id' => $customer2->id]);

        $result = $this->consolidationService->validateConsolidation([$package1->id, $package2->id]);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('same customer', $result['message']);

        // Test invalid consolidation - already consolidated
        $consolidatedPackage = ConsolidatedPackage::factory()->create(['customer_id' => $this->customer->id]);
        $consolidatedPkg = Package::factory()->create([
            'user_id' => $this->customer->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
        ]);
        $normalPkg = Package::factory()->create(['user_id' => $this->customer->id]);

        $result = $this->consolidationService->validateConsolidation([$consolidatedPkg->id, $normalPkg->id]);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('already consolidated', $result['message']);
    }

    /** @test */
    public function it_calculates_consolidated_totals_correctly()
    {
        $packages = collect([
            Package::factory()->make([
                'weight' => 10.5,
                'freight_price' => 100.00,
                'customs_duty' => 25.00,
                'storage_fee' => 15.00,
                'delivery_fee' => 10.00,
            ]),
            Package::factory()->make([
                'weight' => 5.2,
                'freight_price' => 50.00,
                'customs_duty' => 12.50,
                'storage_fee' => 7.50,
                'delivery_fee' => 5.00,
            ]),
        ]);

        $totals = $this->consolidationService->calculateConsolidatedTotals($packages);

        $this->assertEquals(15.7, $totals['weight']);
        $this->assertEquals(2, $totals['quantity']);
        $this->assertEquals(150.00, $totals['freight_price']);
        $this->assertEquals(37.50, $totals['customs_duty']);
        $this->assertEquals(22.50, $totals['storage_fee']);
        $this->assertEquals(15.00, $totals['delivery_fee']);
        $this->assertEquals(225.00, $totals['total_cost']);
    }

    /** @test */
    public function it_generates_consolidated_tracking_number_with_correct_format()
    {
        $trackingNumber = $this->consolidationService->generateConsolidatedTrackingNumber();

        $this->assertStringStartsWith('CONS-', $trackingNumber);
        $this->assertMatchesRegularExpression('/^CONS-\d{8}-\d{4}$/', $trackingNumber);
        
        // Verify date part
        $expectedDate = now()->format('Ymd');
        $this->assertStringContainsString($expectedDate, $trackingNumber);
    }

    /** @test */
    public function it_updates_consolidated_status_and_syncs_to_packages()
    {
        // Create consolidated package
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);
        
        $consolidationResult = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(), 
            $this->admin
        );
        
        $consolidatedPackage = $consolidationResult['consolidated_package'];

        // Update status
        $result = $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage, 
            PackageStatus::SHIPPED, 
            $this->admin
        );

        $this->assertTrue($result['success']);

        // Verify consolidated package status updated
        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::SHIPPED, $consolidatedPackage->status);

        // Verify individual packages status updated
        $packages->each(function ($package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::SHIPPED, $package->status->value);
        });
    }

    /** @test */
    public function it_fails_to_update_consolidated_status_with_invalid_status()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create(['customer_id' => $this->customer->id]);

        $result = $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage, 
            'invalid_status', 
            $this->admin
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid status', $result['message']);
    }

    /** @test */
    public function it_gets_available_packages_for_customer()
    {
        // Create packages in different states
        $availablePackage1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'is_consolidated' => false,
        ]);
        
        $availablePackage2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::PROCESSING,
            'is_consolidated' => false,
        ]);
        
        // Create a real consolidated package first
        $realConsolidatedPackage = ConsolidatedPackage::factory()->create(['customer_id' => $this->customer->id]);
        
        // This should not be available (already consolidated)
        $consolidatedPackage = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'is_consolidated' => true,
            'consolidated_package_id' => $realConsolidatedPackage->id,
        ]);
        
        // This should not be available (delivered status)
        $deliveredPackage = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::DELIVERED,
            'is_consolidated' => false,
        ]);

        $availablePackages = $this->consolidationService->getAvailablePackagesForCustomer($this->customer->id);

        $this->assertCount(2, $availablePackages);
        $this->assertTrue($availablePackages->contains($availablePackage1));
        $this->assertTrue($availablePackages->contains($availablePackage2));
        $this->assertFalse($availablePackages->contains($consolidatedPackage));
        $this->assertFalse($availablePackages->contains($deliveredPackage));
    }

    /** @test */
    public function it_gets_active_consolidated_packages_for_customer()
    {
        $customer2 = User::factory()->create();
        
        // Create active consolidated package for customer
        $activeConsolidated = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'is_active' => true,
        ]);
        
        // Create inactive consolidated package for customer
        $inactiveConsolidated = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'is_active' => false,
        ]);
        
        // Create active consolidated package for different customer
        $otherCustomerConsolidated = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer2->id,
            'is_active' => true,
        ]);

        $activePackages = $this->consolidationService->getActiveConsolidatedPackagesForCustomer($this->customer->id);

        $this->assertCount(1, $activePackages);
        $this->assertTrue($activePackages->contains($activeConsolidated));
        $this->assertFalse($activePackages->contains($inactiveConsolidated));
        $this->assertFalse($activePackages->contains($otherCustomerConsolidated));
    }

    /** @test */
    public function it_gets_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->admin->id,
            'consolidated_at' => now()->subHours(2),
        ]);

        $history = $this->consolidationService->getConsolidationHistory($consolidatedPackage);

        $this->assertIsArray($history);
        $this->assertNotEmpty($history);
        
        $consolidationEvent = $history[0];
        $this->assertEquals('consolidated', $consolidationEvent['action']);
        $this->assertEquals($consolidatedPackage->consolidated_at, $consolidationEvent['performed_at']);
        $this->assertEquals($consolidatedPackage->createdBy->id, $consolidationEvent['performed_by']->id);
    }

    /** @test */
    public function it_handles_empty_package_ids_array()
    {
        $result = $this->consolidationService->consolidatePackages([], $this->admin);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('At least 2 packages', $result['message']);
    }

    /** @test */
    public function it_handles_non_existent_package_ids()
    {
        $result = $this->consolidationService->consolidatePackages([999, 1000], $this->admin);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Some packages were not found', $result['message']);
    }
}