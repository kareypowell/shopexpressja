<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Http\Livewire\Admin\AuditLogViewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Carbon\Carbon;

class AuditLogViewerRelatedActivityTest extends TestCase
{
    use RefreshDatabase;

    protected $superadmin;
    protected $auditLog;

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
        
        // Create a test audit log entry
        $this->auditLog = AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'model_updated',
            'auditable_type' => Package::class,
            'auditable_id' => 1,
            'action' => 'update',
            'old_values' => [
                'status' => 'processing',
                'weight' => 2.5
            ],
            'new_values' => [
                'status' => 'ready',
                'weight' => 3.0
            ],
            'url' => '/admin/packages/1/edit',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'additional_data' => [
                'session_id' => 'test_session_123',
                'request_method' => 'PUT'
            ]
        ]);
    }

    /** @test */
    public function it_displays_related_activity_with_users()
    {
        $this->actingAs($this->superadmin);

        // Create related logs with users
        $relatedLog1 = AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.100',
            'created_at' => Carbon::now()->subMinutes(10)
        ]);

        $relatedLog2 = AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'model_created',
            'auditable_type' => Package::class,
            'auditable_id' => 2,
            'action' => 'create',
            'ip_address' => '192.168.1.100',
            'created_at' => Carbon::now()->addMinutes(5)
        ]);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->call('setActiveTab', 'related')
            ->assertSee('Related Activity')
            ->assertSee('Same User Activity')
            ->assertSee($this->superadmin->full_name)
            ->assertSee('Login')
            ->assertSee('Create');
    }

    /** @test */
    public function it_displays_related_activity_with_null_users()
    {
        $this->actingAs($this->superadmin);

        // Create related logs without users (system events)
        $systemLog = AuditLog::create([
            'user_id' => null, // No user
            'event_type' => 'system_event',
            'action' => 'cleanup',
            'ip_address' => '192.168.1.100',
            'created_at' => Carbon::now()->subMinutes(5)
        ]);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->call('setActiveTab', 'related')
            ->assertSee('Related Activity')
            ->assertSee('Same IP Activity')
            ->assertSee('System') // Should show "System" for null user
            ->assertSee('Cleanup');
    }

    /** @test */
    public function it_displays_related_activity_with_deleted_users()
    {
        $this->actingAs($this->superadmin);

        // Create a user and then delete them
        $customerRole = Role::firstOrCreate(['name' => 'customer'], [
            'description' => 'Customer'
        ]);
        
        $deletedUser = User::factory()->create([
            'role_id' => $customerRole->id
        ]);
        $deletedUserId = $deletedUser->id;
        $deletedUser->delete();

        // Create related log with deleted user
        $relatedLog = AuditLog::create([
            'user_id' => $deletedUserId,
            'event_type' => 'model_updated',
            'auditable_type' => Package::class,
            'auditable_id' => 3,
            'action' => 'update',
            'ip_address' => '192.168.1.100',
            'created_at' => Carbon::now()->subMinutes(5)
        ]);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->call('setActiveTab', 'related')
            ->assertSee('Related Activity')
            ->assertSee('Same IP Activity')
            ->assertSee('System') // Should show "System" for deleted user
            ->assertSee('Update');
    }

    /** @test */
    public function it_handles_empty_related_logs()
    {
        $this->actingAs($this->superadmin);

        // Create a different user for the isolated log
        $customerRole = Role::firstOrCreate(['name' => 'customer'], [
            'description' => 'Customer'
        ]);
        $isolatedUser = User::factory()->create([
            'role_id' => $customerRole->id
        ]);

        // Create audit log with unique data that won't have related logs
        $isolatedLog = AuditLog::create([
            'user_id' => $isolatedUser->id, // Different user
            'event_type' => 'unique_event',
            'action' => 'unique_action',
            'ip_address' => '10.0.0.1', // Different IP
            'created_at' => Carbon::now()->subDays(2) // Different time
        ]);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $isolatedLog->id]);
        
        // Check that relatedLogs is empty
        $relatedLogs = $component->get('relatedLogs');
        $this->assertTrue($relatedLogs->isEmpty(), 'Related logs should be empty');
    }

    /** @test */
    public function it_can_navigate_to_related_log()
    {
        $this->actingAs($this->superadmin);

        // Create related log
        $relatedLog = AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.100',
            'created_at' => Carbon::now()->subMinutes(10)
        ]);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->call('setActiveTab', 'related')
            ->assertSee('Related Activity')
            ->call('showAuditLogDetails', $relatedLog->id)
            ->assertSet('auditLogId', $relatedLog->id)
            ->assertSet('auditLog.action', 'login');
    }

    /** @test */
    public function it_groups_related_logs_correctly()
    {
        $this->actingAs($this->superadmin);

        // Create different types of related logs
        
        // Same user activity
        AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '10.0.0.1', // Different IP
            'created_at' => Carbon::now()->subMinutes(10)
        ]);

        // Create another user for different user scenarios
        $customerRole = Role::firstOrCreate(['name' => 'customer'], [
            'description' => 'Customer'
        ]);
        $otherUser = User::factory()->create([
            'role_id' => $customerRole->id
        ]);

        // Same entity changes
        AuditLog::create([
            'user_id' => $otherUser->id, // Different user
            'event_type' => 'model_created',
            'auditable_type' => Package::class,
            'auditable_id' => 1, // Same entity as main audit log
            'action' => 'create',
            'ip_address' => '10.0.0.2',
            'created_at' => Carbon::now()->subHours(2)
        ]);

        // Same IP activity
        AuditLog::create([
            'user_id' => $otherUser->id, // Different user
            'event_type' => 'system_event',
            'action' => 'cleanup',
            'ip_address' => '192.168.1.100', // Same IP as main audit log
            'created_at' => Carbon::now()->subHours(1)
        ]);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id]);
        
        $relatedLogs = $component->get('relatedLogs');
        
        // Should have multiple groups
        $this->assertGreaterThan(1, $relatedLogs->count());
        
        // Check that we have the expected groups
        $groupTitles = $relatedLogs->pluck('title')->toArray();
        $this->assertContains('Same User Activity', $groupTitles);
        $this->assertContains('Same Entity Changes', $groupTitles);
        $this->assertContains('Same IP Activity', $groupTitles);
    }
}