<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManifestTabSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user for authentication
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_switch_between_tabs_and_load_content()
    {
        // Create a manifest with both individual packages
        $manifest = Manifest::factory()->create();
        
        // Create individual packages
        $individualPackages = Package::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'consolidated_package_id' => null
        ]);

        // Test the ManifestTabsContainer component
        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $manifest]);

        // Initially should be on individual tab
        $component->assertSet('activeTab', 'individual');

        // Switch to consolidated tab
        $component->call('switchTab', 'consolidated');
        $component->assertSet('activeTab', 'consolidated');
        $component->assertEmitted('tabSwitched', 'consolidated');
        $component->assertEmitted('refreshConsolidatedPackages');

        // Switch back to individual tab
        $component->call('switchTab', 'individual');
        $component->assertSet('activeTab', 'individual');
        $component->assertEmitted('tabSwitched', 'individual');
        $component->assertEmitted('refreshIndividualPackages');
    }

    /** @test */
    public function it_preserves_tab_state_in_session()
    {
        $manifest = Manifest::factory()->create();
        
        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $manifest]);

        // Switch to consolidated tab
        $component->call('switchTab', 'consolidated');
        
        // Check that state is preserved in session
        $sessionKey = "manifest_tabs_{$manifest->id}";
        $this->assertTrue(session()->has($sessionKey));
        
        $tabState = session()->get($sessionKey);
        $this->assertEquals('consolidated', $tabState['activeTab']);
        $this->assertEquals($manifest->id, $tabState['manifestId']);
    }

    /** @test */
    public function it_validates_tab_names()
    {
        $manifest = Manifest::factory()->create();
        
        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $manifest]);

        // Try to switch to invalid tab
        $component->call('switchTab', 'invalid_tab');
        
        // Should default to individual tab
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_handles_tab_switching_errors_gracefully()
    {
        $manifest = Manifest::factory()->create();
        
        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $manifest]);

        // Try to switch with malicious input
        $component->call('switchTab', '<script>alert("xss")</script>');
        
        // Should handle error and stay on current tab
        $component->assertSet('activeTab', 'individual');
        $component->assertSet('hasError', true);
    }

    /** @test */
    public function individual_packages_tab_loads_correctly()
    {
        $manifest = Manifest::factory()->create();
        
        // Create individual packages
        Package::factory()->count(5)->create([
            'manifest_id' => $manifest->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $manifest]);
        
        // Should load packages
        $component->assertViewHas('packages');
        $packages = $component->viewData('packages');
        $this->assertCount(5, $packages);
    }

    /** @test */
    public function consolidated_packages_tab_loads_correctly()
    {
        $manifest = Manifest::factory()->create();
        
        // Just create individual packages for now - consolidated packages require more complex setup
        Package::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $manifest]);
        
        // Should load consolidated packages (even if empty)
        $component->assertViewHas('consolidatedPackages');
    }

    /** @test */
    public function tab_components_respond_to_refresh_events()
    {
        $manifest = Manifest::factory()->create();
        
        // Test individual packages tab
        $individualComponent = Livewire::test('manifests.individual-packages-tab', ['manifest' => $manifest]);
        
        // Emit refresh event
        $individualComponent->emit('refreshIndividualPackages');
        
        // Component should handle the refresh
        $individualComponent->assertNotSet('hasError', true);
        
        // Test consolidated packages tab
        $consolidatedComponent = Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $manifest]);
        
        // Emit refresh event
        $consolidatedComponent->emit('refreshConsolidatedPackages');
        
        // Component should handle the refresh
        $consolidatedComponent->assertNotSet('hasError', true);
    }
}