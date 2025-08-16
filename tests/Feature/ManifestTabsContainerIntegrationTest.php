<?php

namespace Tests\Feature;

use App\Http\Livewire\Manifests\ManifestTabsContainer;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManifestTabsContainerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->manifest = Manifest::factory()->create();
        
        // Create test data
        Package::factory()->count(5)->create(['manifest_id' => $this->manifest->id]);
        
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => $consolidatedPackage->id
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_renders_tabbed_interface_correctly()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->assertSee('Consolidated Packages');
        $component->assertSee('Individual Packages');
        $component->assertSee('role="tablist"', false);
        $component->assertSee('role="tab"', false);
        $component->assertSee('aria-selected="true"', false);
    }

    /** @test */
    public function it_displays_correct_package_counts_in_tabs()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Should show badge with consolidated package count (1)
        $component->assertSee('1'); // consolidated packages count
        
        // Should show badge with individual package count (5)
        $component->assertSee('5'); // individual packages count
    }

    /** @test */
    public function it_switches_tabs_and_updates_content()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Initially on consolidated tab
        $component->assertSee('Consolidated Packages');
        
        // Switch to individual tab
        $component->call('switchTab', 'individual');
        
        $component->assertSet('activeTab', 'individual');
        $component->assertSee('Individual Packages');
    }

    /** @test */
    public function it_preserves_state_across_tab_switches()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Switch to individual tab
        $component->call('switchTab', 'individual');
        
        // Check session state is preserved
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        $tabState = session()->get($sessionKey);
        
        $this->assertNotNull($tabState);
        $this->assertEquals('individual', $tabState['activeTab']);
        $this->assertEquals($this->manifest->id, $tabState['manifestId']);
    }

    /** @test */
    public function it_handles_url_query_parameters()
    {
        // Test with individual tab in query string
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest,
            'activeTab' => 'individual'
        ]);
        
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_emits_correct_events_for_child_components()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('switchTab', 'individual');
        
        // Should emit events for child components to handle
        $component->assertEmitted('tabSwitched', 'individual');
        $component->assertEmitted('preserveTabState', 'individual');
    }

    /** @test */
    public function it_dispatches_browser_events_for_url_management()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('switchTab', 'individual');
        
        $component->assertDispatchedBrowserEvent('tab-switched', [
            'tab' => 'individual',
            'manifestId' => $this->manifest->id
        ]);
        
        $component->assertDispatchedBrowserEvent('update-url', [
            'tab' => 'individual',
            'manifestId' => $this->manifest->id
        ]);
    }

    /** @test */
    public function it_handles_external_tab_state_changes()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Simulate external tab state change
        $component->emit('tabStateChanged', ['tab' => 'individual']);
        
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_restores_recent_session_state()
    {
        // Set up recent session state
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        session()->put($sessionKey, [
            'activeTab' => 'individual',
            'timestamp' => now()->timestamp,
            'manifestId' => $this->manifest->id
        ]);
        
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        $component->call('restoreTabState');
        
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_ignores_expired_session_state()
    {
        // Set up expired session state
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        session()->put($sessionKey, [
            'activeTab' => 'individual',
            'timestamp' => now()->subHours(2)->timestamp,
            'manifestId' => $this->manifest->id
        ]);
        
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        $component->call('restoreTabState');
        
        // Should remain on default tab
        $component->assertSet('activeTab', 'consolidated');
    }

    /** @test */
    public function it_provides_accessibility_attributes()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Check for proper ARIA attributes in the rendered HTML
        $component->assertSee('role="tablist"', false);
        $component->assertSee('role="tab"', false);
        $component->assertSee('role="tabpanel"', false);
        $component->assertSee('aria-selected="true"', false);
        $component->assertSee('aria-label=', false);
        $component->assertSee('aria-live="polite"', false);
    }

    /** @test */
    public function it_includes_loading_states()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Check for loading state elements
        $component->assertSee('wire:loading.delay', false);
        $component->assertSee('loading loading-spinner', false);
        $component->assertSee('wire:loading.remove', false);
    }

    /** @test */
    public function it_renders_child_components_correctly()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Should include child component directives
        $component->assertSee('Consolidated Packages');
        $component->assertSee('Individual Packages');
        
        // Should render the consolidated tab by default
        $component->assertSee('This tab will contain all consolidated packages functionality');
    }

    /** @test */
    public function it_includes_responsive_design_classes()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Check for responsive classes
        $component->assertSee('hidden sm:inline', false);
        $component->assertSee('sm:hidden', false);
        $component->assertSee('tabs-lg', false);
    }

    /** @test */
    public function it_handles_multiple_manifests_independently()
    {
        $manifest2 = Manifest::factory()->create();
        
        $component1 = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        $component2 = Livewire::test(ManifestTabsContainer::class, ['manifest' => $manifest2]);
        
        // Switch tabs on different manifests
        $component1->call('switchTab', 'individual');
        $component2->call('switchTab', 'consolidated');
        
        // Each should maintain its own state
        $component1->assertSet('activeTab', 'individual');
        $component2->assertSet('activeTab', 'consolidated');
        
        // Check session states are separate
        $sessionKey1 = "manifest_tabs_{$this->manifest->id}";
        $sessionKey2 = "manifest_tabs_{$manifest2->id}";
        
        $this->assertEquals('individual', session()->get($sessionKey1)['activeTab']);
        $this->assertEquals('consolidated', session()->get($sessionKey2)['activeTab']);
    }

    /** @test */
    public function it_validates_manifest_relationship()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Ensure the component has access to the manifest and its relationships
        $this->assertInstanceOf(Manifest::class, $component->get('manifest'));
        $this->assertEquals($this->manifest->id, $component->get('manifest')->id);
        
        // Test that package counts are calculated correctly
        $tabs = $component->get('tabs');
        $this->assertIsInt($tabs['consolidated']['count']);
        $this->assertIsInt($tabs['individual']['count']);
    }
}