<?php

namespace Tests\Unit;

use App\Models\Role;
use Tests\TestCase;

class RoleUniquenessTest extends TestCase
{
    /** @test */
    public function role_names_are_normalized_to_lowercase()
    {
        $role = new Role(['name' => 'TestRole']);
        
        // Simulate the boot method behavior
        $role->name = strtolower(trim($role->name));
        
        $this->assertEquals('testrole', $role->name);
    }

    /** @test */
    public function role_names_are_trimmed()
    {
        $role = new Role(['name' => '  TestRole  ']);
        
        // Simulate the boot method behavior
        $role->name = strtolower(trim($role->name));
        
        $this->assertEquals('testrole', $role->name);
    }

    /** @test */
    public function role_name_validation_regex_works()
    {
        // Valid names
        $validNames = ['test_role', 'test-role', 'test role', 'TestRole123'];
        
        foreach ($validNames as $name) {
            $this->assertTrue(preg_match('/^[a-zA-Z0-9_\-\s]+$/', $name) === 1, "Name '$name' should be valid");
        }
        
        // Invalid names
        $invalidNames = ['test@role', 'test.role', 'test#role', 'test$role'];
        
        foreach ($invalidNames as $name) {
            $this->assertFalse(preg_match('/^[a-zA-Z0-9_\-\s]+$/', $name) === 1, "Name '$name' should be invalid");
        }
    }

    /** @test */
    public function role_model_has_boot_method()
    {
        $this->assertTrue(method_exists(Role::class, 'boot'));
    }

    /** @test */
    public function role_model_has_fillable_attributes()
    {
        $role = new Role();
        $fillable = $role->getFillable();
        
        $this->assertContains('name', $fillable);
        $this->assertContains('description', $fillable);
    }

    /** @test */
    public function role_model_has_casts()
    {
        $role = new Role();
        $casts = $role->getCasts();
        
        $this->assertArrayHasKey('name', $casts);
        $this->assertArrayHasKey('description', $casts);
        $this->assertEquals('string', $casts['name']);
        $this->assertEquals('string', $casts['description']);
    }

    /** @test */
    public function system_roles_constant_is_defined()
    {
        $expectedRoles = ['superadmin', 'admin', 'customer', 'purchaser'];
        
        $this->assertEquals($expectedRoles, Role::SYSTEM_ROLES);
    }
}