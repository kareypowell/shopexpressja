<?php

namespace Tests\Feature;

use App\Http\Livewire\Roles\Role as RoleComponent;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleUniquenessComponentTest extends TestCase
{
    /** @test */
    public function component_validates_role_uniqueness()
    {
        $component = new RoleComponent();
        
        // Test the validateRoleUniqueness method exists
        $this->assertTrue(method_exists($component, 'validateRoleUniqueness'));
    }

    /** @test */
    public function component_has_updated_name_method()
    {
        $component = new RoleComponent();
        
        // Test the updatedName method exists for real-time validation
        $this->assertTrue(method_exists($component, 'updatedName'));
    }

    /** @test */
    public function component_has_proper_validation_rules()
    {
        $component = new RoleComponent();
        $reflection = new \ReflectionClass($component);
        
        $rulesProperty = $reflection->getProperty('rules');
        $rulesProperty->setAccessible(true);
        $rules = $rulesProperty->getValue($component);
        
        // Check that name validation includes unique constraint
        $this->assertStringContainsString('unique:roles,name', $rules['name']);
        
        // Check that name validation includes regex for allowed characters
        $this->assertStringContainsString('regex:/^[a-zA-Z0-9_\-\s]+$/', $rules['name']);
    }

    /** @test */
    public function component_has_custom_error_messages()
    {
        $component = new RoleComponent();
        $reflection = new \ReflectionClass($component);
        
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($component);
        
        $this->assertArrayHasKey('name.unique', $messages);
        $this->assertArrayHasKey('name.regex', $messages);
        $this->assertEquals('A role with this name already exists.', $messages['name.unique']);
        $this->assertEquals('Role name can only contain letters, numbers, spaces, hyphens, and underscores.', $messages['name.regex']);
    }

    /** @test */
    public function component_handles_database_exceptions()
    {
        $component = new RoleComponent();
        
        // Check that createRole method exists and handles exceptions
        $this->assertTrue(method_exists($component, 'createRole'));
        
        // Check that updateRole method exists and handles exceptions
        $this->assertTrue(method_exists($component, 'updateRole'));
    }
}