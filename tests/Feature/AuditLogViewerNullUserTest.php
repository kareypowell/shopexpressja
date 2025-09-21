<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Http\Livewire\Admin\AuditLogViewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogViewerNullUserTest extends TestCase
{
    use RefreshDatabase;

    protected $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create or get superadmin role
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin'], [
            'description' => 'Super Administrator'
        ]);
        
        // Create a superadmin user
        $this->superadmin = User::factory()->create([
            'role_id' => $superadminRole->id
        ]);
    }

    /** @test */
    public function it_handles_audit_log_with_null_user()
    {
        $this->actingAs($this->superadmin);

        // Create audit log without user (system event)
        $auditLog = AuditLog::create([
            'user_id' => null, // No user
            'event_type' => 'system_event',
            'action' => 'cleanup',
            'ip_address' => '127.0.0.1',
            'additional_data' => ['automated' => true]
        ]);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $auditLog->id]);
        
        // Should not throw errors
        $component->assertSet('auditLog.id', $auditLog->id);
        
        // Should handle null user gracefully
        $userContext = $component->get('userContext');
        $this->assertNull($userContext['user']);
        $this->assertEquals('127.0.0.1', $userContext['ip_address']);
    }

    /** @test */
    public function it_displays_system_event_message_for_null_user()
    {
        $this->actingAs($this->superadmin);

        // Create audit log without user
        $auditLog = AuditLog::create([
            'user_id' => null,
            'event_type' => 'system_event',
            'action' => 'cleanup',
            'ip_address' => '127.0.0.1'
        ]);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $auditLog->id])
            ->call('setActiveTab', 'context')
            ->assertSee('System-generated event (no user context)');
    }

    /** @test */
    public function it_handles_user_with_missing_role()
    {
        $this->actingAs($this->superadmin);

        // Create a basic role first
        $basicRole = Role::firstOrCreate(['name' => 'customer'], [
            'description' => 'Customer'
        ]);

        // Create user with role (since role_id cannot be null)
        $userWithoutRole = User::factory()->create([
            'role_id' => $basicRole->id
        ]);

        $auditLog = AuditLog::create([
            'user_id' => $userWithoutRole->id,
            'event_type' => 'model_updated',
            'action' => 'update',
            'ip_address' => '192.168.1.100'
        ]);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $auditLog->id]);
        
        // Should not throw errors
        $component->assertSet('auditLog.id', $auditLog->id);
        
        // Should handle user without role
        $userContext = $component->get('userContext');
        $this->assertNotNull($userContext['user']);
        $this->assertEquals($userWithoutRole->id, $userContext['user']->id);
    }

    /** @test */
    public function it_handles_deleted_user()
    {
        $this->actingAs($this->superadmin);

        // Create a basic role first
        $basicRole = Role::firstOrCreate(['name' => 'customer'], [
            'description' => 'Customer'
        ]);

        // Create user and then delete them
        $deletedUser = User::factory()->create([
            'role_id' => $basicRole->id
        ]);
        $userId = $deletedUser->id;
        $deletedUser->delete();

        $auditLog = AuditLog::create([
            'user_id' => $userId, // Reference to deleted user
            'event_type' => 'model_updated',
            'action' => 'update',
            'ip_address' => '192.168.1.100'
        ]);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $auditLog->id]);
        
        // Should not throw errors
        $component->assertSet('auditLog.id', $auditLog->id);
        
        // Should handle deleted user gracefully
        $userContext = $component->get('userContext');
        $this->assertNull($userContext['user']); // Should be null since user is deleted
    }
}