<?php

namespace Tests\Feature;

use App\Http\Livewire\Manifests\ManifestAuditTrail;
use App\Models\Manifest;
use App\Models\ManifestAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Carbon\Carbon;

class ManifestAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $manifest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role_id' => 2]); // Admin role
        $this->manifest = Manifest::factory()->create();
        
        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_can_render_audit_trail_component()
    {
        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->assertStatus(200)
                  ->assertViewIs('livewire.manifests.manifest-audit-trail');
    }

    /** @test */
    public function it_displays_audit_records_chronologically()
    {
        // Create audit records with different timestamps
        $audit1 = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed',
            'reason' => 'All packages delivered',
            'performed_at' => Carbon::now()->subHours(2)
        ]);

        $audit2 = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Need to add missing package',
            'performed_at' => Carbon::now()->subHour()
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        // Should display most recent first
        $component->assertSeeInOrder([
            'Need to add missing package', // Most recent
            'All packages delivered'       // Older
        ]);
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
            'reason' => 'All packages delivered successfully'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->set('search', 'shipping')
                  ->assertSee('Need to correct shipping address')
                  ->assertDontSee('All packages delivered successfully');
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

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->set('actionFilter', 'unlocked')
                  ->assertSee('Unlock reason')
                  ->assertDontSee('Close reason');
    }

    /** @test */
    public function it_can_filter_by_user()
    {
        $otherUser = User::factory()->create(['role_id' => 2]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Admin unlock'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $otherUser->id,
            'action' => 'closed',
            'reason' => 'Other user close'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->set('userFilter', $this->admin->id)
                  ->assertSee('Admin unlock')
                  ->assertDontSee('Other user close');
    }

    /** @test */
    public function it_can_filter_by_date_range()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Recent unlock',
            'performed_at' => Carbon::now()
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed',
            'reason' => 'Old close',
            'performed_at' => Carbon::now()->subDays(5)
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->set('dateFrom', Carbon::now()->subDay()->format('Y-m-d'))
                  ->set('dateTo', Carbon::now()->format('Y-m-d'))
                  ->assertSee('Recent unlock')
                  ->assertDontSee('Old close');
    }

    /** @test */
    public function it_can_clear_all_filters()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Test unlock'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->set('search', 'test')
                  ->set('actionFilter', 'unlocked')
                  ->set('userFilter', $this->admin->id)
                  ->call('clearFilters')
                  ->assertSet('search', '')
                  ->assertSet('actionFilter', '')
                  ->assertSet('userFilter', '');
    }

    /** @test */
    public function it_can_export_audit_trail_as_csv()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Export test reason',
            'performed_at' => Carbon::now()
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->call('exportAuditTrail')
                  ->assertEmitted('downloadFile')
                  ->assertDispatchedBrowserEvent('toastr:success');
    }

    /** @test */
    public function it_prevents_export_without_permission()
    {
        $customer = User::factory()->create(['role_id' => 3]); // Customer role
        $this->actingAs($customer);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->call('exportAuditTrail');
        
        // Check if the error was added
        $this->assertTrue($component->instance()->getErrorBag()->has('export'));
    }

    /** @test */
    public function it_displays_correct_action_labels()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'auto_complete',
            'reason' => 'All packages delivered automatically'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->assertSee('Auto-closed (All Delivered)');
    }

    /** @test */
    public function it_paginates_audit_records()
    {
        // Create more records than the per-page limit
        ManifestAudit::factory()->count(15)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        // Should show pagination controls
        $component->assertSee('Showing')
                  ->assertSee('of 15 records');
    }

    /** @test */
    public function it_resets_page_when_filters_change()
    {
        // Create enough records to have multiple pages
        ManifestAudit::factory()->count(15)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        // Go to page 2
        $component->set('page', 2);

        // Change filter - should reset to page 1
        $component->set('search', 'test')
                  ->assertSet('page', 1);
    }

    /** @test */
    public function it_shows_empty_state_when_no_records()
    {
        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->assertSee('No audit records found')
                  ->assertSee('No locking operations have been performed on this manifest yet.');
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

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->set('search', 'nonexistent')
                  ->assertSee('No audit records found')
                  ->assertSee('Try adjusting your filters to see more results.');
    }

    /** @test */
    public function it_generates_correct_csv_content()
    {
        $audit = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Test CSV export',
            'performed_at' => Carbon::parse('2023-01-01 12:00:00')
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('generateCsvContent');
        $method->setAccessible(true);

        $csvContent = $method->invoke($component->instance(), collect([$audit]));

        $this->assertStringContainsString('"Date/Time","Action","User","User Email","Reason","Manifest ID"', $csvContent);
        $this->assertStringContainsString('2023-01-01 12:00:00', $csvContent);
        $this->assertStringContainsString('Unlocked', $csvContent);
        $this->assertStringContainsString($this->admin->name, $csvContent);
        $this->assertStringContainsString('Test CSV export', $csvContent);
    }

    /** @test */
    public function it_handles_csv_special_characters_correctly()
    {
        $audit = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked',
            'reason' => 'Reason with "quotes" and, commas',
            'performed_at' => Carbon::now()
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('generateCsvContent');
        $method->setAccessible(true);

        $csvContent = $method->invoke($component->instance(), collect([$audit]));

        // Should properly escape quotes and handle commas
        $this->assertStringContainsString('Reason with ""quotes"" and, commas', $csvContent);
    }

    /** @test */
    public function it_only_shows_audits_for_current_manifest()
    {
        $otherManifest = Manifest::factory()->create();

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'reason' => 'Current manifest audit'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $otherManifest->id,
            'user_id' => $this->admin->id,
            'reason' => 'Other manifest audit'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->assertSee('Current manifest audit')
                  ->assertDontSee('Other manifest audit');
    }

    /** @test */
    public function it_sets_default_date_range_on_mount()
    {
        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $expectedFromDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $expectedToDate = Carbon::now()->format('Y-m-d');

        $component->assertSet('dateFrom', $expectedFromDate)
                  ->assertSet('dateTo', $expectedToDate);
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

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->assertSee($this->admin->name)
                  ->assertSee($this->admin->email);
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

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->assertSee('System')
                  ->assertSee('N/A'); // For email field
    }
}