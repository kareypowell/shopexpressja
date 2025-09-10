<?php

namespace Tests\Browser;

use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Carbon\Carbon;

class ManifestAuditTrailBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $admin;
    protected $manifest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role_id' => 2]);
        $this->manifest = Manifest::factory()->create();
    }

    /** @test */
    public function it_displays_audit_trail_interface_correctly()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Test audit record',
            'performed_at' => Carbon::now()
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('Audit Trail')
                    ->assertSee('Complete history of manifest locking and unlocking operations')
                    ->assertSee('Export CSV')
                    ->assertSee('Test audit record')
                    ->assertSee($this->admin->name);
        });
    }

    /** @test */
    public function it_can_filter_by_search_term()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Need to correct shipping address'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed',
            'reason' => 'All packages delivered'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->type('input[wire\\:model="search"]', 'shipping')
                    ->waitForLivewire()
                    ->assertSee('Need to correct shipping address')
                    ->assertDontSee('All packages delivered');
        });
    }

    /** @test */
    public function it_can_filter_by_action_type()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Unlock reason'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed',
            'reason' => 'Close reason'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->select('select[wire\\:model="actionFilter"]', 'unlocked')
                    ->waitForLivewire()
                    ->assertSee('Unlock reason')
                    ->assertDontSee('Close reason');
        });
    }

    /** @test */
    public function it_can_filter_by_date_range()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Recent audit',
            'performed_at' => Carbon::now()
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed',
            'reason' => 'Old audit',
            'performed_at' => Carbon::now()->subDays(10)
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->type('input[wire\\:model="dateFrom"]', Carbon::now()->subDay()->format('Y-m-d'))
                    ->type('input[wire\\:model="dateTo"]', Carbon::now()->format('Y-m-d'))
                    ->waitForLivewire()
                    ->assertSee('Recent audit')
                    ->assertDontSee('Old audit');
        });
    }

    /** @test */
    public function it_can_clear_all_filters()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Test audit'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->type('input[wire\\:model="search"]', 'test')
                    ->select('select[wire\\:model="actionFilter"]', 'unlocked')
                    ->waitForLivewire()
                    ->click('button:contains("Clear Filters")')
                    ->waitForLivewire()
                    ->assertInputValue('input[wire\\:model="search"]', '')
                    ->assertSelected('select[wire\\:model="actionFilter"]', '');
        });
    }

    /** @test */
    public function it_displays_action_badges_with_correct_styling()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Unlocked audit'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed',
            'reason' => 'Closed audit'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'auto_complete',
            'reason' => 'Auto-complete audit'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('Unlocked')
                    ->assertSee('Closed')
                    ->assertSee('Auto-closed (All Delivered)')
                    // Check for badge styling classes
                    ->assertPresent('.bg-green-100.text-green-800')
                    ->assertPresent('.bg-red-100.text-red-800')
                    ->assertPresent('.bg-blue-100.text-blue-800');
        });
    }

    /** @test */
    public function it_shows_pagination_when_many_records()
    {
        // Create more records than the per-page limit
        ManifestAudit::factory()->count(15)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('Showing')
                    ->assertSee('of 15 records')
                    ->assertPresent('nav[role="navigation"]'); // Pagination controls
        });
    }

    /** @test */
    public function it_shows_empty_state_when_no_records()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('No audit records found')
                    ->assertSee('No locking operations have been performed on this manifest yet.');
        });
    }

    /** @test */
    public function it_shows_filtered_empty_state()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Test reason'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->type('input[wire\\:model="search"]', 'nonexistent')
                    ->waitForLivewire()
                    ->assertSee('No audit records found')
                    ->assertSee('Try adjusting your filters to see more results.');
        });
    }

    /** @test */
    public function it_displays_user_information_correctly()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Test audit'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee($this->admin->name)
                    ->assertSee($this->admin->email);
        });
    }

    /** @test */
    public function it_handles_system_audits_without_user()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => null,
            'action' => 'auto_complete',
            'reason' => 'System auto-closure'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('System')
                    ->assertSee('N/A');
        });
    }

    /** @test */
    public function it_shows_full_reason_on_button_click()
    {
        $longReason = str_repeat('This is a very long reason that should be truncated in the display. ', 10);
        
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => $longReason
        ]);

        $this->browse(function (Browser $browser) use ($longReason) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('Show full reason')
                    ->click('button:contains("Show full reason")')
                    ->waitFor('.swal2-popup') // Wait for alert dialog
                    ->assertDialogOpened($longReason);
        });
    }

    /** @test */
    public function it_can_export_csv_successfully()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Export test'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->click('button:contains("Export CSV")')
                    ->waitForLivewire()
                    ->assertSee('Audit trail exported successfully.');
        });
    }

    /** @test */
    public function it_hides_export_button_for_unauthorized_users()
    {
        $customer = User::factory()->create(['role_id' => 1]); // Customer role

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Test audit'
        ]);

        $this->browse(function (Browser $browser) use ($customer) {
            $browser->loginAs($customer)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertDontSee('Export CSV');
        });
    }

    /** @test */
    public function it_displays_correct_date_and_time_format()
    {
        $testDate = Carbon::parse('2023-06-15 14:30:00');
        
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Date format test',
            'performed_at' => $testDate
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('Jun 15, 2023') // Date format
                    ->assertSee('2:30 PM');     // Time format
        });
    }

    /** @test */
    public function it_maintains_responsive_design_on_mobile()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Mobile test'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->resize(375, 667) // iPhone SE dimensions
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('Audit Trail')
                    ->assertSee('Mobile test')
                    // Check that table is scrollable on mobile
                    ->assertPresent('.overflow-hidden.shadow.ring-1');
        });
    }

    /** @test */
    public function it_updates_record_count_when_filters_change()
    {
        ManifestAudit::factory()->count(5)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked'
        ]);

        ManifestAudit::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->assertSee('Showing 8 of 8 records')
                    ->select('select[wire\\:model="actionFilter"]', 'unlocked')
                    ->waitForLivewire()
                    ->assertSee('Showing 5 of 5 records');
        });
    }

    /** @test */
    public function it_preserves_filters_in_url_query_string()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'URL test'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/admin/manifests/{$this->manifest->id}?activeTab=audit")
                    ->waitForLivewire()
                    ->type('input[wire\\:model="search"]', 'URL')
                    ->select('select[wire\\:model="actionFilter"]', 'unlocked')
                    ->waitForLivewire()
                    ->assertQueryStringHas('search', 'URL')
                    ->assertQueryStringHas('actionFilter', 'unlocked');
        });
    }
}