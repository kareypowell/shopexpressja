<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Livewire\Customers\CustomerPackagesWithModal;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerModalTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Get existing customer role
        $customerRole = Role::where('name', 'customer')->first();
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
        ]);
    }

    /** @test */
    public function customer_can_open_package_details_modal()
    {
        $this->actingAs($this->customer);

        $component = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $this->customer]);

        // Initially modal should be closed
        $this->assertFalse($component->get('showModal'));
        $this->assertNull($component->get('selectedPackage'));

        // Emit the showPackageDetails event
        $component->emit('showPackageDetails', $this->package->id);

        // Modal should now be open with the selected package
        $this->assertTrue($component->get('showModal'));
        $this->assertNotNull($component->get('selectedPackage'));
        $this->assertEquals($this->package->id, $component->get('selectedPackage')->id);
    }

    /** @test */
    public function customer_can_close_package_details_modal()
    {
        $this->actingAs($this->customer);

        $component = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $this->customer]);

        // Open the modal first
        $component->emit('showPackageDetails', $this->package->id);
        $this->assertTrue($component->get('showModal'));

        // Close the modal
        $component->call('closeModal');

        // Modal should now be closed
        $this->assertFalse($component->get('showModal'));
        $this->assertNull($component->get('selectedPackage'));
    }

    /** @test */
    public function customer_cannot_view_other_customers_packages_in_modal()
    {
        // Create another customer and their package
        $otherCustomer = User::factory()->create(['role_id' => 3]);
        $otherPackage = Package::factory()->create([
            'user_id' => $otherCustomer->id,
            'manifest_id' => Manifest::factory()->create()->id,
            'shipper_id' => Shipper::factory()->create()->id,
            'office_id' => Office::factory()->create()->id,
        ]);

        $this->actingAs($this->customer);

        $component = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $this->customer]);

        // Try to open modal with other customer's package
        $component->emit('showPackageDetails', $otherPackage->id);

        // Modal should remain closed since package doesn't belong to current customer
        $this->assertFalse($component->get('showModal'));
        $this->assertNull($component->get('selectedPackage'));
    }
}