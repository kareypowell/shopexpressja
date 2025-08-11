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

class CustomerCostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $packages;

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
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create packages with different statuses
        $this->packages = collect([
            Package::factory()->create([
                'user_id' => $this->customer->id,
                'manifest_id' => $manifest->id,
                'shipper_id' => $shipper->id,
                'office_id' => $office->id,
                'status' => 'processing',
                'freight_price' => 100.00,
                'customs_duty' => 25.00,
                'storage_fee' => 10.00,
                'delivery_fee' => 15.00,
            ]),
            Package::factory()->create([
                'user_id' => $this->customer->id,
                'manifest_id' => $manifest->id,
                'shipper_id' => $shipper->id,
                'office_id' => $office->id,
                'status' => 'ready',
                'freight_price' => 200.00,
                'customs_duty' => 50.00,
                'storage_fee' => 20.00,
                'delivery_fee' => 30.00,
            ]),
            Package::factory()->create([
                'user_id' => $this->customer->id,
                'manifest_id' => $manifest->id,
                'shipper_id' => $shipper->id,
                'office_id' => $office->id,
                'status' => 'delivered',
                'freight_price' => 150.00,
                'customs_duty' => 30.00,
                'storage_fee' => 15.00,
                'delivery_fee' => 20.00,
            ]),
            Package::factory()->create([
                'user_id' => $this->customer->id,
                'manifest_id' => $manifest->id,
                'shipper_id' => $shipper->id,
                'office_id' => $office->id,
                'status' => 'shipped',
                'freight_price' => 120.00,
                'customs_duty' => 35.00,
                'storage_fee' => 12.00,
                'delivery_fee' => 18.00,
            ]),
        ]);
    }

    /** @test */
    public function admin_can_always_see_costs()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Admin should be able to see costs
        $this->assertTrue($component->instance()->shouldShowCosts());

        // Admin should see costs for all packages regardless of status
        foreach ($this->packages as $package) {
            $this->assertTrue($component->instance()->shouldShowCostForPackage($package));
        }
    }

    /** @test */
    public function customer_can_only_see_costs_for_ready_and_delivered_packages()
    {
        $this->actingAs($this->customer);
        
        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Customer should be able to see costs (column visibility)
        $this->assertTrue($component->instance()->shouldShowCosts());

        // Check individual package cost visibility
        $processingPackage = $this->packages->filter(fn($p) => $p->status->value === 'processing')->first();
        $readyPackage = $this->packages->filter(fn($p) => $p->status->value === 'ready')->first();
        $deliveredPackage = $this->packages->filter(fn($p) => $p->status->value === 'delivered')->first();
        $shippedPackage = $this->packages->filter(fn($p) => $p->status->value === 'shipped')->first();

        // Customer should NOT see costs for processing packages
        $this->assertFalse($component->instance()->shouldShowCostForPackage($processingPackage));

        // Customer should NOT see costs for shipped packages (in transit)
        $this->assertFalse($component->instance()->shouldShowCostForPackage($shippedPackage));

        // Customer SHOULD see costs for ready packages
        $this->assertTrue($component->instance()->shouldShowCostForPackage($readyPackage));

        // Customer SHOULD see costs for delivered packages
        $this->assertTrue($component->instance()->shouldShowCostForPackage($deliveredPackage));
    }

    /** @test */
    public function customer_cannot_see_other_customers_costs()
    {
        // Create another customer using existing role
        $otherCustomer = User::factory()->create(['role_id' => 3]);

        $this->actingAs($this->admin); // Use admin to bypass authorization
        
        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $otherCustomer]);

        // Now test as customer - should not be able to see other customer's costs
        $this->actingAs($this->customer);
        
        // Customer should NOT be able to see other customer's costs
        $this->assertFalse($component->instance()->shouldShowCosts());
    }

    /** @test */
    public function package_stats_only_include_visible_costs_for_customers()
    {
        $this->actingAs($this->customer);
        
        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $stats = $component->instance()->getPackageStats();

        // Should count all packages
        $this->assertEquals(4, $stats['total_packages']);

        // Should only include costs from ready and delivered packages
        // ready: 200 + 50 + 20 + 30 = 300
        // delivered: 150 + 30 + 15 + 20 = 215
        // Total: 515
        $this->assertEquals(515.00, $stats['total_spent']);

        // Average should be based on packages with visible costs (2 packages)
        $this->assertEquals(257.50, round($stats['average_cost'], 2));
    }

    /** @test */
    public function admin_package_stats_include_all_costs()
    {
        $this->actingAs($this->admin);
        
        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $stats = $component->instance()->getPackageStats();

        // Should count all packages
        $this->assertEquals(4, $stats['total_packages']);

        // Should include costs from all packages
        // processing: 100 + 25 + 10 + 15 = 150
        // ready: 200 + 50 + 20 + 30 = 300
        // delivered: 150 + 30 + 15 + 20 = 215
        // shipped: 120 + 35 + 12 + 18 = 185
        // Total: 850
        $this->assertEquals(850.00, $stats['total_spent']);

        // Average should be based on all packages (4 packages)
        $this->assertEquals(212.50, round($stats['average_cost'], 2));
    }
}