<?php

namespace Tests;

use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create basic roles for all tests to prevent foreign key constraint violations
        $this->createBasicRoles();
    }

    /**
     * Create basic roles needed for tests
     */
    protected function createBasicRoles(): void
    {
        // Create roles without specifying IDs to avoid conflicts
        if (!Role::where('name', 'superadmin')->exists()) {
            Role::factory()->create(['name' => 'superadmin']);
        }
        
        if (!Role::where('name', 'admin')->exists()) {
            Role::factory()->create(['name' => 'admin']);
        }
        
        if (!Role::where('name', 'customer')->exists()) {
            Role::factory()->create(['name' => 'customer']);
        }
        
        if (!Role::where('name', 'purchaser')->exists()) {
            Role::factory()->create(['name' => 'purchaser']);
        }
    }
}