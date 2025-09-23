<?php

namespace Tests\Unit;

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

class CustomerPackagesWithModalTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        // Create admin user
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create customer user
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

        $component = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $this->customer]);

        $component->assertSet('customer', $this->customer);
        $component->assertSet('showModal', false);
        $component->assertSet('selectedPackage', null);
    }

    /** @test */
    public function it_can_show_package_details_modal()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $this->customer]);

        $component->call('showPackageDetails', $this->package->id);

        $component->assertSet('showModal', true);
        $component->assertSet('selectedPackage.id', $this->package->id);
    }

    /** @test */
    public function it_can_close_modal()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $this->customer]);

        // First open the modal
        $component->call('showPackageDetails', $this->package->id);
        $component->assertSet('showModal', true);

        // Then close it
        $component->call('closeModal');
        $component->assertSet('showModal', false);
        $component->assertSet('selectedPackage', null);
    }

    /** @test */
    public function it_only_shows_packages_for_the_specific_customer()
    {
        $this->actingAs($this->admin);

        // Create another customer and package
        $otherCustomer = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'customer'])->id]);
        $otherPackage = Package::factory()->create([
            'user_id' => $otherCustomer->id,
            'manifest_id' => $this->package->manifest_id,
            'shipper_id' => $this->package->shipper_id,
            'office_id' => $this->package->office_id,
        ]);

        $component = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $this->customer]);

        // Try to show the other customer's package - should not work
        $component->call('showPackageDetails', $otherPackage->id);
        $component->assertSet('showModal', false);
        $component->assertSet('selectedPackage', null);

        // But should work for the correct customer's package
        $component->call('showPackageDetails', $this->package->id);
        $component->assertSet('showModal', true);
        $component->assertSet('selectedPackage.id', $this->package->id);
    }
}