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
        // Create roles with specific IDs to match existing test expectations
        if (!Role::find(1)) {
            Role::factory()->create(['id' => 1, 'name' => 'superadmin']);
        }
        
        if (!Role::find(2)) {
            Role::factory()->create(['id' => 2, 'name' => 'admin']);
        }
        
        if (!Role::find(3)) {
            Role::factory()->create(['id' => 3, 'name' => 'customer']);
        }
        
        if (!Role::find(4)) {
            Role::factory()->create(['id' => 4, 'name' => 'purchaser']);
        }
    }
}