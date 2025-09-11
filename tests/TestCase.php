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
        // Create roles only if they don't exist to prevent unique constraint violations
        if (!Role::where('name', 'superadmin')->exists()) {
            Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        }
        
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin', 'description' => 'Administrator']);
        }
        
        if (!Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer', 'description' => 'Customer']);
        }
        
        if (!Role::where('name', 'purchaser')->exists()) {
            Role::create(['name' => 'purchaser', 'description' => 'Purchaser']);
        }
    }
}