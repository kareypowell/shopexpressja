<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Role;
use App\Policies\AddressPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddressPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $policy;
    protected $adminUser;
    protected $superAdminUser;
    protected $customerUser;
    protected $address;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new AddressPolicy();
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create users with different roles
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create an address for testing
        $this->address = Address::factory()->create();
    }

    public function test_admin_can_view_any_addresses()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
    }

    public function test_superadmin_can_view_any_addresses()
    {
        $this->assertTrue($this->policy->viewAny($this->superAdminUser));
    }

    public function test_customer_cannot_view_any_addresses()
    {
        $this->assertFalse($this->policy->viewAny($this->customerUser));
    }

    public function test_admin_can_view_address()
    {
        $this->assertTrue($this->policy->view($this->adminUser, $this->address));
    }

    public function test_superadmin_can_view_address()
    {
        $this->assertTrue($this->policy->view($this->superAdminUser, $this->address));
    }

    public function test_customer_cannot_view_address()
    {
        $this->assertFalse($this->policy->view($this->customerUser, $this->address));
    }

    public function test_admin_can_create_address()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_superadmin_can_create_address()
    {
        $this->assertTrue($this->policy->create($this->superAdminUser));
    }

    public function test_customer_cannot_create_address()
    {
        $this->assertFalse($this->policy->create($this->customerUser));
    }

    public function test_admin_can_update_address()
    {
        $this->assertTrue($this->policy->update($this->adminUser, $this->address));
    }

    public function test_superadmin_can_update_address()
    {
        $this->assertTrue($this->policy->update($this->superAdminUser, $this->address));
    }

    public function test_customer_cannot_update_address()
    {
        $this->assertFalse($this->policy->update($this->customerUser, $this->address));
    }

    public function test_admin_can_delete_address()
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->address));
    }

    public function test_superadmin_can_delete_address()
    {
        $this->assertTrue($this->policy->delete($this->superAdminUser, $this->address));
    }

    public function test_customer_cannot_delete_address()
    {
        $this->assertFalse($this->policy->delete($this->customerUser, $this->address));
    }
}