<?php

namespace Tests\Feature;

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

class ReadyStatusCostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_can_see_costs_for_packages_with_ready_status()
    {
        // Use existing customer role
        $customerRole = Role::find(3);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);

        // Create supporting models
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create a package with 'ready' status
        $readyPackage = Package::factory()->create([
            'user_id' => $customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => 'ready',
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
        ]);

        $this->actingAs($customer);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $customer]);

        // Customer should be able to see costs for 'ready' packages
        $this->assertTrue($component->instance()->shouldShowCostForPackage($readyPackage));

        // Check that the package stats include the ready package costs
        $stats = $component->instance()->getPackageStats();
        $this->assertEquals(150.00, $stats['total_spent']); // 100 + 25 + 10 + 15
    }

    /** @test */
    public function ready_status_displays_correctly_in_table()
    {
        // Use existing customer role
        $customerRole = Role::find(3);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);

        // Create supporting models
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create a package with 'ready' status
        $readyPackage = Package::factory()->create([
            'user_id' => $customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => 'ready',
            'tracking_number' => 'READY123',
            'description' => 'Ready Package Test',
            'weight' => 5.0,
        ]);

        $this->actingAs($customer);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $customer]);

        // Check that the component renders the package
        $component->assertSee('READY123');
        $component->assertSee('Ready Package Test');
        
        // The status should be displayed as "Ready for Pickup" in the UI
        $component->assertSee('Ready for Pickup');
    }
}