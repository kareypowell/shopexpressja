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
use App\Http\Livewire\Customers\CustomerPackagesTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerPackagesTableFeatureTest extends TestCase
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

        // Create customer user
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
    public function it_requires_authorization_to_view_customer_packages()
    {
        // Create another customer
        $otherCustomer = User::factory()->create(['role_id' => $this->customerRole->id]);

        // Test the policy directly - customer should not be able to view other customer's packages
        $this->assertFalse($this->customer->can('customer.viewPackages', $otherCustomer));
        
        // Customer should be able to view their own packages
        $this->assertTrue($this->customer->can('customer.viewPackages', $this->customer));
    }

    /** @test */
    public function admin_can_view_any_customer_packages()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $component->assertSuccessful();
    }

    /** @test */
    public function customer_can_view_own_packages()
    {
        $component = Livewire::actingAs($this->customer)
            ->test(CustomerPackagesTable::class, ['customer' => $this->customer]);

        $component->assertSuccessful();
    }
}