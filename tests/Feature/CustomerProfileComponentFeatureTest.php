<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use App\Http\Livewire\Customers\CustomerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerProfileComponentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $customerRole;
    protected $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->customerRole = Role::factory()->create(['name' => 'customer']);

        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'User'
        ]);

        // Create customer user with profile
        $this->customer = User::factory()->create([
            'role_id' => $this->customerRole->id,
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        Profile::factory()->create([
            'user_id' => $this->customer->id,
            'account_number' => 'ACC123456',
            'tax_number' => 'TAX123',
            'telephone_number' => '1234567890',
            'street_address' => '123 Main St',
            'city_town' => 'Kingston',
            'parish' => 'St. Andrew',
            'country' => 'Jamaica',
            'pickup_location' => 1
        ]);
    }

    /** @test */
    public function it_can_toggle_package_view()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertSet('showAllPackages', false);

        $component->call('togglePackageView');

        $component->assertSet('showAllPackages', true);

        $component->call('togglePackageView');

        $component->assertSet('showAllPackages', false);
    }

    /** @test */
    public function it_can_export_customer_data()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->call('exportCustomerData')
                 ->assertDispatchedBrowserEvent('show-alert', [
                     'type' => 'info',
                     'message' => 'Export functionality will be implemented in a future update.'
                 ]);
    }

    /** @test */
    public function it_renders_with_paginated_packages_when_showing_all()
    {
        // Create packages
        $manifest = Manifest::factory()->create();
        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();

        Package::factory()->count(15)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'office_id' => $office->id,
            'shipper_id' => $shipper->id,
            'status' => 'delivered'
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(CustomerProfile::class, ['customer' => $this->customer]);

        // Toggle to show all packages
        $component->call('togglePackageView');

        // Should show paginated packages
        $component->assertSet('showAllPackages', true);
        $component->assertViewHas('packages');
    }

    /** @test */
    public function admin_can_view_any_customer_profile()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertSuccessful();
    }

    /** @test */
    public function customer_can_view_own_profile()
    {
        $component = Livewire::actingAs($this->customer)
            ->test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertSuccessful();
    }
}