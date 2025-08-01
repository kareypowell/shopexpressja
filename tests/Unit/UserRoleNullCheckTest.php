<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRoleNullCheckTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_with_missing_role_returns_false_for_has_role_check()
    {
        // Create a role and user, then delete the role to simulate missing role
        $role = Role::factory()->create(['name' => 'test_role']);
        $user = User::factory()->create(['role_id' => $role->id]);
        
        // Delete the role to simulate a missing role relationship
        $role->delete();
        
        // Clear the relationship cache
        $user->unsetRelation('role');

        // Test that hasRole returns false instead of throwing an error
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('customer'));
        $this->assertFalse($user->hasRole(['admin', 'customer']));
        $this->assertFalse($user->hasRole('admin,customer'));
    }

    /** @test */
    public function user_with_valid_role_works_correctly()
    {
        // Create a role and user
        $role = Role::factory()->create(['name' => 'customer']);
        $user = User::factory()->create(['role_id' => $role->id]);

        // Test that hasRole works correctly with valid role
        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->hasRole(['admin', 'customer']));
        $this->assertTrue($user->hasRole('admin,customer'));
    }

    /** @test */
    public function user_role_methods_handle_missing_role_gracefully()
    {
        // Create a role and user, then delete the role to simulate missing role
        $role = Role::factory()->create(['name' => 'test_role']);
        $user = User::factory()->create(['role_id' => $role->id]);
        
        // Delete the role to simulate a missing role relationship
        $role->delete();
        
        // Clear the relationship cache
        $user->unsetRelation('role');

        // Test that role methods return false instead of throwing errors
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isCustomer());
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isPurchaser());
    }
}