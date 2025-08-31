<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Office;
use App\Models\Package;
use App\Models\Profile;
use App\Models\Role;
use App\Policies\OfficePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OfficePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $policy;
    protected $adminUser;
    protected $superAdminUser;
    protected $customerUser;
    protected $office;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new OfficePolicy();
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create users with different roles
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create an office for testing
        $this->office = Office::factory()->create();
    }

    public function test_admin_can_view_any_offices()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
    }

    public function test_superadmin_can_view_any_offices()
    {
        $this->assertTrue($this->policy->viewAny($this->superAdminUser));
    }

    public function test_customer_cannot_view_any_offices()
    {
        $this->assertFalse($this->policy->viewAny($this->customerUser));
    }

    public function test_admin_can_view_office()
    {
        $this->assertTrue($this->policy->view($this->adminUser, $this->office));
    }

    public function test_superadmin_can_view_office()
    {
        $this->assertTrue($this->policy->view($this->superAdminUser, $this->office));
    }

    public function test_customer_cannot_view_office()
    {
        $this->assertFalse($this->policy->view($this->customerUser, $this->office));
    }

    public function test_admin_can_create_office()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_superadmin_can_create_office()
    {
        $this->assertTrue($this->policy->create($this->superAdminUser));
    }

    public function test_customer_cannot_create_office()
    {
        $this->assertFalse($this->policy->create($this->customerUser));
    }

    public function test_admin_can_update_office()
    {
        $this->assertTrue($this->policy->update($this->adminUser, $this->office));
    }

    public function test_superadmin_can_update_office()
    {
        $this->assertTrue($this->policy->update($this->superAdminUser, $this->office));
    }

    public function test_customer_cannot_update_office()
    {
        $this->assertFalse($this->policy->update($this->customerUser, $this->office));
    }

    public function test_admin_can_delete_office_without_dependencies()
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->office));
    }

    public function test_superadmin_can_delete_office_without_dependencies()
    {
        $this->assertTrue($this->policy->delete($this->superAdminUser, $this->office));
    }

    public function test_customer_cannot_delete_office()
    {
        $this->assertFalse($this->policy->delete($this->customerUser, $this->office));
    }

    public function test_admin_cannot_delete_office_with_packages()
    {
        // Create a package associated with this office
        $package = Package::factory()->create(['office_id' => $this->office->id]);
        
        $this->assertFalse($this->policy->delete($this->adminUser, $this->office));
    }

    public function test_admin_cannot_delete_office_with_profiles()
    {
        // Create a profile associated with this office
        $profile = Profile::factory()->create(['pickup_location' => $this->office->id]);
        
        $this->assertFalse($this->policy->delete($this->adminUser, $this->office));
    }
}