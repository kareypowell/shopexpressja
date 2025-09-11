<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleModelTest extends TestCase
{
    /** @test */
    public function it_identifies_system_roles_correctly()
    {
        // Test with system role names
        $systemRole = new Role(['name' => 'superadmin']);
        $this->assertTrue($systemRole->isSystemRole());
        
        $adminRole = new Role(['name' => 'admin']);
        $this->assertTrue($adminRole->isSystemRole());
        
        $customerRole = new Role(['name' => 'customer']);
        $this->assertTrue($customerRole->isSystemRole());
        
        $purchaserRole = new Role(['name' => 'purchaser']);
        $this->assertTrue($purchaserRole->isSystemRole());
        
        // Test with custom role
        $customRole = new Role(['name' => 'custom_role']);
        $this->assertFalse($customRole->isSystemRole());
    }

    /** @test */
    public function system_roles_constant_contains_expected_roles()
    {
        $expectedRoles = ['superadmin', 'admin', 'customer', 'purchaser'];
        
        $this->assertEquals($expectedRoles, Role::SYSTEM_ROLES);
    }

    /** @test */
    public function fillable_attributes_are_correct()
    {
        $role = new Role();
        $expectedFillable = ['name', 'description'];
        
        $this->assertEquals($expectedFillable, $role->getFillable());
    }

    /** @test */
    public function it_determines_if_system_role_cannot_be_deleted()
    {
        $systemRole = new Role(['name' => 'admin']);
        $this->assertFalse($systemRole->canBeDeleted());
    }

    /** @test */
    public function it_has_users_relationship_method()
    {
        $role = new Role();
        $this->assertTrue(method_exists($role, 'users'));
    }

    /** @test */
    public function it_has_get_user_count_method()
    {
        $role = new Role();
        $this->assertTrue(method_exists($role, 'getUserCount'));
    }

    /** @test */
    public function it_has_scope_methods()
    {
        $role = new Role();
        $this->assertTrue(method_exists($role, 'scopeSystemRoles'));
        $this->assertTrue(method_exists($role, 'scopeCustomRoles'));
        $this->assertTrue(method_exists($role, 'scopeSearch'));
    }
}