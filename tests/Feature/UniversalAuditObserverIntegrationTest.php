<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

class UniversalAuditObserverIntegrationTest extends TestCase
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
    }

    /** @test */
    public function it_logs_user_creation()
    {
        // Clear any existing audit logs
        AuditLog::truncate();
        
        $this->assertDatabaseCount('audit_logs', 0);

        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
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
    public function it_logs_user_updates()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
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
    public function it_logs_user_deletion()
    {
        $user = User::factory()->create();
        
        // Clear the creation audit log
        AuditLog::truncate();

        $user->delete();

        $this->assertDatabaseCount('audit_logs', 1);
        
        $auditLog = AuditLog::first();
        $this->assertEquals('model_deleted', $auditLog->event_type);
        $this->assertEquals('delete', $auditLog->action);
        $this->assertEquals(User::class, $auditLog->auditable_type);
        $this->assertEquals($user->id, $auditLog->auditable_id);
        $this->assertNotNull($auditLog->old_values);
    }

    /** @test */
    public function it_logs_user_restoration()
    {
        $user = User::factory()->create();
        $user->delete();
        
        // Clear previous audit logs
        AuditLog::truncate();

        $user->restore();

        $this->assertDatabaseCount('audit_logs', 1);
        
        $auditLog = AuditLog::first();
        $this->assertEquals('business_action', $auditLog->event_type);
        $this->assertEquals('restore', $auditLog->action);
        $this->assertEquals(User::class, $auditLog->auditable_type);
        $this->assertEquals($user->id, $auditLog->auditable_id);
    }

    /** @test */
    public function it_logs_user_force_deletion()
    {
        $user = User::factory()->create();
        
        // Clear the creation audit log
        AuditLog::truncate();

        $user->forceDelete();

        $this->assertDatabaseCount('audit_logs', 1);
        
        $auditLog = AuditLog::first();
        $this->assertEquals('security_event', $auditLog->event_type);
        $this->assertEquals('force_delete', $auditLog->action);
        $this->assertArrayHasKey('severity', $auditLog->additional_data);
        $this->assertEquals('high', $auditLog->additional_data['severity']);
    }

    /** @test */
    public function it_respects_model_audit_exclusions()
    {
        $user = User::factory()->make();
        $user->auditingDisabled = true;
        $user->save();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    /** @test */
    public function it_excludes_sensitive_fields_from_audit()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret'),
            'remember_token' => 'token123',
        ]);

        $auditLog = AuditLog::first();
        $this->assertArrayNotHasKey('password', $auditLog->new_values);
        $this->assertArrayNotHasKey('remember_token', $auditLog->new_values);
    }

    /** @test */
    public function it_works_with_async_logging()
    {
        Queue::fake();
        Config::set('audit.observer.async_logging', true);

        $user = User::factory()->create();

        // Should not have created audit log synchronously
        $this->assertDatabaseCount('audit_logs', 0);
        
        // Should have queued the audit job
        Queue::assertPushed(\App\Jobs\ProcessAuditLogJob::class);
    }

    /** @test */
    public function it_handles_audit_failures_gracefully()
    {
        // Mock the audit service to throw an exception
        $this->mock(\App\Services\AuditService::class, function ($mock) {
            $mock->shouldReceive('logModelCreated')
                 ->andThrow(new \Exception('Audit service failed'));
        });

        // This should not throw an exception
        $user = User::factory()->create();
        
        // User should still be created despite audit failure
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_works_alongside_existing_observers()
    {
        // Create a user which should trigger both UserObserver and UniversalAuditObserver
        $user = User::factory()->create();

        // Should have audit log from UniversalAuditObserver
        $this->assertDatabaseCount('audit_logs', 1);
        
        // UserObserver should also have run (this would be tested by checking cache invalidation)
        // For now, we just verify the user was created successfully
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_includes_model_specific_audit_context()
    {
        $role = Role::factory()->create(['name' => 'customer']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $auditLog = AuditLog::first();
        
        // The User model should include audit context
        $this->assertArrayHasKey('model_name', $auditLog->additional_data);
        $this->assertEquals('User', $auditLog->additional_data['model_name']);
    }

    /** @test */
    public function it_respects_restoration_logging_configuration()
    {
        Config::set('audit.observer.log_restorations', false);
        
        $user = User::factory()->create();
        $user->delete();
        
        // Clear previous audit logs
        AuditLog::truncate();

        $user->restore();

        // Should not have logged the restoration
        $this->assertDatabaseCount('audit_logs', 0);
    }

    /** @test */
    public function it_respects_force_deletion_logging_configuration()
    {
        Config::set('audit.observer.log_force_deletions', false);
        
        $user = User::factory()->create();
        
        // Clear the creation audit log
        AuditLog::truncate();

        $user->forceDelete();

        // Should not have logged the force deletion
        $this->assertDatabaseCount('audit_logs', 0);
    }
}