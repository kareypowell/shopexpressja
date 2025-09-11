<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class UserRoleScopesTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdminRole;
    protected $adminRole;
    protected $customerRole;
    protected $purchaserRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Get roles from database (created by parent TestCase)
        $this->superAdminRole = Role::where('name', 'superadmin')->first();
        $this->adminRole = Role::where('name', 'admin')->first();
        $this->customerRole = Role::where('name', 'customer')->first();
        $this->purchaserRole = Role::where('name', 'purchaser')->first();
    }

    /** @test */
    public function with_role_scope_filters_users_by_role()
    {
        $adminUser1 = User::factory()->create(['role_id' => $this->adminRole->id]);
        $adminUser2 = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $adminUsers = User::withRole('admin')->get();
        $customerUsers = User::withRole('customer')->get();
        
        $this->assertCount(2, $adminUsers);
        $this->assertTrue($adminUsers->contains($adminUser1));
        $this->assertTrue($adminUsers->contains($adminUser2));
        $this->assertFalse($adminUsers->contains($customerUser));
        
        $this->assertCount(1, $customerUsers);
        $this->assertTrue($customerUsers->contains($customerUser));
        $this->assertFalse($customerUsers->contains($adminUser1));
    }

    /** @test */
    public function with_role_scope_is_case_insensitive()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        
        $this->assertCount(1, User::withRole('admin')->get());
        $this->assertCount(1, User::withRole('ADMIN')->get());
        $this->assertCount(1, User::withRole('Admin')->get());
        $this->assertCount(1, User::withRole('aDmIn')->get());
    }

    /** @test */
    public function admins_scope_returns_only_admin_users()
    {
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser1 = User::factory()->create(['role_id' => $this->adminRole->id]);
        $adminUser2 = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $adminUsers = User::admins()->get();
        
        $this->assertCount(2, $adminUsers);
        $this->assertTrue($adminUsers->contains($adminUser1));
        $this->assertTrue($adminUsers->contains($adminUser2));
        $this->assertFalse($adminUsers->contains($superAdminUser));
        $this->assertFalse($adminUsers->contains($customerUser));
        $this->assertFalse($adminUsers->contains($purchaserUser));
    }

    /** @test */
    public function super_admins_scope_returns_only_superadmin_users()
    {
        $superAdminUser1 = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $superAdminUser2 = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        
        $superAdminUsers = User::superAdmins()->get();
        
        $this->assertCount(2, $superAdminUsers);
        $this->assertTrue($superAdminUsers->contains($superAdminUser1));
        $this->assertTrue($superAdminUsers->contains($superAdminUser2));
        $this->assertFalse($superAdminUsers->contains($adminUser));
        $this->assertFalse($superAdminUsers->contains($customerUser));
    }

    /** @test */
    public function purchasers_scope_returns_only_purchaser_users()
    {
        $purchaserUser1 = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        $purchaserUser2 = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        
        $purchaserUsers = User::purchasers()->get();
        
        $this->assertCount(2, $purchaserUsers);
        $this->assertTrue($purchaserUsers->contains($purchaserUser1));
        $this->assertTrue($purchaserUsers->contains($purchaserUser2));
        $this->assertFalse($purchaserUsers->contains($adminUser));
        $this->assertFalse($purchaserUsers->contains($customerUser));
    }

    /** @test */
    public function with_any_role_scope_returns_users_with_any_of_the_specified_roles()
    {
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $adminAndCustomerUsers = User::withAnyRole(['admin', 'customer'])->get();
        $superAdminAndPurchaserUsers = User::withAnyRole(['superadmin', 'purchaser'])->get();
        
        $this->assertCount(2, $adminAndCustomerUsers);
        $this->assertTrue($adminAndCustomerUsers->contains($adminUser));
        $this->assertTrue($adminAndCustomerUsers->contains($customerUser));
        $this->assertFalse($adminAndCustomerUsers->contains($superAdminUser));
        $this->assertFalse($adminAndCustomerUsers->contains($purchaserUser));
        
        $this->assertCount(2, $superAdminAndPurchaserUsers);
        $this->assertTrue($superAdminAndPurchaserUsers->contains($superAdminUser));
        $this->assertTrue($superAdminAndPurchaserUsers->contains($purchaserUser));
        $this->assertFalse($superAdminAndPurchaserUsers->contains($adminUser));
        $this->assertFalse($superAdminAndPurchaserUsers->contains($customerUser));
    }

    /** @test */
    public function with_any_role_scope_is_case_insensitive()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        
        $users = User::withAnyRole(['ADMIN', 'CUSTOMER'])->get();
        
        $this->assertCount(2, $users);
        $this->assertTrue($users->contains($adminUser));
        $this->assertTrue($users->contains($customerUser));
    }

    /** @test */
    public function without_role_scope_excludes_users_with_specified_role()
    {
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $nonAdminUsers = User::withoutRole('admin')->get();
        
        $this->assertCount(3, $nonAdminUsers);
        $this->assertTrue($nonAdminUsers->contains($superAdminUser));
        $this->assertTrue($nonAdminUsers->contains($customerUser));
        $this->assertTrue($nonAdminUsers->contains($purchaserUser));
        $this->assertFalse($nonAdminUsers->contains($adminUser));
    }

    /** @test */
    public function without_role_scope_is_case_insensitive()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        
        $nonAdminUsers = User::withoutRole('ADMIN')->get();
        
        $this->assertCount(1, $nonAdminUsers);
        $this->assertTrue($nonAdminUsers->contains($customerUser));
        $this->assertFalse($nonAdminUsers->contains($adminUser));
    }

    /** @test */
    public function without_any_role_scope_excludes_users_with_any_of_specified_roles()
    {
        $superAdminUser = User::factory()->create(['role_id' => $this->superAdminRole->id]);
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $nonAdminNonCustomerUsers = User::withoutAnyRole(['admin', 'customer'])->get();
        
        $this->assertCount(2, $nonAdminNonCustomerUsers);
        $this->assertTrue($nonAdminNonCustomerUsers->contains($superAdminUser));
        $this->assertTrue($nonAdminNonCustomerUsers->contains($purchaserUser));
        $this->assertFalse($nonAdminNonCustomerUsers->contains($adminUser));
        $this->assertFalse($nonAdminNonCustomerUsers->contains($customerUser));
    }

    /** @test */
    public function without_any_role_scope_is_case_insensitive()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $users = User::withoutAnyRole(['ADMIN', 'CUSTOMER'])->get();
        
        $this->assertCount(1, $users);
        $this->assertTrue($users->contains($purchaserUser));
        $this->assertFalse($users->contains($adminUser));
        $this->assertFalse($users->contains($customerUser));
    }

    /** @test */
    public function scopes_can_be_chained_together()
    {
        $adminUser1 = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'first_name' => 'John',
            'last_name' => 'Admin'
        ]);
        $adminUser2 = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'first_name' => 'Jane',
            'last_name' => 'Admin'
        ]);
        $customerUser = User::factory()->create([
            'role_id' => $this->customerRole->id,
            'first_name' => 'John',
            'last_name' => 'Customer'
        ]);
        
        // Chain role scope with search scope
        $johnAdmins = User::admins()->where('first_name', 'John')->get();
        
        $this->assertCount(1, $johnAdmins);
        $this->assertTrue($johnAdmins->contains($adminUser1));
        $this->assertFalse($johnAdmins->contains($adminUser2));
        $this->assertFalse($johnAdmins->contains($customerUser));
    }

    /** @test */
    public function scopes_return_empty_collection_when_no_matches()
    {
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        
        $adminUsers = User::admins()->get();
        $superAdminUsers = User::superAdmins()->get();
        $purchaserUsers = User::purchasers()->get();
        
        $this->assertCount(0, $adminUsers);
        $this->assertCount(0, $superAdminUsers);
        $this->assertCount(0, $purchaserUsers);
    }

    /** @test */
    public function scopes_work_with_mixed_role_types()
    {
        $adminUser = User::factory()->create(['role_id' => $this->adminRole->id]);
        $customerUser = User::factory()->create(['role_id' => $this->customerRole->id]);
        $purchaserUser = User::factory()->create(['role_id' => $this->purchaserRole->id]);
        
        $adminUsers = User::admins()->get();
        $customerUsers = User::customerUsers()->get();
        $allUsers = User::all();
        
        $this->assertCount(1, $adminUsers);
        $this->assertTrue($adminUsers->contains($adminUser));
        $this->assertFalse($adminUsers->contains($customerUser));
        $this->assertFalse($adminUsers->contains($purchaserUser));
        
        $this->assertCount(1, $customerUsers);
        $this->assertTrue($customerUsers->contains($customerUser));
        $this->assertFalse($customerUsers->contains($adminUser));
        
        $this->assertCount(3, $allUsers);
        $this->assertTrue($allUsers->contains($adminUser));
        $this->assertTrue($allUsers->contains($customerUser));
        $this->assertTrue($allUsers->contains($purchaserUser));
    }
}