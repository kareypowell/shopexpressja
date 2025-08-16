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

class ManifestTabsUrlStateIntegrationTest extends TestCase
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
    public function url_updates_when_switching_tabs()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test URL update emission when switching tabs
        Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest])
            ->assertSet('activeTab', 'individual')
            ->call('switchTab', 'consolidated')
            ->assertSet('activeTab', 'consolidated')
            ->assertDispatchedBrowserEvent('update-url', ['tab' => 'consolidated', 'manifestId' => $this->manifest->id])
            ->call('switchTab', 'individual')
            ->assertSet('activeTab', 'individual')
            ->assertDispatchedBrowserEvent('update-url', ['tab' => 'individual', 'manifestId' => $this->manifest->id]);
    }

    /** @test */
    public function url_parameter_sets_initial_active_tab()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test individual tab URL parameter
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Individual Packages');

        // Test consolidated tab URL parameter
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'consolidated'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Consolidated Packages');
    }

    /** @test */
    public function invalid_tab_parameter_defaults_to_first_tab()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test invalid tab parameter
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'invalid_tab'
        ]));

        $response->assertStatus(200);
        // Should default to individual tab
        $response->assertSee('Individual Packages');

        // Test component behavior with invalid tab
        Livewire::test('manifests.manifest-tabs-container', [
            'manifest' => $this->manifest,
            'activeTab' => 'invalid_tab'
        ])
            ->assertSet('activeTab', 'individual'); // Should default to individual
    }

    /** @test */
    public function browser_back_and_forward_navigation_works()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Simulate browser navigation history
        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest]);

        // Initial state
        $component->assertSet('activeTab', 'individual');

        // Switch to consolidated tab (simulates forward navigation)
        $component->call('switchTab', 'consolidated')
            ->assertSet('activeTab', 'consolidated')
            ->assertDispatchedBrowserEvent('update-url', ['tab' => 'consolidated', 'manifestId' => $this->manifest->id]);

        // Switch back to individual (simulates back navigation)
        $component->call('switchTab', 'individual')
            ->assertSet('activeTab', 'individual')
            ->assertDispatchedBrowserEvent('update-url', ['tab' => 'individual', 'manifestId' => $this->manifest->id]);
    }

    /** @test */
    public function bookmarkable_urls_work_correctly()
    {
        $this->actingAs($this->admin);

        // Create packages for both tabs
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        // Test bookmarking individual packages tab
        $individualUrl = route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual'
        ]);

        $response = $this->get($individualUrl);
        $response->assertStatus(200);
        $response->assertSee('Individual Packages');

        // Test bookmarking consolidated packages tab
        $consolidatedUrl = route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'consolidated'
        ]);

        $response = $this->get($consolidatedUrl);
        $response->assertStatus(200);
        $response->assertSee('Consolidated Packages');

        // Test workflow page bookmarking
        $workflowIndividualUrl = route('admin.manifests.workflow', [
            'manifest' => $this->manifest,
            'tab' => 'individual'
        ]);

        $response = $this->get($workflowIndividualUrl);
        $response->assertStatus(200);
        $response->assertSee('Individual Packages');
    }

    /** @test */
    public function url_state_persists_across_page_refreshes()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Visit page with individual tab parameter
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual'
        ]));

        $response->assertStatus(200);

        // Simulate page refresh by making another request with same URL
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Individual Packages');

        // Test component initialization with URL parameter
        Livewire::test('manifests.manifest-tabs-container', [
            'manifest' => $this->manifest,
            'activeTab' => 'individual'
        ])
            ->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function url_parameters_work_with_additional_query_parameters()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test URL with multiple parameters
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual',
            'search' => 'test',
            'status' => 'processing'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Individual Packages');

        // Test that tab parameter is preserved with other parameters
        $component = Livewire::test('manifests.manifest-tabs-container', [
            'manifest' => $this->manifest,
            'activeTab' => 'individual'
        ]);

        $component->call('switchTab', 'consolidated')
            ->assertDispatchedBrowserEvent('update-url', ['tab' => 'consolidated', 'manifestId' => $this->manifest->id]);
    }

    /** @test */
    public function url_encoding_handles_special_characters()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test URL with encoded parameters
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual',
            'search' => 'test%20search'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Individual Packages');
    }

    /** @test */
    public function url_state_works_with_ajax_requests()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test AJAX request with tab parameter
        $response = $this->getJson(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual'
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function url_state_preserves_tab_specific_filters()
    {
        $this->actingAs($this->admin);

        // Create packages with different statuses
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => 'processing',
        ]);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => 'ready',
        ]);

        // Test URL with tab and filter parameters
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual',
            'status_filter' => 'processing',
            'search' => 'test'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Individual Packages');

        // Test that switching tabs preserves the URL structure
        $component = Livewire::test('manifests.manifest-tabs-container', [
            'manifest' => $this->manifest,
            'activeTab' => 'individual'
        ]);

        $component->call('switchTab', 'consolidated')
            ->assertDispatchedBrowserEvent('update-url', ['tab' => 'consolidated', 'manifestId' => $this->manifest->id]);
    }

    /** @test */
    public function url_state_works_with_different_manifest_types()
    {
        $this->actingAs($this->admin);

        // Create air manifest
        $airManifest = Manifest::factory()->create(['type' => 'air']);
        
        // Create sea manifest
        $seaManifest = Manifest::factory()->create(['type' => 'sea']);

        Package::factory()->create([
            'manifest_id' => $airManifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        Package::factory()->create([
            'manifest_id' => $seaManifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test URL state with air manifest
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $airManifest,
            'tab' => 'individual'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Individual Packages');

        // Test URL state with sea manifest
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $seaManifest,
            'tab' => 'individual'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Individual Packages');
    }

    /** @test */
    public function url_state_handles_concurrent_tab_switches()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test rapid tab switching
        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest]);

        // Rapid switches
        $component->call('switchTab', 'individual')
            ->call('switchTab', 'consolidated')
            ->call('switchTab', 'individual')
            ->call('switchTab', 'consolidated');

        // Should end up on consolidated tab (last switch)
        $component->assertSet('activeTab', 'consolidated')
            ->assertDispatchedBrowserEvent('update-url', ['tab' => 'consolidated', 'manifestId' => $this->manifest->id]);
    }

    /** @test */
    public function url_state_works_with_deep_linking()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'DEEP123',
        ]);

        // Test deep linking to specific tab with search
        $response = $this->get(route('admin.manifests.packages', [
            'manifest' => $this->manifest,
            'tab' => 'individual',
            'search' => 'DEEP123'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Individual Packages');
        $response->assertSee('DEEP123');
    }
}