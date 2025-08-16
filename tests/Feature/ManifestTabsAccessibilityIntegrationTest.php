<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Office;
use App\Models\Shipper;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class ManifestTabsAccessibilityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $manifest;
    protected $office;
    protected $shipper;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => 1,
            'email_verified_at' => now(),
        ]);

        // Create customer user
        $this->customer = User::factory()->create([
            'role_id' => 3,
            'email_verified_at' => now(),
        ]);

        // Create office and shipper
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();

        // Create manifest
        $this->manifest = Manifest::factory()->create();
    }

    /** @test */
    public function tab_components_have_proper_aria_attributes()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for proper ARIA attributes in the HTML
        $response->assertSee('role=&quot;tablist&quot;', false);
        $response->assertSee('role=&quot;tab&quot;', false);
        $response->assertSee('role=&quot;tabpanel&quot;', false);
        $response->assertSee('aria-selected', false);
        $response->assertSee('aria-controls', false);
        $response->assertSee('aria-labelledby', false);
    }

    /** @test */
    public function tab_navigation_provides_screen_reader_announcements()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test that tab switching emits screen reader announcements
        Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest])
            ->call('switchTab', 'individual')
            ->assertEmitted('announceToScreenReader', 'Individual packages tab selected')
            ->call('switchTab', 'consolidated')
            ->assertEmitted('announceToScreenReader', 'Consolidated packages tab selected');
    }

    /** @test */
    public function keyboard_navigation_works_correctly()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest]);

        // Test keyboard navigation events
        $component->call('handleKeyDown', 'ArrowRight')
            ->assertSet('activeTab', 'individual')
            ->assertEmitted('urlUpdated', ['tab' => 'individual']);

        $component->call('handleKeyDown', 'ArrowLeft')
            ->assertSet('activeTab', 'consolidated')
            ->assertEmitted('urlUpdated', ['tab' => 'consolidated']);

        $component->call('handleKeyDown', 'Home')
            ->assertSet('activeTab', 'consolidated'); // First tab

        $component->call('handleKeyDown', 'End')
            ->assertSet('activeTab', 'individual'); // Last tab
    }

    /** @test */
    public function focus_management_works_correctly()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest]);

        // Test focus management when switching tabs
        $component->call('switchTab', 'individual')
            ->assertEmitted('focusTab', 'individual');

        $component->call('switchTab', 'consolidated')
            ->assertEmitted('focusTab', 'consolidated');
    }

    /** @test */
    public function loading_states_are_announced_to_screen_readers()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        // Test loading state announcements
        $component->set('search', 'test')
            ->assertEmitted('announceToScreenReader', 'Searching packages...');

        // Test completion announcements
        $component->call('$refresh')
            ->assertEmitted('announceToScreenReader');
    }

    /** @test */
    public function error_states_are_accessible()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        // Test error state accessibility
        $component->set('selectedPackages', [999]) // Non-existent package
            ->set('bulkStatus', 'ready')
            ->call('confirmBulkStatusUpdate')
            ->assertHasErrors()
            ->assertEmitted('announceToScreenReader'); // Should announce error
    }

    /** @test */
    public function empty_states_have_proper_accessibility()
    {
        $this->actingAs($this->admin);

        // Test empty individual packages tab
        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));
        $response->assertStatus(200);

        // Check for proper heading structure and role attributes
        $response->assertSee('role=&quot;status&quot;', false);
        $response->assertSee('No individual packages found');

        // Test empty consolidated packages tab
        $consolidatedComponent = Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest]);
        
        $response->assertSee('No consolidated packages found');
    }

    /** @test */
    public function form_elements_have_proper_labels()
    {
        $this->actingAs($this->admin);

        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for proper form labels
        $response->assertSee('aria-label', false);
        $response->assertSee('for=', false); // Label associations

        // Test search input accessibility
        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);
        
        // Search input should have proper labeling
        $response->assertSee('Search packages');
    }

    /** @test */
    public function bulk_actions_are_accessible()
    {
        $this->actingAs($this->admin);

        $packages = Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        // Test bulk selection accessibility
        $component->call('selectAll')
            ->assertEmitted('announceToScreenReader', 'All packages selected');

        $component->call('deselectAll')
            ->assertEmitted('announceToScreenReader', 'All packages deselected');

        // Test bulk action announcements
        $component->set('selectedPackages', $packages->pluck('id')->toArray())
            ->set('bulkStatus', 'ready')
            ->call('confirmBulkStatusUpdate')
            ->assertEmitted('announceToScreenReader'); // Should announce completion
    }

    /** @test */
    public function table_elements_have_proper_accessibility()
    {
        $this->actingAs($this->admin);

        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for proper table accessibility
        $response->assertSee('role=&quot;table&quot;', false);
        $response->assertSee('role=&quot;columnheader&quot;', false);
        $response->assertSee('role=&quot;row&quot;', false);
        $response->assertSee('scope=&quot;col&quot;', false);
        $response->assertSee('aria-sort', false);
    }

    /** @test */
    public function sorting_controls_are_accessible()
    {
        $this->actingAs($this->admin);

        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        // Test sorting accessibility
        $component->call('sortBy', 'tracking_number')
            ->assertEmitted('announceToScreenReader', 'Sorted by tracking number ascending');

        $component->call('sortBy', 'tracking_number') // Second click for descending
            ->assertEmitted('announceToScreenReader', 'Sorted by tracking number descending');
    }

    /** @test */
    public function modal_dialogs_are_accessible()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        // Test modal accessibility
        $component->call('showFeeEntryModal', $package->id)
            ->assertEmitted('announceToScreenReader', 'Fee entry modal opened')
            ->assertEmitted('focusModal');

        $component->call('closeFeeEntryModal')
            ->assertEmitted('announceToScreenReader', 'Fee entry modal closed')
            ->assertEmitted('restoreFocus');
    }

    /** @test */
    public function pagination_controls_are_accessible()
    {
        $this->actingAs($this->admin);

        // Create enough packages to trigger pagination
        Package::factory()->count(25)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for pagination accessibility
        $response->assertSee('aria-label=&quot;Pagination&quot;', false);
        $response->assertSee('aria-current=&quot;page&quot;', false);
        $response->assertSee('Previous page');
        $response->assertSee('Next page');
    }

    /** @test */
    public function status_badges_have_accessible_text()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => 'processing',
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for accessible status indicators
        $response->assertSee('aria-label', false);
        $response->assertSee('Processing status');
    }

    /** @test */
    public function summary_information_is_accessible()
    {
        $this->actingAs($this->admin);

        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'weight' => 10.5,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for accessible summary
        $response->assertSee('Manifest Summary');
        $response->assertSee('aria-label', false);
        $response->assertSee('role=&quot;region&quot;', false);
    }

    /** @test */
    public function color_contrast_requirements_are_met()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for high contrast mode support
        $response->assertSee('prefers-contrast');
        $response->assertSee('high-contrast');
    }

    /** @test */
    public function reduced_motion_preferences_are_respected()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for reduced motion support
        $response->assertSee('prefers-reduced-motion');
    }

    /** @test */
    public function skip_links_are_provided()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);

        // Check for skip links
        $response->assertSee('Skip to main content');
        $response->assertSee('Skip to tab content');
    }

    /** @test */
    public function live_regions_announce_dynamic_content()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        // Test live region announcements for dynamic content
        $component->set('search', 'test')
            ->assertEmitted('announceToScreenReader');

        $component->set('statusFilter', 'processing')
            ->assertEmitted('announceToScreenReader');

        // Test package count updates
        $component->call('$refresh')
            ->assertEmitted('announceToScreenReader');
    }
}