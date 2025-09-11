<?php

namespace Tests\Feature;

use App\Http\Livewire\Roles\Role as RoleComponent;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleManagementFeatureTest extends TestCase
{
    /** @test */
    public function role_component_has_required_methods()
    {
        $component = new RoleComponent();
        
        $this->assertTrue(method_exists($component, 'render'));
        $this->assertTrue(method_exists($component, 'showCreateModal'));
        $this->assertTrue(method_exists($component, 'createRole'));
        $this->assertTrue(method_exists($component, 'showEditModal'));
        $this->assertTrue(method_exists($component, 'updateRole'));
        $this->assertTrue(method_exists($component, 'showDeleteModal'));
        $this->assertTrue(method_exists($component, 'deleteRole'));
        $this->assertTrue(method_exists($component, 'showAuditModal'));
        $this->assertTrue(method_exists($component, 'loadUserCounts'));
        $this->assertTrue(method_exists($component, 'getUserCountByRole'));
    }

    /** @test */
    public function role_component_has_required_properties()
    {
        $component = new RoleComponent();
        $reflection = new \ReflectionClass($component);
        
        $this->assertTrue($reflection->hasProperty('showCreateModal'));
        $this->assertTrue($reflection->hasProperty('showEditModal'));
        $this->assertTrue($reflection->hasProperty('showDeleteModal'));
        $this->assertTrue($reflection->hasProperty('showAuditModal'));
        $this->assertTrue($reflection->hasProperty('name'));
        $this->assertTrue($reflection->hasProperty('description'));
        $this->assertTrue($reflection->hasProperty('userCounts'));
    }

    /** @test */
    public function role_component_has_validation_rules()
    {
        $component = new RoleComponent();
        $reflection = new \ReflectionClass($component);
        
        $this->assertTrue($reflection->hasProperty('rules'));
        
        $rulesProperty = $reflection->getProperty('rules');
        $rulesProperty->setAccessible(true);
        $rules = $rulesProperty->getValue($component);
        
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
    }

    /** @test */
    public function role_component_has_listeners()
    {
        $component = new RoleComponent();
        $reflection = new \ReflectionClass($component);
        
        $this->assertTrue($reflection->hasProperty('listeners'));
        
        $listenersProperty = $reflection->getProperty('listeners');
        $listenersProperty->setAccessible(true);
        $listeners = $listenersProperty->getValue($component);
        
        $this->assertContains('refreshComponent', $listeners);
    }

    /** @test */
    public function role_component_has_custom_validation_messages()
    {
        $component = new RoleComponent();
        $reflection = new \ReflectionClass($component);
        
        $this->assertTrue($reflection->hasProperty('messages'));
        
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($component);
        
        $this->assertArrayHasKey('name.unique', $messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('name.regex', $messages);
    }

    /** @test */
    public function role_component_has_uniqueness_validation_method()
    {
        $component = new RoleComponent();
        $reflection = new \ReflectionClass($component);
        
        $this->assertTrue($reflection->hasMethod('validateRoleUniqueness'));
        $this->assertTrue($reflection->hasMethod('updatedName'));
    }
}