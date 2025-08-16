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

class ManifestTabsContainerIntegrationTest extends TestCase
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

        // Create roles
        $adminRole = Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        $customerRole = Role::create(['name' => 'Customer', 'description' => 'Customer']);

        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create customer user
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        // Create office and shipper
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();

        // Create manifest
        $this->manifest = Manifest::factory()->create();
    }

    /** @test */
    public function manifest_packages_page_uses_tabbed_interface()
    {
        $this->actingAs($this->admin);

        // Create some packages
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Create a consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);
        $response->assertSeeLivewire('manifests.enhanced-manifest-summary');
        $response->assertSeeLivewire('manifests.manifest-tabs-container');
    }

    /** @test */
    public function package_workflow_page_uses_tabbed_interface()
    {
        $this->actingAs($this->admin);

        // Create some packages
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.workflow', $this->manifest));

        $response->assertStatus(200);
        $response->assertSeeLivewire('manifests.enhanced-manifest-summary');
        $response->assertSeeLivewire('manifests.manifest-tabs-container');
    }

    /** @test */
    public function manifest_tabs_container_displays_correct_tabs()
    {
        $this->actingAs($this->admin);

        // Create individual packages
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Create consolidated packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest])
            ->assertSee('Consolidated Packages')
            ->assertSee('Individual Packages')
            ->assertSee('tab-consolidated')
            ->assertSee('tab-individual');
    }

    /** @test */
    public function tabs_switch_correctly()
    {
        $this->actingAs($this->admin);

        // Create packages for both tabs
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest])
            ->assertSet('activeTab', 'individual')
            ->call('switchTab', 'consolidated')
            ->assertSet('activeTab', 'consolidated')
            ->call('switchTab', 'individual')
            ->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function enhanced_manifest_summary_displays_correctly()
    {
        $this->actingAs($this->admin);

        // Create packages with weight data
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'weight' => 10.5,
        ]);

        Livewire::test('manifests.enhanced-manifest-summary', ['manifest' => $this->manifest])
            ->assertSee('Manifest Summary')
            ->assertSee('Total Packages')
            ->assertSee('2'); // Should show 2 packages
    }

    /** @test */
    public function consolidated_packages_tab_shows_consolidated_packages()
    {
        $this->actingAs($this->admin);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'consolidated_tracking_number' => 'CONS123',
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest])
            ->assertSee('CONS123')
            ->assertSee($this->customer->full_name);
    }

    /** @test */
    public function individual_packages_tab_shows_individual_packages()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'PKG123',
            'description' => 'Test Package',
        ]);

        Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->assertSee('PKG123')
            ->assertSee('Test Package')
            ->assertSee($this->customer->full_name);
    }

    /** @test */
    public function tab_state_is_preserved_in_url()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest])
            ->call('switchTab', 'consolidated')
            ->assertEmitted('tabSwitched', 'consolidated');
    }

    /** @test */
    public function existing_functionality_preserved_in_manifest_packages_page()
    {
        $this->actingAs($this->admin);

        // Create packages
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.packages', $this->manifest));

        $response->assertStatus(200);
        // Verify the page still has the essential elements
        $response->assertSee('Manifest Packages');
        $response->assertSee('Add Package');
        $response->assertSee('Package Workflow');
    }

    /** @test */
    public function existing_functionality_preserved_in_workflow_page()
    {
        $this->actingAs($this->admin);

        // Create packages
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $response = $this->get(route('admin.manifests.workflow', $this->manifest));

        $response->assertStatus(200);
        // Verify the page still has the essential elements
        $response->assertSee('Package Workflow Management');
        $response->assertSee('Status Statistics');
        $response->assertSee('Search');
    }
}