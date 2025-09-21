<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AuditSettingsBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

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
    public function can_access_audit_settings_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                    ->visit('/admin/audit-settings')
                    ->assertSee('Audit System Settings')
                    ->assertSee('Retention Policies')
                    ->assertSee('Alert Thresholds')
                    ->assertSee('Notifications');
        });
    }

    /** @test */
    public function can_switch_between_tabs()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                    ->visit('/admin/audit-settings')
                    ->click('@alerts-tab')
                    ->waitForText('Security Alert Thresholds')
                    ->click('@notifications-tab')
                    ->waitForText('Notification Settings')
                    ->click('@performance-tab')
                    ->waitForText('Performance Settings')
                    ->click('@export-tab')
                    ->waitForText('Export Settings')
                    ->click('@health-tab')
                    ->waitForText('System Health');
        });
    }

    /** @test */
    public function can_save_retention_settings()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                    ->visit('/admin/audit-settings')
                    ->type('@retention-authentication', '200')
                    ->type('@retention-authorization', '200')
                    ->click('@save-retention-button')
                    ->waitForText('Retention settings saved successfully');
        });
    }

    /** @test */
    public function can_save_alert_thresholds()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                    ->visit('/admin/audit-settings')
                    ->click('@alerts-tab')
                    ->waitForText('Security Alert Thresholds')
                    ->type('@alert-failed-login', '8')
                    ->type('@alert-bulk-operation', '75')
                    ->click('@save-alerts-button')
                    ->waitForText('Alert thresholds saved successfully');
        });
    }

    /** @test */
    public function can_manage_notification_recipients()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                    ->visit('/admin/audit-settings')
                    ->click('@notifications-tab')
                    ->waitForText('Notification Settings')
                    ->click('@add-recipient-button')
                    ->type('@recipient-0', 'admin@test.com')
                    ->click('@add-recipient-button')
                    ->type('@recipient-1', 'security@test.com')
                    ->click('@save-notifications-button')
                    ->waitForText('Notification settings saved successfully');
        });
    }

    /** @test */
    public function can_preview_cleanup()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                    ->visit('/admin/audit-settings')
                    ->click('@preview-cleanup-button')
                    ->waitForText('Cleanup preview');
        });
    }

    /** @test */
    public function can_initialize_default_settings()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                    ->visit('/admin/audit-settings')
                    ->click('@initialize-defaults-button')
                    ->waitForText('Default settings initialized successfully');
        });
    }
}