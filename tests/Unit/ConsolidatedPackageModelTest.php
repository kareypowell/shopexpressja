<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ConsolidatedPackage;
use App\Models\Package;
use App\Models\User;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidatedPackageModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function it_has_correct_fillable_fields()
    {
        $expectedFillable = [
            'consolidated_tracking_number',
            'customer_id',
            'created_by',
            'total_weight',
            'total_quantity',
            'total_freight_price',
            'total_clearance_fee',
            'total_storage_fee',
            'total_delivery_fee',
            'status',
            'consolidated_at',
            'unconsolidated_at',
            'is_active',
            'notes',
        ];

        $consolidatedPackage = new ConsolidatedPackage();
        $this->assertEquals($expectedFillable, $consolidatedPackage->getFillable());
    }

    /** @test */
    public function it_belongs_to_customer()
    {
        $customer = User::factory()->create();
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $this->assertInstanceOf(User::class, $consolidatedPackage->customer);
        $this->assertEquals($customer->id, $consolidatedPackage->customer->id);
    }

    /** @test */
    public function it_belongs_to_created_by_user()
    {
        $admin = User::factory()->create();
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'created_by' => $admin->id,
        ]);

        $this->assertInstanceOf(User::class, $consolidatedPackage->createdBy);
        $this->assertEquals($admin->id, $consolidatedPackage->createdBy->id);
    }

    /** @test */
    public function it_has_many_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        $packages = Package::factory()->count(3)->create([
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        $this->assertCount(3, $consolidatedPackage->packages);
        $this->assertInstanceOf(Package::class, $consolidatedPackage->packages->first());
    }

    /** @test */
    public function it_calculates_total_weight_from_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'total_weight' => 0,
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'weight' => 10.5,
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'weight' => 15.3,
        ]);

        // Refresh to get updated relationships
        $consolidatedPackage->refresh();
        
        $this->assertEquals(25.8, $consolidatedPackage->total_weight);
    }

    /** @test */
    public function it_calculates_total_quantity_from_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'total_quantity' => 0,
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        // Refresh to get updated relationships
        $consolidatedPackage->refresh();
        
        $this->assertEquals(2, $consolidatedPackage->total_quantity);
    }

    /** @test */
    public function it_calculates_total_cost_from_all_fees()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'total_freight_price' => 100.00,
            'total_clearance_fee' => 25.50,
            'total_storage_fee' => 15.00,
            'total_delivery_fee' => 10.00,
        ]);

        $this->assertEquals(150.50, $consolidatedPackage->total_cost);
    }

    /** @test */
    public function it_gets_formatted_tracking_numbers()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'tracking_number' => 'PKG001',
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'tracking_number' => 'PKG002',
        ]);

        $this->assertEquals('PKG001, PKG002', $consolidatedPackage->formatted_tracking_numbers);
    }

    /** @test */
    public function it_calculates_and_updates_totals()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'weight' => 10.5,
            'freight_price' => 50.00,
            'clearance_fee' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 8.00,
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'weight' => 15.3,
            'freight_price' => 75.00,
            'clearance_fee' => 15.00,
            'storage_fee' => 7.50,
            'delivery_fee' => 12.00,
        ]);

        $consolidatedPackage->calculateTotals();
        $consolidatedPackage->refresh();

        $this->assertEquals(25.8, $consolidatedPackage->total_weight);
        $this->assertEquals(2, $consolidatedPackage->total_quantity);
        $this->assertEquals(125.00, $consolidatedPackage->total_freight_price);
        $this->assertEquals(25.00, $consolidatedPackage->total_clearance_fee);
        $this->assertEquals(12.50, $consolidatedPackage->total_storage_fee);
        $this->assertEquals(20.00, $consolidatedPackage->total_delivery_fee);
    }

    /** @test */
    public function it_can_be_unconsolidated_when_active_and_not_distributed()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'is_active' => true,
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
        ]);

        $this->assertTrue($consolidatedPackage->canBeUnconsolidated());
    }

    /** @test */
    public function it_cannot_be_unconsolidated_when_inactive()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'is_active' => false,
        ]);

        $this->assertFalse($consolidatedPackage->canBeUnconsolidated());
    }

    /** @test */
    public function it_cannot_be_unconsolidated_when_packages_are_distributed()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'is_active' => true,
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::DELIVERED,
        ]);

        $this->assertFalse($consolidatedPackage->canBeUnconsolidated());
    }

    /** @test */
    public function it_generates_consolidated_tracking_number()
    {
        $consolidatedPackage = new ConsolidatedPackage();
        $trackingNumber = $consolidatedPackage->generateConsolidatedTrackingNumber();

        $expectedPattern = '/^CONS-\d{8}-\d{4}$/';
        $this->assertMatchesRegularExpression($expectedPattern, $trackingNumber);
    }

    /** @test */
    public function it_updates_status_from_packages_when_all_same()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        
        Package::factory()->count(2)->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
        ]);

        $consolidatedPackage->updateStatusFromPackages();
        $consolidatedPackage->refresh();

        $this->assertEquals(PackageStatus::READY, $consolidatedPackage->status);
    }

    /** @test */
    public function it_updates_status_from_packages_with_priority_when_mixed()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::PROCESSING,
        ]);
        
        Package::factory()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
        ]);

        $consolidatedPackage->updateStatusFromPackages();
        $consolidatedPackage->refresh();

        // READY has higher priority than PROCESSING
        $this->assertEquals(PackageStatus::READY, $consolidatedPackage->status);
    }

    /** @test */
    public function it_syncs_status_to_all_packages()
    {
        $user = User::factory()->create();
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        
        $packages = Package::factory()->count(3)->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        $consolidatedPackage->syncPackageStatuses(PackageStatus::READY, $user);

        foreach ($packages as $package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::READY, $package->status);
        }

        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::READY, $consolidatedPackage->status);
    }

    /** @test */
    public function it_scopes_active_consolidations()
    {
        ConsolidatedPackage::factory()->create(['is_active' => true]);
        ConsolidatedPackage::factory()->create(['is_active' => false]);

        $activeConsolidations = ConsolidatedPackage::active()->get();

        $this->assertCount(1, $activeConsolidations);
        $this->assertTrue($activeConsolidations->first()->is_active);
    }

    /** @test */
    public function it_scopes_for_specific_customer()
    {
        $customer1 = User::factory()->create();
        $customer2 = User::factory()->create();
        
        ConsolidatedPackage::factory()->create(['customer_id' => $customer1->id]);
        ConsolidatedPackage::factory()->create(['customer_id' => $customer2->id]);

        $customer1Consolidations = ConsolidatedPackage::forCustomer($customer1->id)->get();

        $this->assertCount(1, $customer1Consolidations);
        $this->assertEquals($customer1->id, $customer1Consolidations->first()->customer_id);
    }
}