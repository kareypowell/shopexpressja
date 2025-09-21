<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditSetting;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Admin\AuditSettings;

class AuditSettingsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create superadmin role and user
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'superadmin'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'Super Administrator with full access'
            ]
        );
        
        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'email' => 'superadmin@test.com'
        ]);
    }

    /** @test */
    public function superadmin_can_access_audit_settings_page()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('audit-settings'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(AuditSettings::class);
    }

    /** @test */
    public function audit_settings_component_loads_default_settings()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->assertSet('retentionSettings.authentication', 365)
            ->assertSet('retentionSettings.financial_transaction', 2555)
            ->assertSet('alertThresholds.failed_login_attempts', 5)
            ->assertSet('notificationSettings.security_alerts_enabled', true);
    }

    /** @test */
    public function can_save_retention_settings()
    {
        $newRetentionSettings = [
            'authentication' => 180,
            'authorization' => 180,
            'model_created' => 90,
            'model_updated' => 90,
            'model_deleted' => 180,
            'business_action' => 120,
            'financial_transaction' => 2555,
            'system_event' => 60,
            'security_event' => 180,
        ];

        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->set('retentionSettings', $newRetentionSettings)
            ->call('saveRetentionSettings')
            ->assertHasNoErrors()
            ->assertDispatchedBrowserEvent('toastr:success');

        // Verify settings were saved
        $savedSettings = AuditSetting::get('retention_policy');
        $this->assertEquals(180, $savedSettings['authentication']);
        $this->assertEquals(90, $savedSettings['model_created']);
    }

    /** @test */
    public function can_save_alert_thresholds()
    {
        $newAlertThresholds = [
            'failed_login_attempts' => 10,
            'bulk_operation_threshold' => 100,
            'suspicious_activity_score' => 80,
        ];

        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->set('alertThresholds', $newAlertThresholds)
            ->call('saveAlertThresholds')
            ->assertHasNoErrors()
            ->assertDispatchedBrowserEvent('toastr:success');

        // Verify settings were saved
        $savedSettings = AuditSetting::get('alert_thresholds');
        $this->assertEquals(10, $savedSettings['failed_login_attempts']);
        $this->assertEquals(100, $savedSettings['bulk_operation_threshold']);
    }

    /** @test */
    public function can_save_notification_settings()
    {
        $newNotificationSettings = [
            'security_alerts_enabled' => false,
            'security_alert_recipients' => ['admin@test.com', 'security@test.com'],
            'daily_summary_enabled' => true,
            'weekly_report_enabled' => true,
        ];

        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->set('notificationSettings', $newNotificationSettings)
            ->call('saveNotificationSettings')
            ->assertHasNoErrors()
            ->assertDispatchedBrowserEvent('toastr:success');

        // Verify settings were saved
        $savedSettings = AuditSetting::get('notification_settings');
        $this->assertFalse($savedSettings['security_alerts_enabled']);
        $this->assertContains('admin@test.com', $savedSettings['security_alert_recipients']);
    }

    /** @test */
    public function validates_retention_settings()
    {
        $invalidRetentionSettings = [
            'authentication' => 0, // Invalid: must be at least 1
            'authorization' => 4000, // Invalid: exceeds maximum
            'model_created' => 'invalid', // Invalid: not numeric
        ];

        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->set('retentionSettings.authentication', 0)
            ->call('saveRetentionSettings')
            ->assertHasErrors(['retentionSettings.authentication']);
    }

    /** @test */
    public function validates_alert_thresholds()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->set('alertThresholds.failed_login_attempts', 0)
            ->call('saveAlertThresholds')
            ->assertHasErrors(['alertThresholds.failed_login_attempts']);
    }

    /** @test */
    public function validates_notification_recipients()
    {
        $invalidNotificationSettings = [
            'security_alerts_enabled' => true,
            'security_alert_recipients' => ['invalid-email', 'admin@test.com'],
            'daily_summary_enabled' => false,
            'weekly_report_enabled' => false,
        ];

        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->set('notificationSettings', $invalidNotificationSettings)
            ->call('saveNotificationSettings')
            ->assertHasErrors(['notificationSettings.security_alert_recipients.0']);
    }

    /** @test */
    public function can_add_and_remove_security_alert_recipients()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->call('addSecurityAlertRecipient')
            ->assertCount('notificationSettings.security_alert_recipients', 1)
            ->call('addSecurityAlertRecipient')
            ->assertCount('notificationSettings.security_alert_recipients', 2)
            ->call('removeSecurityAlertRecipient', 0)
            ->assertCount('notificationSettings.security_alert_recipients', 1);
    }

    /** @test */
    public function can_switch_between_tabs()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->assertSet('activeTab', 'retention')
            ->call('setActiveTab', 'alerts')
            ->assertSet('activeTab', 'alerts')
            ->call('setActiveTab', 'notifications')
            ->assertSet('activeTab', 'notifications');
    }

    /** @test */
    public function loads_system_health_data()
    {
        // Get initial counts
        $initialTotal = AuditLog::count();
        $initialLast24h = AuditLog::where('created_at', '>=', now()->subDay())->count();
        
        // Create some test audit logs using the existing superAdmin user
        AuditLog::factory()->count(10)->create([
            'user_id' => $this->superAdmin->id,
            'created_at' => now()->subHours(12)
        ]);
        
        AuditLog::factory()->count(5)->create([
            'user_id' => $this->superAdmin->id,
            'created_at' => now()->subDays(2)
        ]);

        $expectedTotal = $initialTotal + 15;
        $expectedLast24h = $initialLast24h + 10;

        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->call('setActiveTab', 'health')
            ->assertSet('systemHealth.total_logs', $expectedTotal)
            ->assertSet('systemHealth.logs_last_24h', $expectedLast24h);
    }

    /** @test */
    public function can_initialize_default_settings()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->call('initializeDefaultSettings')
            ->assertDispatchedBrowserEvent('toastr:success');

        // Verify default settings exist
        $this->assertDatabaseHas('audit_settings', [
            'setting_key' => 'retention_policy'
        ]);
    }

    /** @test */
    public function can_clear_settings_cache()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->call('clearSettingsCache')
            ->assertDispatchedBrowserEvent('toastr:success');
    }

    /** @test */
    public function regular_user_cannot_access_audit_settings()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)
            ->get(route('audit-settings'));

        $response->assertStatus(403);
    }

    /** @test */
    public function can_run_cleanup_preview()
    {
        // Create some old audit logs
        AuditLog::factory()->count(5)->create([
            'user_id' => $this->superAdmin->id,
            'event_type' => 'authentication',
            'created_at' => now()->subDays(400) // Older than default retention
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->call('getCleanupPreview')
            ->assertDispatchedBrowserEvent('toastr:info');
    }

    /** @test */
    public function can_run_cleanup_now()
    {
        // Create some old audit logs
        AuditLog::factory()->count(3)->create([
            'user_id' => $this->superAdmin->id,
            'event_type' => 'authentication',
            'created_at' => now()->subDays(400) // Older than default retention
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(AuditSettings::class)
            ->call('runCleanupNow')
            ->assertDispatchedBrowserEvent('toastr:success');
    }
}