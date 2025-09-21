<?php

namespace Tests\Browser;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuditLogViewerBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $superadmin;
    protected $auditLog;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create superadmin role
        $superadminRole = Role::create([
            'name' => 'superadmin',
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
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
    }

    /** @test */
    public function it_can_open_audit_log_viewer_modal()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superadmin)
                    ->visit('/admin/audit-logs')
                    ->waitFor('table')
                    ->assertSee('Audit Logs')
                    ->assertSee($this->superadmin->full_name)
                    ->click('button:contains("View Details")')
                    ->waitFor('[x-data*="showModal"]')
                    ->assertSee('Audit Log Details')
                    ->assertSee('model_updated')
                    ->assertSee('update');
        });
    }

    /** @test */
    public function it_can_switch_between_tabs_in_modal()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superadmin)
                    ->visit('/admin/audit-logs')
                    ->waitFor('table')
                    ->click('button:contains("View Details")')
                    ->waitFor('[x-data*="showModal"]')
                    ->assertSee('Details')
                    ->click('button:contains("Changes")')
                    ->waitFor('.space-y-4')
                    ->assertSee('Field Changes')
                    ->assertSee('status')
                    ->assertSee('processing')
                    ->assertSee('ready');
        });
    }

    /** @test */
    public function it_displays_before_after_value_comparison()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superadmin)
                    ->visit('/admin/audit-logs')
                    ->waitFor('table')
                    ->click('button:contains("View Details")')
                    ->waitFor('[x-data*="showModal"]')
                    ->click('button:contains("Changes")')
                    ->waitFor('.space-y-4')
                    ->assertSee('Previous Value')
                    ->assertSee('New Value')
                    ->assertSee('processing')
                    ->assertSee('ready')
                    ->assertSee('2.5')
                    ->assertSee('3');
        });
    }

    /** @test */
    public function it_displays_user_context_information()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superadmin)
                    ->visit('/admin/audit-logs')
                    ->waitFor('table')
                    ->click('button:contains("View Details")')
                    ->waitFor('[x-data*="showModal"]')
                    ->click('button:contains("User Context")')
                    ->waitFor('.space-y-6')
                    ->assertSee('User Information')
                    ->assertSee($this->superadmin->full_name)
                    ->assertSee($this->superadmin->email)
                    ->assertSee('192.168.1.100');
        });
    }

    /** @test */
    public function it_can_close_modal()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superadmin)
                    ->visit('/admin/audit-logs')
                    ->waitFor('table')
                    ->click('button:contains("View Details")')
                    ->waitFor('[x-data*="showModal"]')
                    ->assertSee('Audit Log Details')
                    ->click('button:contains("Close")')
                    ->waitUntilMissing('[x-data*="showModal"]')
                    ->assertDontSee('Audit Log Details');
        });
    }
}