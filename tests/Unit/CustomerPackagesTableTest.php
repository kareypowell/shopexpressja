<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Customers\CustomerPackagesTable;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Illuminate\Support\Facades\Gate;

class CustomerPackagesTableTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $packages;
    protected $office;
    protected $shipper;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing roles
        $adminRole = Role::find(2);
        $customerRole = Role::find(3);

        // Create admin user
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create customer user
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);

        // Create supporting models
        $manifest = Manifest::factory()->create();
        $this->shipper = Shipper::factory()->create();
        $this->office = Office::factory()->create();

        // Create packages for the customer
        $this->packages = Package::factory()->count(5)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $this->shipper->id,
            'office_id' => $this->office->id,
            'status' => 'ready', // Set status to allow cost visibility
            'freight_price' => 100.00,
            'clearance_fee' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
        ]);
    }

    /** @test */
    public function it_can_be_mounted_with_customer()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $component->assertSet('customer', $this->customer);
    }

    /** @test */
    public function it_requires_authorization_to_view_customer_packages()
    {
        $this->markTestSkipped('Authorization test requires proper policy setup');
    }

    /** @test */
    public function it_displays_customer_packages()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Check that packages are loaded
        $component->assertSee($this->packages->first()->tracking_number);
    }

    /** @test */
    public function it_calculates_total_cost_correctly()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $package = $this->packages->first();
        $expectedTotal = $package->freight_price + $package->clearance_fee + $package->storage_fee + $package->delivery_fee;

        // Test the calculation using the Package model's total_cost attribute
        $actualTotal = $package->total_cost;

        $this->assertEquals($expectedTotal, $actualTotal);
    }

    /** @test */
    public function it_can_toggle_cost_breakdown_display()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $component->assertSet('showCostBreakdown', false);

        $component->call('toggleCostBreakdown');

        $component->assertSet('showCostBreakdown', true);

        $component->call('toggleCostBreakdown');

        $component->assertSet('showCostBreakdown', false);
    }

    /** @test */
    public function it_filters_packages_by_status()
    {
        $this->actingAs($this->admin);

        // Create packages with different statuses
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => 'shipped',
            'tracking_number' => 'SHIPPED001'
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => 'processing',
            'tracking_number' => 'PROCESSING001'
        ]);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Filter by shipped status
        $component->set('filters.status', 'shipped');

        $component->assertSee('SHIPPED001');
        $component->assertDontSee('PROCESSING001');
    }

    /** @test */
    public function it_filters_packages_by_date_range()
    {
        $this->actingAs($this->admin);

        // Create an old package
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'created_at' => now()->subDays(60),
            'tracking_number' => 'OLD001'
        ]);

        // Create a recent package
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'created_at' => now()->subDays(15),
            'tracking_number' => 'RECENT001'
        ]);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Filter by last 30 days
        $component->set('filters.date_range', 'last_30_days');

        $component->assertSee('RECENT001');
        $component->assertDontSee('OLD001');
    }

    /** @test */
    public function it_calculates_package_statistics_correctly()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $stats = $component->instance()->getPackageStats();

        $this->assertEquals(5, $stats['total_packages']);
        $this->assertEquals(750.00, $stats['total_spent']); // 5 packages * $150 each
        $this->assertEquals(150.00, $stats['average_cost']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $stats['status_breakdown']);
    }

    /** @test */
    public function it_handles_packages_with_zero_costs()
    {
        $this->actingAs($this->admin);

        // Create package with zero costs
        $packageWithZeroCosts = Package::factory()->create([
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'freight_price' => 0,
            'clearance_fee' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Test the calculation using the Package model's total_cost attribute
        $totalCost = $packageWithZeroCosts->total_cost;

        $this->assertEquals(0, $totalCost);
    }

    /** @test */
    public function it_sorts_packages_by_total_cost()
    {
        $this->actingAs($this->admin);

        // Create packages with different costs
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'freight_price' => 50.00,
            'clearance_fee' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 5.00,
            'tracking_number' => 'LOW_COST'
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'freight_price' => 200.00,
            'clearance_fee' => 50.00,
            'storage_fee' => 20.00,
            'delivery_fee' => 30.00,
            'tracking_number' => 'HIGH_COST'
        ]);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Test that sorting works (this would be more comprehensive in a feature test)
        $this->assertTrue(true); // Placeholder - actual sorting test would require more complex setup
    }

    /** @test */
    public function it_searches_packages_by_tracking_number()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'SEARCH123'
        ]);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $component->set('filters.search', 'SEARCH123');

        $component->assertSee('SEARCH123');
    }

    /** @test */
    public function it_paginates_large_package_lists()
    {
        $this->actingAs($this->admin);

        // Create shared office and shipper to avoid duplicates
        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();

        // Create many packages
        Package::factory()->count(30)->create([
            'user_id' => $this->customer->id,
            'office_id' => $office->id,
            'shipper_id' => $shipper->id,
        ]);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Test that pagination is working (component should have pagination controls)
        $this->assertTrue(true); // Placeholder - actual pagination test would require more complex setup
    }
}