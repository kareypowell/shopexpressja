<?php

namespace Tests\Unit;

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

        $this->admin = User::factory()->create(['role_id' => 2]);
        $this->manifest = Manifest::factory()->create();
        
        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_initializes_with_correct_default_values()
    {
        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $component->assertSet('search', '')
                  ->assertSet('actionFilter', '')
                  ->assertSet('userFilter', '')
                  ->assertSet('perPage', 10);

        // Check default date range (last 30 days)
        $expectedFromDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $expectedToDate = Carbon::now()->format('Y-m-d');

        $component->assertSet('dateFrom', $expectedFromDate)
                  ->assertSet('dateTo', $expectedToDate);
    }

    /** @test */
    public function it_resets_page_when_search_is_updated()
    {
        ManifestAudit::factory()->count(15)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        // Go to page 2
        $component->set('page', 2);

        // Update search - should reset page
        $component->set('search', 'test')
                  ->assertSet('page', 1);
    }

    /** @test */
    public function it_resets_page_when_filters_are_updated()
    {
        ManifestAudit::factory()->count(15)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        // Go to page 2
        $component->set('page', 2);

        // Update each filter and verify page reset
        $component->set('actionFilter', 'unlocked')
                  ->assertSet('page', 1);

        $component->set('page', 2)
                  ->set('dateFrom', '2023-01-01')
                  ->assertSet('page', 1);

        $component->set('page', 2)
                  ->set('dateTo', '2023-12-31')
                  ->assertSet('page', 1);

        $component->set('page', 2)
                  ->set('userFilter', $this->admin->id)
                  ->assertSet('page', 1);
    }

    /** @test */
    public function it_gets_available_actions_correctly()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'auto_complete'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getAvailableActions');
        $method->setAccessible(true);

        $actions = $method->invoke($component->instance());

        $this->assertCount(3, $actions);
        
        $actionValues = $actions->pluck('value')->toArray();
        $this->assertContains('unlocked', $actionValues);
        $this->assertContains('closed', $actionValues);
        $this->assertContains('auto_complete', $actionValues);

        $actionLabels = $actions->pluck('label')->toArray();
        $this->assertContains('Unlocked', $actionLabels);
        $this->assertContains('Closed', $actionLabels);
        $this->assertContains('Auto-closed (All Delivered)', $actionLabels);
    }

    /** @test */
    public function it_gets_available_users_correctly()
    {
        $user1 = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com']);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $user1->id
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $user2->id
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getAvailableUsers');
        $method->setAccessible(true);

        $users = $method->invoke($component->instance());

        $this->assertCount(2, $users);
        
        $userLabels = $users->pluck('label')->toArray();
        $this->assertContains('John Doe (john@example.com)', $userLabels);
        $this->assertContains('Jane Smith (jane@example.com)', $userLabels);
    }

    /** @test */
    public function it_gets_action_labels_correctly()
    {
        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getActionLabel');
        $method->setAccessible(true);

        $this->assertEquals('Closed', $method->invoke($component->instance(), 'closed'));
        $this->assertEquals('Unlocked', $method->invoke($component->instance(), 'unlocked'));
        $this->assertEquals('Auto-closed (All Delivered)', $method->invoke($component->instance(), 'auto_complete'));
        $this->assertEquals('Unknown', $method->invoke($component->instance(), 'unknown'));
    }

    /** @test */
    public function it_filters_audits_by_search_term_in_reason()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'reason' => 'Need to correct shipping address'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'reason' => 'Package delivery completed'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getFilteredAudits');
        $method->setAccessible(true);

        // Set search term
        $component->set('search', 'shipping');

        $audits = $method->invoke($component->instance(), false);

        $this->assertCount(1, $audits);
        $this->assertEquals('Need to correct shipping address', $audits->first()->reason);
    }

    /** @test */
    public function it_filters_audits_by_search_term_in_user_name()
    {
        $user1 = User::factory()->create(['first_name' => 'John', 'last_name' => 'Shipping Manager']);
        $user2 = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Delivery Coordinator']);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $user1->id,
            'reason' => 'First audit'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $user2->id,
            'reason' => 'Second audit'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getFilteredAudits');
        $method->setAccessible(true);

        // Set search term
        $component->set('search', 'shipping');

        $audits = $method->invoke($component->instance(), false);

        $this->assertCount(1, $audits);
        $this->assertEquals('First audit', $audits->first()->reason);
    }

    /** @test */
    public function it_filters_audits_by_action()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'unlocked'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'action' => 'closed'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getFilteredAudits');
        $method->setAccessible(true);

        // Set action filter
        $component->set('actionFilter', 'unlocked');

        $audits = $method->invoke($component->instance(), false);

        $this->assertCount(1, $audits);
        $this->assertEquals('unlocked', $audits->first()->action);
    }

    /** @test */
    public function it_filters_audits_by_date_range()
    {
        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'performed_at' => Carbon::parse('2023-01-15')
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'performed_at' => Carbon::parse('2023-02-15')
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getFilteredAudits');
        $method->setAccessible(true);

        // Set date range
        $component->set('dateFrom', '2023-01-01')
                  ->set('dateTo', '2023-01-31');

        $audits = $method->invoke($component->instance(), false);

        $this->assertCount(1, $audits);
        $this->assertEquals('2023-01-15', $audits->first()->performed_at->format('Y-m-d'));
    }

    /** @test */
    public function it_orders_audits_by_performed_at_desc()
    {
        $audit1 = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'performed_at' => Carbon::now()->subDay(),
            'reason' => 'First audit'
        ]);

        $audit2 = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'performed_at' => Carbon::now(),
            'reason' => 'Second audit'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getFilteredAudits');
        $method->setAccessible(true);

        $audits = $method->invoke($component->instance(), false);

        // Should be ordered by performed_at desc (most recent first)
        $this->assertEquals('Second audit', $audits->first()->reason);
        $this->assertEquals('First audit', $audits->last()->reason);
    }

    /** @test */
    public function it_generates_csv_with_proper_headers()
    {
        $audit = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('generateCsvContent');
        $method->setAccessible(true);

        $csvContent = $method->invoke($component->instance(), collect([$audit]));

        $lines = explode("\n", $csvContent);
        $headers = str_getcsv($lines[0]);

        $expectedHeaders = [
            'Date/Time',
            'Action',
            'User',
            'User Email',
            'Reason',
            'Manifest ID'
        ];

        $this->assertEquals($expectedHeaders, $headers);
    }

    /** @test */
    public function it_handles_null_user_in_csv_generation()
    {
        $audit = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => null,
            'action' => 'auto_complete',
            'reason' => 'System closure'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('generateCsvContent');
        $method->setAccessible(true);

        $csvContent = $method->invoke($component->instance(), collect([$audit]));

        $this->assertStringContainsString('System', $csvContent);
        $this->assertStringContainsString('N/A', $csvContent);
    }

    /** @test */
    public function it_properly_escapes_csv_content()
    {
        $audit = ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'reason' => 'Reason with "quotes" and, commas'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('generateCsvContent');
        $method->setAccessible(true);

        $csvContent = $method->invoke($component->instance(), collect([$audit]));

        // Should properly escape quotes by doubling them
        $this->assertStringContainsString('""quotes""', $csvContent);
    }

    /** @test */
    public function it_clears_filters_correctly()
    {
        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        // Set all filters
        $component->set('search', 'test')
                  ->set('actionFilter', 'unlocked')
                  ->set('userFilter', $this->admin->id)
                  ->set('dateFrom', '2023-01-01')
                  ->set('dateTo', '2023-12-31');

        // Clear filters
        $component->call('clearFilters');

        // Check that filters are cleared and dates are reset to default
        $expectedFromDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $expectedToDate = Carbon::now()->format('Y-m-d');

        $component->assertSet('search', '')
                  ->assertSet('actionFilter', '')
                  ->assertSet('userFilter', '')
                  ->assertSet('dateFrom', $expectedFromDate)
                  ->assertSet('dateTo', $expectedToDate);
    }

    /** @test */
    public function it_only_returns_audits_for_current_manifest()
    {
        $otherManifest = Manifest::factory()->create();

        ManifestAudit::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->admin->id,
            'reason' => 'Current manifest'
        ]);

        ManifestAudit::factory()->create([
            'manifest_id' => $otherManifest->id,
            'user_id' => $this->admin->id,
            'reason' => 'Other manifest'
        ]);

        $component = Livewire::test(ManifestAuditTrail::class, ['manifest' => $this->manifest]);

        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getFilteredAudits');
        $method->setAccessible(true);

        $audits = $method->invoke($component->instance(), false);

        $this->assertCount(1, $audits);
        $this->assertEquals('Current manifest', $audits->first()->reason);
    }
}