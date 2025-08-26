<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestTabSwitchingBrowserTest extends TestCase
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
    public function it_renders_manifest_tabs_page_correctly()
    {
        // Create a manifest with packages
        $manifest = Manifest::factory()->create();
        
        // Create individual packages
        Package::factory()->count(5)->create([
            'manifest_id' => $manifest->id,
            'consolidated_package_id' => null
        ]);

        // Visit the manifest packages page
        $response = $this->get("/admin/manifests/{$manifest->id}/packages");
        
        $response->assertStatus(200);
        
        // Check that the tab container is rendered
        $response->assertSee('Individual Packages');
        $response->assertSee('Consolidated Packages');
        
        // Check that Livewire components are included
        $response->assertSee('wire:id');
        
        // Check that Alpine.js is working (manifestTabs function should be present)
        $response->assertSee('manifestTabs()');
    }

    /** @test */
    public function it_includes_proper_javascript_for_tab_switching()
    {
        $manifest = Manifest::factory()->create();
        
        $response = $this->get("/admin/manifests/{$manifest->id}/packages");
        
        $response->assertStatus(200);
        
        // Check that the JavaScript functions are included
        $response->assertSee('switchTab');
        $response->assertSee('nextTab');
        $response->assertSee('prevTab');
        $response->assertSee('updateTabVisibility');
        
        // Check that event listeners are set up
        $response->assertSee('tabSwitched');
        $response->assertSee('refreshTabContent');
    }

    /** @test */
    public function it_has_proper_alpine_js_directives()
    {
        $manifest = Manifest::factory()->create();
        
        $response = $this->get("/admin/manifests/{$manifest->id}/packages");
        
        $response->assertStatus(200);
        
        // Check for Alpine.js directives
        $response->assertSee('x-data="manifestTabs()"');
        $response->assertSee('x-init="init()"');
        $response->assertSee('x-show="$wire.activeTab');
        $response->assertSee('wire:click="switchTab');
    }
}