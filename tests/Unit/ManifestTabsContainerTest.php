<?php

namespace Tests\Unit;

use App\Http\Livewire\Manifests\ManifestTabsContainer;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManifestTabsContainerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->manifest = Manifest::factory()->create();
        
        // Create some test packages
        Package::factory()->count(3)->create(['manifest_id' => $this->manifest->id]);
        
        // Create consolidated packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => $consolidatedPackage->id
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_mount_with_default_tab()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->assertSet('activeTab', 'individual');
        $component->assertSet('manifest.id', $this->manifest->id);
    }

    /** @test */
    public function it_can_mount_with_specified_tab()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest,
            'activeTab' => 'individual'
        ]);
        
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_validates_invalid_tab_names()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest,
            'activeTab' => 'invalid_tab'
        ]);
        
        $component->assertSet('activeTab', 'consolidated');
    }

    /** @test */
    public function it_can_switch_tabs()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('switchTab', 'individual');
        $component->assertSet('activeTab', 'individual');
        
        $component->call('switchTab', 'consolidated');
        $component->assertSet('activeTab', 'consolidated');
    }

    /** @test */
    public function it_validates_tab_names_when_switching()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('switchTab', 'invalid_tab');
        $component->assertSet('activeTab', 'consolidated');
    }

    /** @test */
    public function it_emits_events_when_switching_tabs()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('switchTab', 'individual');
        
        $component->assertEmitted('tabSwitched', 'individual');
        $component->assertDispatchedBrowserEvent('tab-switched', [
            'tab' => 'individual',
            'manifestId' => $this->manifest->id
        ]);
    }

    /** @test */
    public function it_does_not_emit_events_when_switching_to_same_tab()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('switchTab', 'consolidated');
        
        $component->assertNotEmitted('tabSwitched');
    }

    /** @test */
    public function it_preserves_tab_state_in_session()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('switchTab', 'individual');
        
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        $tabState = session()->get($sessionKey);
        
        $this->assertNotNull($tabState);
        $this->assertEquals('individual', $tabState['activeTab']);
        $this->assertEquals($this->manifest->id, $tabState['manifestId']);
        $this->assertArrayHasKey('timestamp', $tabState);
    }

    /** @test */
    public function it_can_restore_tab_state_from_session()
    {
        // Set up session state
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
    public function it_ignores_old_session_state()
    {
        // Set up old session state (more than 1 hour old)
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        session()->put($sessionKey, [
            'activeTab' => 'individual',
            'timestamp' => now()->subHours(2)->timestamp,
            'manifestId' => $this->manifest->id
        ]);
        
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        $component->call('restoreTabState');
        
        $component->assertSet('activeTab', 'consolidated');
    }

    /** @test */
    public function it_handles_tab_state_change_events()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->emit('tabStateChanged', ['tab' => 'individual']);
        
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_ignores_tab_state_change_for_same_tab()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->emit('tabStateChanged', ['tab' => 'consolidated']);
        
        // Should not emit additional events
        $component->assertNotEmitted('tabSwitched');
    }

    /** @test */
    public function it_dispatches_url_update_events()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('updateUrl');
        
        $component->assertDispatchedBrowserEvent('update-url', [
            'tab' => 'consolidated',
            'manifestId' => $this->manifest->id
        ]);
    }

    /** @test */
    public function it_provides_tabs_property_with_correct_data()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $tabs = $component->get('tabs');
        
        $this->assertArrayHasKey('consolidated', $tabs);
        $this->assertArrayHasKey('individual', $tabs);
        
        $this->assertEquals('Consolidated Packages', $tabs['consolidated']['name']);
        $this->assertEquals('Individual Packages', $tabs['individual']['name']);
        
        $this->assertEquals('archive-box', $tabs['consolidated']['icon']);
        $this->assertEquals('cube', $tabs['individual']['icon']);
        
        $this->assertArrayHasKey('count', $tabs['consolidated']);
        $this->assertArrayHasKey('count', $tabs['individual']);
        $this->assertArrayHasKey('aria_label', $tabs['consolidated']);
        $this->assertArrayHasKey('aria_label', $tabs['individual']);
    }

    /** @test */
    public function it_provides_active_tab_data_property()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $activeTabData = $component->get('activeTabData');
        
        $this->assertEquals('Consolidated Packages', $activeTabData['name']);
        $this->assertEquals('archive-box', $activeTabData['icon']);
        
        $component->call('switchTab', 'individual');
        
        $activeTabData = $component->get('activeTabData');
        $this->assertEquals('Individual Packages', $activeTabData['name']);
        $this->assertEquals('cube', $activeTabData['icon']);
    }

    /** @test */
    public function it_counts_consolidated_packages_correctly()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $tabs = $component->get('tabs');
        
        // We created 1 consolidated package in setUp
        $this->assertEquals(1, $tabs['consolidated']['count']);
    }

    /** @test */
    public function it_counts_individual_packages_correctly()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $tabs = $component->get('tabs');
        
        // We created 3 individual packages (not consolidated) in setUp
        $this->assertEquals(3, $tabs['individual']['count']);
    }

    /** @test */
    public function it_renders_the_correct_view()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->assertViewIs('livewire.manifests.manifest-tabs-container');
    }

    /** @test */
    public function it_includes_manifest_in_view_data()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->assertViewHas('manifest', $this->manifest);
        $component->assertViewHas('activeTab', 'consolidated');
        $component->assertViewHas('tabs');
        $component->assertViewHas('activeTabData');
    }

    /** @test */
    public function it_handles_query_string_parameters()
    {
        // Test that the component properly handles query string parameters
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest])
            ->set('activeTab', 'individual');
        
        // The query string should be updated
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_emits_preserve_state_event_when_switching_tabs()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        $component->call('switchTab', 'individual');
        
        $component->assertEmitted('preserveTabState', 'individual');
    }

    /** @test */
    public function it_validates_tab_parameter_in_validate_tab_method()
    {
        $component = Livewire::test(ManifestTabsContainer::class, ['manifest' => $this->manifest]);
        
        // Test invalid tabs default to consolidated when switching
        $component->call('switchTab', 'invalid');
        $component->assertSet('activeTab', 'consolidated');
        
        $component->call('switchTab', '');
        $component->assertSet('activeTab', 'consolidated');
        
        // Test valid tabs work correctly
        $component->call('switchTab', 'individual');
        $component->assertSet('activeTab', 'individual');
        
        $component->call('switchTab', 'consolidated');
        $component->assertSet('activeTab', 'consolidated');
    }
}