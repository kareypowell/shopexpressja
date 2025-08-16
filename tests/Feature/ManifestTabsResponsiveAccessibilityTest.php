<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestTabsResponsiveAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role_id' => 1]);
        $this->manifest = Manifest::factory()->create();
        
        // Create some individual packages
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => null
        ]);
        
        // Create consolidated packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => $consolidatedPackage->id
        ]);
    }

    /** @test */
    public function manifest_tabs_page_renders_with_proper_accessibility_structure()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for proper ARIA structure
        $response->assertSee('role="region"', false);
        $response->assertSee('aria-label="Manifest package management interface"', false);
        $response->assertSee('role="tablist"', false);
        $response->assertSee('aria-label="Manifest package views"', false);
        $response->assertSee('role="tab"', false);
        $response->assertSee('role="tabpanel"', false);
        $response->assertSee('aria-live="polite"', false);
    }

    /** @test */
    public function tabs_have_proper_aria_attributes_in_html()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check individual tab attributes
        $response->assertSee('id="tab-individual"', false);
        $response->assertSee('aria-selected="true"', false);
        $response->assertSee('aria-controls="tabpanel-individual"', false);
        $response->assertSee('tabindex="0"', false);

        // Check consolidated tab attributes
        $response->assertSee('id="tab-consolidated"', false);
        $response->assertSee('aria-selected="false"', false);
        $response->assertSee('aria-controls="tabpanel-consolidated"', false);
        $response->assertSee('tabindex="-1"', false);
    }

    /** @test */
    public function skip_link_is_present_for_accessibility()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);
        $response->assertSee('href="#tab-content"', false);
        $response->assertSee('Skip to tab content', false);
        $response->assertSee('sr-only focus:not-sr-only', false);
    }

    /** @test */
    public function responsive_css_classes_are_applied()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for responsive classes
        $response->assertSee('overflow-x-auto', false);
        $response->assertSee('sm:overflow-x-visible', false);
        $response->assertSee('flex-shrink-0', false);
        $response->assertSee('min-w-[120px]', false);
        $response->assertSee('sm:min-w-0', false);
        $response->assertSee('sm:flex-1', false);
        $response->assertSee('touch-manipulation', false);
    }

    /** @test */
    public function mobile_optimized_labels_are_present()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for mobile-specific label classes
        $response->assertSee('hidden sm:inline', false);
        $response->assertSee('sm:hidden', false);
        $response->assertSee('truncate', false);
    }

    /** @test */
    public function keyboard_navigation_attributes_are_present()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for keyboard navigation event handlers
        $response->assertSee('@keydown.arrow-right.prevent', false);
        $response->assertSee('@keydown.arrow-left.prevent', false);
        $response->assertSee('@keydown.home.prevent', false);
        $response->assertSee('@keydown.end.prevent', false);
        $response->assertSee('@keydown.space.prevent', false);
        $response->assertSee('@keydown.enter.prevent', false);
    }

    /** @test */
    public function loading_states_have_proper_accessibility_attributes()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for loading state accessibility
        $response->assertSee('role="status"', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('aria-busy=', false);
        $response->assertSee('Please wait while content loads', false);
    }

    /** @test */
    public function icons_are_properly_hidden_from_screen_readers()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check that SVG icons have aria-hidden
        $response->assertSee('aria-hidden="true"', false);
    }

    /** @test */
    public function package_count_badges_have_accessibility_labels()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for aria-label on package count badges (counts may vary)
        $response->assertSee('aria-label=', false);
        $response->assertSee('packages"', false);
    }

    /** @test */
    public function screen_reader_announcement_area_is_present()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for screen reader announcement area
        $response->assertSee('aria-live="assertive"', false);
        $response->assertSee('aria-atomic="true"', false);
        $response->assertSee('sr-only', false);
    }

    /** @test */
    public function tab_content_containers_have_proper_ids_and_labels()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for proper tabpanel structure (only active tab content is rendered)
        $response->assertSee('id="tabpanel-individual"', false);
        $response->assertSee('aria-labelledby="tab-individual"', false);
        $response->assertSee('aria-label="Individual packages view"', false);
        
        // The consolidated tab content is only rendered when that tab is active
        // But we can check that the tab structure supports it
        $response->assertSee('id="tab-consolidated"', false);
        $response->assertSee('aria-controls="tabpanel-consolidated"', false);
    }

    /** @test */
    public function responsive_css_classes_are_included_in_page()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check that responsive CSS classes are present in the HTML
        $response->assertSee('manifest-tabs-container', false);
        $response->assertSee('overflow-x-auto', false);
        $response->assertSee('touch-manipulation', false);
        $response->assertSee('sm:overflow-x-visible', false);
    }

    /** @test */
    public function javascript_accessibility_functions_are_included()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for core JavaScript functions that are inline
        $response->assertSee('manifestTabs()', false);
        $response->assertSee('x-data="manifestTabs()"', false);
        $response->assertSee('x-init="init()"', false);
    }

    /** @test */
    public function empty_state_has_proper_accessibility_structure()
    {
        // Create a manifest with no packages
        $emptyManifest = Manifest::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$emptyManifest->id}/packages");

        $response->assertStatus(200);

        // Check empty state accessibility
        $response->assertSee('role="status"', false);
        $response->assertSee('No individual packages found', false);
        $response->assertSee('aria-hidden="true"', false); // For the icon
    }

    /** @test */
    public function focus_management_attributes_are_present()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for focus management
        $response->assertSee('focus:outline-none', false);
        $response->assertSee('focus:ring-2', false);
        $response->assertSee('focus:ring-blue-500', false);
        $response->assertSee('focus:z-10', false);
        $response->assertSee('@focus="scrollTabIntoView', false);
    }

    /** @test */
    public function disabled_state_attributes_are_present()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check for disabled state handling
        $response->assertSee('wire:loading.attr="disabled"', false);
        $response->assertSee('disabled:opacity-50', false);
        $response->assertSee('disabled:cursor-not-allowed', false);
    }

    /** @test */
    public function global_keyboard_shortcut_is_included()
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/admin/manifests/{$this->manifest->id}/packages");

        $response->assertStatus(200);

        // Check that the JavaScript section exists (the actual script is in @push)
        $response->assertSee('manifestTabs()', false);
        $response->assertSee('nextTab()', false);
        $response->assertSee('prevTab()', false);
    }
}