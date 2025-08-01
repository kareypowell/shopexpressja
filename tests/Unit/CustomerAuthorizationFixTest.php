<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Customers\CustomerPackagesWithModal;
use App\Http\Livewire\Customers\PackageHistory;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerAuthorizationFixTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_can_access_their_own_package_history()
    {
        // Create customer role
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create customer user
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Act as the customer
        $this->actingAs($customer);

        // Test that customer can access their own package history
        $component = Livewire::test(PackageHistory::class, ['customer' => $customer]);
        
        $component->assertSet('customer.id', $customer->id);
    }

    /** @test */
    public function customer_can_access_their_own_packages_with_modal()
    {
        // Create customer role
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create customer user
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Act as the customer
        $this->actingAs($customer);

        // Test that customer can access their own packages with modal
        $component = Livewire::test(CustomerPackagesWithModal::class, ['customer' => $customer]);
        
        $component->assertSet('customer.id', $customer->id);
    }

    /** @test */
    public function customer_cannot_access_other_customers_data()
    {
        // Create customer role
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create two customer users
        $customer1 = User::factory()->create(['role_id' => $customerRole->id]);
        $customer2 = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Act as customer1
        $this->actingAs($customer1);

        // Test that customer1 cannot access customer2's data
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        Livewire::test(PackageHistory::class, ['customer' => $customer2]);
    }

    /** @test */
    public function admin_can_access_customer_data()
    {
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create admin and customer users
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Act as admin
        $this->actingAs($admin);

        // Test that admin can access customer data
        $component = Livewire::test(PackageHistory::class, ['customer' => $customer]);
        
        $component->assertSet('customer.id', $customer->id);
    }
}