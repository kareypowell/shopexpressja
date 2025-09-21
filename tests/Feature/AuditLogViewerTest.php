<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Package;
use App\Http\Livewire\Admin\AuditLogViewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    protected $superadmin;
    protected $auditLog;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create or get superadmin role
        $superadminRole = \App\Models\Role::firstOrCreate(['name' => 'superadmin'], [
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
                'weight' => 2.5,
                'description' => 'Old description'
            ],
            'new_values' => [
                'status' => 'ready',
                'weight' => 3.0,
                'description' => 'New description'
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
    public function it_can_load_audit_log_details()
    {
        $this->actingAs($this->superadmin);

        Livewire::test(AuditLogViewer::class)
            ->call('loadAuditLog', $this->auditLog->id)
            ->assertSet('auditLogId', $this->auditLog->id)
            ->assertSet('auditLog.id', $this->auditLog->id)
            ->assertSet('auditLog.event_type', 'model_updated')
            ->assertSet('auditLog.action', 'update');
    }

    /** @test */
    public function it_shows_modal_when_audit_log_details_event_is_emitted()
    {
        $this->actingAs($this->superadmin);

        Livewire::test(AuditLogViewer::class)
            ->emit('showAuditLogDetails', $this->auditLog->id)
            ->assertSet('showModal', true)
            ->assertSet('auditLogId', $this->auditLog->id);
    }

    /** @test */
    public function it_displays_basic_audit_log_information()
    {
        $this->actingAs($this->superadmin);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->assertSee('Model updated')
            ->assertSee('Update')
            ->assertSee('Package')
            ->assertSee('192.168.1.100')
            ->assertSee($this->superadmin->full_name);
    }

    /** @test */
    public function it_calculates_value_changes_correctly()
    {
        $this->actingAs($this->superadmin);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id]);
        
        $valueChanges = $component->get('valueChanges');
        
        $this->assertCount(3, $valueChanges);
        
        // Check status change
        $statusChange = collect($valueChanges)->firstWhere('field', 'status');
        $this->assertEquals('processing', $statusChange['old_value']);
        $this->assertEquals('ready', $statusChange['new_value']);
        $this->assertEquals('modified', $statusChange['change_type']);
        
        // Check weight change
        $weightChange = collect($valueChanges)->firstWhere('field', 'weight');
        $this->assertEquals(2.5, $weightChange['old_value']);
        $this->assertEquals(3.0, $weightChange['new_value']);
        $this->assertEquals('modified', $weightChange['change_type']);
        
        // Check description change
        $descriptionChange = collect($valueChanges)->firstWhere('field', 'description');
        $this->assertEquals('Old description', $descriptionChange['old_value']);
        $this->assertEquals('New description', $descriptionChange['new_value']);
        $this->assertEquals('modified', $descriptionChange['change_type']);
    }

    /** @test */
    public function it_handles_added_and_removed_values()
    {
        $this->actingAs($this->superadmin);

        // Create audit log with added and removed values
        $auditLog = AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'model_updated',
            'auditable_type' => Package::class,
            'auditable_id' => 2,
            'action' => 'update',
            'old_values' => [
                'status' => 'processing',
                'removed_field' => 'will be removed'
            ],
            'new_values' => [
                'status' => 'processing',
                'new_field' => 'newly added'
            ],
            'ip_address' => '192.168.1.100'
        ]);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $auditLog->id]);
        
        $valueChanges = $component->get('valueChanges');
        
        $this->assertCount(2, $valueChanges);
        
        // Check added field
        $addedChange = collect($valueChanges)->firstWhere('field', 'new_field');
        $this->assertNull($addedChange['old_value']);
        $this->assertEquals('newly added', $addedChange['new_value']);
        $this->assertEquals('added', $addedChange['change_type']);
        
        // Check removed field
        $removedChange = collect($valueChanges)->firstWhere('field', 'removed_field');
        $this->assertEquals('will be removed', $removedChange['old_value']);
        $this->assertNull($removedChange['new_value']);
        $this->assertEquals('removed', $removedChange['change_type']);
    }

    /** @test */
    public function it_displays_user_context_information()
    {
        $this->actingAs($this->superadmin);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id]);
        
        $userContext = $component->get('userContext');
        
        $this->assertEquals($this->superadmin->id, $userContext['user']->id);
        $this->assertEquals('192.168.1.100', $userContext['ip_address']);
        $this->assertEquals('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', $userContext['user_agent']);
        $this->assertEquals('/admin/packages/1/edit', $userContext['url']);
        $this->assertEquals('test_session_123', $userContext['session_id']);
    }

    /** @test */
    public function it_loads_related_logs_for_same_user()
    {
        $this->actingAs($this->superadmin);

        // Create related logs for the same user
        $baseTime = \Carbon\Carbon::now();
        
        $relatedLog1 = AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.100',
            'created_at' => $baseTime->copy()->subMinutes(10)
        ]);

        $relatedLog2 = AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'model_created',
            'auditable_type' => Package::class,
            'auditable_id' => 2,
            'action' => 'create',
            'ip_address' => '192.168.1.100',
            'created_at' => $baseTime->copy()->addMinutes(5)
        ]);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id]);
        
        $relatedLogs = $component->get('relatedLogs');
        
        $this->assertNotEmpty($relatedLogs);
        
        // Check that we have same user activity
        $sameUserActivity = collect($relatedLogs)->firstWhere('title', 'Same User Activity');
        $this->assertNotNull($sameUserActivity);
        $this->assertGreaterThan(0, $sameUserActivity['logs']->count());
    }

    /** @test */
    public function it_loads_related_logs_for_same_entity()
    {
        $this->actingAs($this->superadmin);

        // Create related logs for the same entity
        $relatedLog = AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'model_created',
            'auditable_type' => Package::class,
            'auditable_id' => 1, // Same entity as main audit log
            'action' => 'create',
            'ip_address' => '192.168.1.101',
            'created_at' => \Carbon\Carbon::now()->subHours(2)
        ]);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id]);
        
        $relatedLogs = $component->get('relatedLogs');
        
        $this->assertNotEmpty($relatedLogs);
        
        // Check that we have same entity changes
        $sameEntityChanges = collect($relatedLogs)->firstWhere('title', 'Same Entity Changes');
        $this->assertNotNull($sameEntityChanges);
        $this->assertGreaterThan(0, $sameEntityChanges['logs']->count());
    }

    /** @test */
    public function it_can_switch_between_tabs()
    {
        $this->actingAs($this->superadmin);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->assertSet('activeTab', 'details')
            ->call('setActiveTab', 'changes')
            ->assertSet('activeTab', 'changes')
            ->call('setActiveTab', 'context')
            ->assertSet('activeTab', 'context')
            ->call('setActiveTab', 'related')
            ->assertSet('activeTab', 'related');
    }

    /** @test */
    public function it_can_close_modal()
    {
        $this->actingAs($this->superadmin);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->set('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('auditLog', null)
            ->assertSet('auditLogId', null);
    }

    /** @test */
    public function it_formats_values_correctly()
    {
        $this->actingAs($this->superadmin);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id]);
        
        // Test null value formatting
        $nullFormatted = $component->instance()->formatValue(null);
        $this->assertStringContainsString('null', $nullFormatted);
        
        // Test boolean value formatting
        $trueFormatted = $component->instance()->formatValue(true);
        $this->assertStringContainsString('true', $trueFormatted);
        
        $falseFormatted = $component->instance()->formatValue(false);
        $this->assertStringContainsString('false', $falseFormatted);
        
        // Test array value formatting
        $arrayFormatted = $component->instance()->formatValue(['key' => 'value']);
        $this->assertStringContainsString('pre', $arrayFormatted);
        
        // Test long string formatting
        $longString = str_repeat('a', 150);
        $longFormatted = $component->instance()->formatValue($longString);
        $this->assertStringContainsString('Show More', $longFormatted);
    }

    /** @test */
    public function it_determines_correct_event_type_colors()
    {
        $this->actingAs($this->superadmin);

        $component = Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id]);
        
        $eventTypeColor = $component->get('eventTypeColor');
        
        // For model_updated event type, should be green
        $this->assertStringContainsString('bg-green-100 text-green-800', $eventTypeColor);
    }

    /** @test */
    public function it_handles_audit_log_not_found()
    {
        $this->actingAs($this->superadmin);

        Livewire::test(AuditLogViewer::class)
            ->call('loadAuditLog', 99999) // Non-existent ID
            ->assertHasErrors('auditLog'); // Should handle gracefully
    }

    /** @test */
    public function it_displays_changes_tab_when_value_changes_exist()
    {
        $this->actingAs($this->superadmin);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->assertSee('Changes (3)'); // Should show changes count
    }

    /** @test */
    public function it_displays_related_activity_tab_when_related_logs_exist()
    {
        $this->actingAs($this->superadmin);

        // Create a related log
        AuditLog::create([
            'user_id' => $this->superadmin->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.100',
            'created_at' => \Carbon\Carbon::now()->subMinutes(10)
        ]);

        Livewire::test(AuditLogViewer::class, ['auditLogId' => $this->auditLog->id])
            ->assertSee('Related Activity'); // Should show related activity tab
    }
}