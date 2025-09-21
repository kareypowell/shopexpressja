<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class SimpleAuditObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up audit configuration
        Config::set('audit.auditable_models', [
            'App\Models\User',
        ]);
        
        Config::set('audit.excluded_fields', [
            'password',
            'remember_token',
            'api_token',
        ]);

        // Disable async logging for testing
        Config::set('audit.observer.async_logging', false);
        
        // Create a basic role for users if it doesn't exist
        Role::firstOrCreate([
            'name' => 'customer'
        ], [
            'description' => 'Customer role'
        ]);
    }

    /** @test */
    public function it_logs_user_creation_with_universal_audit_observer()
    {
        // Clear any existing audit logs
        AuditLog::truncate();
        
        $this->assertDatabaseCount('audit_logs', 0);

        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        $this->assertDatabaseCount('audit_logs', 1);
        
        $auditLog = AuditLog::first();
        $this->assertEquals('model_created', $auditLog->event_type);
        $this->assertEquals('create', $auditLog->action);
        $this->assertEquals(User::class, $auditLog->auditable_type);
        $this->assertEquals($user->id, $auditLog->auditable_id);
        $this->assertArrayHasKey('first_name', $auditLog->new_values);
        $this->assertEquals('John', $auditLog->new_values['first_name']);
        $this->assertArrayNotHasKey('password', $auditLog->new_values);
    }

    /** @test */
    public function it_logs_user_updates_with_universal_audit_observer()
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        // Clear the creation audit log
        AuditLog::truncate();

        $user->update([
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
        ]);

        $this->assertDatabaseCount('audit_logs', 1);
        
        $auditLog = AuditLog::first();
        $this->assertEquals('model_updated', $auditLog->event_type);
        $this->assertEquals('update', $auditLog->action);
        $this->assertEquals(User::class, $auditLog->auditable_type);
        $this->assertEquals($user->id, $auditLog->auditable_id);
        
        // Check old values
        $this->assertArrayHasKey('first_name', $auditLog->old_values);
        $this->assertEquals('John', $auditLog->old_values['first_name']);
        
        // Check new values
        $this->assertArrayHasKey('first_name', $auditLog->new_values);
        $this->assertEquals('Jane', $auditLog->new_values['first_name']);
        
        // Check additional data
        $this->assertArrayHasKey('changed_fields', $auditLog->additional_data);
        $this->assertContains('first_name', $auditLog->additional_data['changed_fields']);
        $this->assertContains('email', $auditLog->additional_data['changed_fields']);
    }

    /** @test */
    public function it_works_alongside_existing_user_observer()
    {
        // Clear any existing audit logs
        AuditLog::truncate();
        
        // Create a user which should trigger both UserObserver and UniversalAuditObserver
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        // Should have audit log from UniversalAuditObserver
        $this->assertDatabaseCount('audit_logs', 1);
        
        // UserObserver should also have run (this would be tested by checking cache invalidation)
        // For now, we just verify the user was created successfully
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        
        // Verify the audit log has the correct structure
        $auditLog = AuditLog::first();
        $this->assertEquals('model_created', $auditLog->event_type);
        $this->assertEquals(User::class, $auditLog->auditable_type);
        $this->assertEquals($user->id, $auditLog->auditable_id);
    }
}