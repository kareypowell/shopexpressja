<?php

namespace Tests\Browser;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Package;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuditLogManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $superAdminUser;
    protected $adminUser;
    protected $customerUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createTestUsers();
        $this->createTestAuditData();
    }

    protected function createTestUsers()
    {
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
    }

    protected function createTestAuditData()
    {
        // Create various audit log entries for testing
        
        // Authentication events
        AuditLog::factory()->count(5)->create([
            'user_id' => $this->customerUser->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.1'
        ]);

        // Security events
        AuditLog::factory()->count(3)->create([
            'event_type' => 'security_event',
            'action' => 'failed_authentication',
            'ip_address' => '192.168.1.100',
            'additional_data' => ['severity' => 'high']
        ]);

        // Business actions
        $package = Package::factory()->create(['user_id' => $this->customerUser->id]);
        AuditLog::factory()->count(2)->create([
            'user_id' => $this->adminUser->id,
            'event_type' => 'business_action',
            'action' => 'package_status_change',
            'auditable_type' => Package::class,
            'auditable_id' => $package->id
        ]);

        // Financial transactions
        AuditLog::factory()->count(4)->create([
            'user_id' => $this->customerUser->id,
            'event_type' => 'financial_transaction',
            'action' => 'charge_applied'
        ]);
    }

    /** @test */
    public function superadmin_can_access_audit_log_management_interface()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->assertSee('Audit Logs')
                    ->assertSee('Search audit logs')
                    ->assertSee('Event Type')
                    ->assertSee('Action')
                    ->assertSee('User')
                    ->assertSee('Date Range');
        });
    }

    /** @test */
    public function admin_can_access_audit_log_management_interface()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/audit-logs')
                    ->assertSee('Audit Logs')
                    ->assertSee('Search audit logs');
        });
    }

    /** @test */
    public function customer_cannot_access_audit_log_management_interface()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                    ->visit('/admin/audit-logs')
                    ->assertSee('403')
                    ->assertDontSee('Audit Logs');
        });
    }

    /** @test */
    public function it_displays_audit_log_entries_in_table()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->assertSee('authentication')
                    ->assertSee('login')
                    ->assertSee('security_event')
                    ->assertSee('failed_authentication')
                    ->assertSee('business_action')
                    ->assertSee('package_status_change')
                    ->assertSee('financial_transaction')
                    ->assertSee('charge_applied');
        });
    }

    /** @test */
    public function it_can_search_audit_logs_by_text()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->type('search', 'login')
                    ->pause(1000) // Wait for Livewire to process
                    ->assertSee('login')
                    ->assertDontSee('charge_applied');
        });
    }

    /** @test */
    public function it_can_filter_by_event_type()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->select('eventType', 'authentication')
                    ->pause(1000)
                    ->assertSee('authentication')
                    ->assertSee('login')
                    ->assertDontSee('security_event');
        });
    }

    /** @test */
    public function it_can_filter_by_action()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->select('action', 'failed_authentication')
                    ->pause(1000)
                    ->assertSee('failed_authentication')
                    ->assertDontSee('login');
        });
    }

    /** @test */
    public function it_can_filter_by_user()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->select('userId', $this->customerUser->id)
                    ->pause(1000)
                    ->assertSee($this->customerUser->first_name)
                    ->assertDontSee($this->adminUser->first_name);
        });
    }

    /** @test */
    public function it_can_filter_by_date_range()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->type('dateFrom', now()->subDays(7)->format('Y-m-d'))
                    ->type('dateTo', now()->format('Y-m-d'))
                    ->pause(1000)
                    ->assertSee('authentication')
                    ->assertSee('security_event');
        });
    }

    /** @test */
    public function it_can_apply_quick_filters()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click('@quick-filter-today')
                    ->pause(1000)
                    ->assertInputValue('dateFrom', now()->format('Y-m-d'))
                    ->assertInputValue('dateTo', now()->format('Y-m-d'));
        });
    }

    /** @test */
    public function it_can_apply_filter_presets()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->select('filterPreset', 'security_events')
                    ->pause(1000)
                    ->assertSelected('eventType', 'security_event')
                    ->assertSee('failed_authentication');
        });
    }

    /** @test */
    public function it_can_clear_all_filters()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->type('search', 'test')
                    ->select('eventType', 'authentication')
                    ->pause(500)
                    ->click('@clear-filters')
                    ->pause(1000)
                    ->assertInputValue('search', '')
                    ->assertSelected('eventType', '');
        });
    }

    /** @test */
    public function it_can_sort_audit_logs_by_different_columns()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click('@sort-event-type')
                    ->pause(1000)
                    ->assertSee('authentication')
                    ->click('@sort-created-at')
                    ->pause(1000);
        });
    }

    /** @test */
    public function it_can_change_pagination_size()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->select('perPage', '10')
                    ->pause(1000)
                    ->assertSee('Showing')
                    ->assertSee('of');
        });
    }

    /** @test */
    public function it_can_navigate_through_pagination()
    {
        // Create more audit logs to ensure pagination
        AuditLog::factory()->count(30)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->select('perPage', '10')
                    ->pause(1000)
                    ->assertSee('Next')
                    ->click('@next-page')
                    ->pause(1000)
                    ->assertSee('Previous');
        });
    }

    /** @test */
    public function it_can_view_audit_log_details()
    {
        $auditLog = AuditLog::first();

        $this->browse(function (Browser $browser) use ($auditLog) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click("@view-audit-{$auditLog->id}")
                    ->pause(1000)
                    ->assertSee('Audit Log Details')
                    ->assertSee($auditLog->event_type)
                    ->assertSee($auditLog->action);
        });
    }

    /** @test */
    public function it_can_export_audit_logs_to_csv()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click('@export-button')
                    ->pause(500)
                    ->assertSee('Export Audit Logs')
                    ->select('exportFormat', 'csv')
                    ->click('@confirm-export')
                    ->pause(3000)
                    ->assertSee('Export generated successfully');
        });
    }

    /** @test */
    public function it_can_export_audit_logs_to_pdf()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click('@export-button')
                    ->pause(500)
                    ->select('exportFormat', 'pdf')
                    ->click('@confirm-export')
                    ->pause(3000)
                    ->assertSee('Export generated successfully');
        });
    }

    /** @test */
    public function it_can_generate_compliance_report()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click('@compliance-report')
                    ->pause(3000)
                    ->assertSee('Compliance report generated successfully');
        });
    }

    /** @test */
    public function it_shows_advanced_search_options()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click('@toggle-filters')
                    ->pause(500)
                    ->assertSee('Search in Old Values')
                    ->assertSee('Search in New Values')
                    ->assertSee('Search in Additional Data')
                    ->assertSee('Search in URL')
                    ->assertSee('Search in User Agent');
        });
    }

    /** @test */
    public function it_can_search_in_json_fields()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click('@toggle-filters')
                    ->pause(500)
                    ->check('searchInAdditionalData')
                    ->type('search', 'severity')
                    ->pause(1000)
                    ->assertSee('security_event');
        });
    }

    /** @test */
    public function it_displays_user_information_in_audit_logs()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->assertSee($this->customerUser->first_name)
                    ->assertSee($this->customerUser->last_name)
                    ->assertSee($this->adminUser->first_name);
        });
    }

    /** @test */
    public function it_displays_ip_addresses_and_timestamps()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->assertSee('192.168.1.1')
                    ->assertSee('192.168.1.100')
                    ->assertSee(now()->format('M j, Y'));
        });
    }

    /** @test */
    public function it_handles_empty_search_results()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->type('search', 'nonexistentterm12345')
                    ->pause(1000)
                    ->assertSee('No audit logs found')
                    ->assertDontSee('authentication');
        });
    }

    /** @test */
    public function it_shows_loading_states_during_filtering()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->type('search', 'login')
                    ->assertSee('wire:loading') // Livewire loading indicator
                    ->pause(1000);
        });
    }

    /** @test */
    public function it_maintains_filter_state_in_url()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->type('search', 'login')
                    ->select('eventType', 'authentication')
                    ->pause(1000)
                    ->assertQueryStringHas('search', 'login')
                    ->assertQueryStringHas('eventType', 'authentication');
        });
    }

    /** @test */
    public function it_can_filter_by_ip_address()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->type('ipAddress', '192.168.1.1')
                    ->pause(1000)
                    ->assertSee('192.168.1.1')
                    ->assertDontSee('192.168.1.100');
        });
    }

    /** @test */
    public function it_shows_audit_log_statistics()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->assertSee('Total Entries')
                    ->assertSee('Authentication Events')
                    ->assertSee('Security Events')
                    ->assertSee('Business Actions');
        });
    }

    /** @test */
    public function it_can_refresh_audit_log_data()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser)
                    ->visit('/admin/audit-logs')
                    ->click('@refresh-data')
                    ->pause(1000)
                    ->assertSee('authentication')
                    ->assertSee('security_event');
        });
    }
}