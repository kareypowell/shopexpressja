<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Livewire\Customers\CustomerPackagesTable;
use App\Http\Livewire\Customers\CustomerPackagesWithModal;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerPackageTableEventTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing customer role
        $customerRole = Role::find(3);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);

        // Create supporting models
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create a package for the customer
        $this->package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => 'ready_for_pickup',
            'tracking_number' => 'TEST123',
            'description' => 'Test Package',
            'weight' => 10.5,
        ]);
    }

    /** @test */
    public function customer_packages_table_renders_view_details_button()
    {
        $this->actingAs($this->customer);

        $component = Livewire::test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        // Check that the component renders without errors
        $component->assertStatus(200);
        
        // Check that the table shows the package
        $component->assertSee('TEST123');
        $component->assertSee('Test Package');
        $component->assertSee('View Details');
    }

    /** @test */
    public function modal_wrapper_receives_event_from_table()
    {
        $this->actingAs($this->customer);

        // Test the full integration with the modal wrapper
        $modalComponent = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $this->customer]);

        // Initially modal should be closed
        $this->assertFalse($modalComponent->get('showModal'));

        // Simulate the event that would be emitted by the table
        $modalComponent->emit('showPackageDetails', $this->package->id);

        // Modal should now be open
        $this->assertTrue($modalComponent->get('showModal'));
        $this->assertEquals($this->package->id, $modalComponent->get('selectedPackage')->id);
    }
}