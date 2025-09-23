<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Models\User;
use App\Models\Role;
use App\Enums\PackageStatus;

class ConsolidatedPackageFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles needed for factories
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }

    /** @test */
    public function it_can_create_basic_consolidated_package_using_factory()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        $this->assertInstanceOf(ConsolidatedPackage::class, $consolidatedPackage);
        $this->assertNotNull($consolidatedPackage->consolidated_tracking_number);
        $this->assertNotNull($consolidatedPackage->customer_id);
        $this->assertNotNull($consolidatedPackage->created_by);
        $this->assertGreaterThan(0, $consolidatedPackage->total_weight);
        $this->assertGreaterThan(0, $consolidatedPackage->total_quantity);
        $this->assertGreaterThan(0, $consolidatedPackage->total_freight_price);
        $this->assertTrue($consolidatedPackage->is_active);
        $this->assertNotNull($consolidatedPackage->consolidated_at);
        $this->assertNull($consolidatedPackage->unconsolidated_at);
    }

    /** @test */
    public function it_generates_proper_tracking_number_format()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        $trackingNumber = $consolidatedPackage->consolidated_tracking_number;
        
        // Should match format: CONS-YYYYMMDD-XXXX
        $this->assertMatchesRegularExpression('/^CONS-\d{8}-\d{4}$/', $trackingNumber);
        
        // Extract date part and verify it's today
        $datePart = substr($trackingNumber, 5, 8);
        $expectedDate = now()->format('Ymd');
        $this->assertEquals($expectedDate, $datePart);
    }

    /** @test */
    public function it_can_create_inactive_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->inactive()->create();

        $this->assertFalse($consolidatedPackage->is_active);
        $this->assertNotNull($consolidatedPackage->unconsolidated_at);
    }

    /** @test */
    public function it_can_create_consolidated_package_with_specific_status()
    {
        $statuses = [
            PackageStatus::PENDING,
            PackageStatus::PROCESSING,
            PackageStatus::SHIPPED,
            PackageStatus::CUSTOMS,
            PackageStatus::READY,
            PackageStatus::DELIVERED,
        ];

        foreach ($statuses as $status) {
            $consolidatedPackage = ConsolidatedPackage::factory()->withStatus($status)->create();
            $this->assertEquals($status, $consolidatedPackage->status);
        }
    }

    /** @test */
    public function it_can_create_consolidated_package_for_specific_customer()
    {
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()->forCustomer($customer)->create();

        $this->assertEquals($customer->id, $consolidatedPackage->customer_id);
    }

    /** @test */
    public function it_can_create_consolidated_package_with_specific_creator()
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()->createdBy($admin)->create();

        $this->assertEquals($admin->id, $consolidatedPackage->created_by);
    }

    /** @test */
    public function it_can_create_delivered_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->delivered()->create();

        $this->assertEquals(PackageStatus::DELIVERED, $consolidatedPackage->status);
    }

    /** @test */
    public function it_can_create_processing_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->processing()->create();

        $this->assertEquals(PackageStatus::PROCESSING, $consolidatedPackage->status);
    }

    /** @test */
    public function it_can_create_customs_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->customs()->create();

        $this->assertEquals(PackageStatus::CUSTOMS, $consolidatedPackage->status);
    }

    /** @test */
    public function it_can_create_shipped_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->shipped()->create();

        $this->assertEquals(PackageStatus::SHIPPED, $consolidatedPackage->status);
    }

    /** @test */
    public function it_can_create_ready_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->ready()->create();

        $this->assertEquals(PackageStatus::READY, $consolidatedPackage->status);
    }

    /** @test */
    public function it_can_create_high_value_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->highValue()->create();

        $this->assertGreaterThanOrEqual(10, $consolidatedPackage->total_weight);
        $this->assertGreaterThanOrEqual(5, $consolidatedPackage->total_quantity);
        $this->assertGreaterThanOrEqual(200, $consolidatedPackage->total_freight_price);
        $this->assertGreaterThanOrEqual(50, $consolidatedPackage->total_clearance_fee);
    }

    /** @test */
    public function it_can_create_small_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->small()->create();

        $this->assertLessThanOrEqual(5, $consolidatedPackage->total_weight);
        $this->assertLessThanOrEqual(3, $consolidatedPackage->total_quantity);
        $this->assertLessThanOrEqual(80, $consolidatedPackage->total_freight_price);
    }

    /** @test */
    public function it_can_create_historical_consolidated_package()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->historical()->create();

        $this->assertTrue($consolidatedPackage->consolidated_at->isPast());
        $this->assertTrue($consolidatedPackage->created_at->isPast());
        $this->assertLessThanOrEqual(30, $consolidatedPackage->created_at->diffInDays(now()));
        $this->assertGreaterThanOrEqual(7, $consolidatedPackage->created_at->diffInDays(now()));
    }

    /** @test */
    public function it_can_create_consolidated_package_with_custom_notes()
    {
        $notes = 'Custom test notes for consolidation';
        $consolidatedPackage = ConsolidatedPackage::factory()->withNotes($notes)->create();

        $this->assertEquals($notes, $consolidatedPackage->notes);
    }

    /** @test */
    public function it_can_chain_multiple_factory_states()
    {
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()
            ->forCustomer($customer)
            ->createdBy($admin)
            ->highValue()
            ->ready()
            ->withNotes('Chained factory states test')
            ->create();

        $this->assertEquals($customer->id, $consolidatedPackage->customer_id);
        $this->assertEquals($admin->id, $consolidatedPackage->created_by);
        $this->assertEquals(PackageStatus::READY, $consolidatedPackage->status);
        $this->assertGreaterThanOrEqual(200, $consolidatedPackage->total_freight_price);
        $this->assertEquals('Chained factory states test', $consolidatedPackage->notes);
    }

    /** @test */
    public function it_can_create_multiple_consolidated_packages()
    {
        $consolidatedPackages = ConsolidatedPackage::factory()->count(5)->create();

        $this->assertCount(5, $consolidatedPackages);
        
        // Verify each has unique tracking number
        $trackingNumbers = $consolidatedPackages->pluck('consolidated_tracking_number')->toArray();
        $this->assertEquals(5, count(array_unique($trackingNumbers)));
    }

    /** @test */
    public function it_creates_valid_relationships()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        // Test customer relationship
        $this->assertInstanceOf(User::class, $consolidatedPackage->customer);
        // Note: Factory may create users with different roles, so we just verify the relationship exists

        // Test created_by relationship
        $this->assertInstanceOf(User::class, $consolidatedPackage->createdBy);
    }

    /** @test */
    public function consolidation_history_factory_creates_valid_records()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        
        // Test different action types
        $actions = ['consolidated', 'unconsolidated', 'status_changed'];
        
        foreach ($actions as $action) {
            $history = ConsolidationHistory::factory()
                ->for($consolidatedPackage)
                ->create(['action' => $action]);

            $this->assertEquals($action, $history->action);
            $this->assertEquals($consolidatedPackage->id, $history->consolidated_package_id);
            $this->assertInstanceOf(User::class, $history->performedBy);
            $this->assertIsArray($history->details);
            $this->assertNotEmpty($history->details);

            // Verify action-specific details
            switch ($action) {
                case 'consolidated':
                    $this->assertArrayHasKey('package_count', $history->details);
                    $this->assertArrayHasKey('total_weight', $history->details);
                    $this->assertArrayHasKey('total_cost', $history->details);
                    $this->assertArrayHasKey('package_ids', $history->details);
                    break;

                case 'unconsolidated':
                    $this->assertArrayHasKey('package_count', $history->details);
                    $this->assertArrayHasKey('reason', $history->details);
                    $this->assertArrayHasKey('package_ids', $history->details);
                    break;

                case 'status_changed':
                    $this->assertArrayHasKey('old_status', $history->details);
                    $this->assertArrayHasKey('new_status', $history->details);
                    $this->assertArrayHasKey('package_count', $history->details);
                    $this->assertArrayHasKey('reason', $history->details);
                    break;
            }
        }
    }

    /** @test */
    public function consolidation_history_factory_states_work_correctly()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        // Test consolidated state
        $consolidatedHistory = ConsolidationHistory::factory()
            ->for($consolidatedPackage)
            ->consolidated()
            ->create();

        $this->assertEquals('consolidated', $consolidatedHistory->action);
        $this->assertArrayHasKey('package_count', $consolidatedHistory->details);

        // Test unconsolidated state
        $unconsolidatedHistory = ConsolidationHistory::factory()
            ->for($consolidatedPackage)
            ->unconsolidated()
            ->create();

        $this->assertEquals('unconsolidated', $unconsolidatedHistory->action);
        $this->assertArrayHasKey('reason', $unconsolidatedHistory->details);

        // Test status changed state
        $statusChangedHistory = ConsolidationHistory::factory()
            ->for($consolidatedPackage)
            ->statusChanged()
            ->create();

        $this->assertEquals('status_changed', $statusChangedHistory->action);
        $this->assertArrayHasKey('old_status', $statusChangedHistory->details);
        $this->assertArrayHasKey('new_status', $statusChangedHistory->details);
    }

    /** @test */
    public function it_can_create_realistic_test_scenarios_using_factories()
    {
        // Scenario: Customer with multiple consolidations in different states
        $customer = User::factory()->create([
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        // Active consolidation ready for pickup
        $readyConsolidation = ConsolidatedPackage::factory()
            ->forCustomer($customer)
            ->createdBy($admin)
            ->ready()
            ->withNotes('Ready for customer pickup')
            ->create();

        // Processing consolidation
        $processingConsolidation = ConsolidatedPackage::factory()
            ->forCustomer($customer)
            ->createdBy($admin)
            ->processing()
            ->small()
            ->create();

        // Historical delivered consolidation
        $deliveredConsolidation = ConsolidatedPackage::factory()
            ->forCustomer($customer)
            ->createdBy($admin)
            ->delivered()
            ->historical()
            ->highValue()
            ->create();

        // Inactive (unconsolidated) consolidation
        $inactiveConsolidation = ConsolidatedPackage::factory()
            ->forCustomer($customer)
            ->createdBy($admin)
            ->inactive()
            ->withNotes('Unconsolidated due to customer request')
            ->create();

        // Verify scenario
        $customerConsolidations = ConsolidatedPackage::where('customer_id', $customer->id)->get();
        $this->assertCount(4, $customerConsolidations);

        $activeConsolidations = $customerConsolidations->where('is_active', true);
        $this->assertCount(3, $activeConsolidations);

        $readyConsolidations = $activeConsolidations->where('status', PackageStatus::READY);
        $this->assertCount(1, $readyConsolidations);

        $historicalConsolidations = $activeConsolidations->where('status', PackageStatus::DELIVERED);
        $this->assertCount(1, $historicalConsolidations);
        $this->assertTrue($historicalConsolidations->first()->created_at->isPast());
    }
}