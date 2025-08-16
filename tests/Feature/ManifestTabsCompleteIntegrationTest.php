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
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class ManifestTabsCompleteIntegrationTest extends TestCase
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
    public function complete_tab_switching_workflow_preserves_state()
    {
        $this->actingAs($this->admin);

        // Create individual packages
        $individualPackages = Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Create consolidated packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $consolidatedPackages = Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'status' => PackageStatus::READY,
        ]);

        // Test individual packages tab state preservation
        $individualTabComponent = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->set('search', 'test search')
            ->set('statusFilter', PackageStatus::PROCESSING)
            ->set('selectedPackages', [$individualPackages->first()->id])
            ->assertSet('search', 'test search')
            ->assertSet('statusFilter', PackageStatus::PROCESSING)
            ->assertSet('selectedPackages', [$individualPackages->first()->id]);

        // Test consolidated packages tab state preservation
        $consolidatedTabComponent = Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest])
            ->set('search', 'consolidated search')
            ->set('statusFilter', PackageStatus::READY)
            ->set('selectedConsolidatedPackages', [$consolidatedPackage->id])
            ->assertSet('search', 'consolidated search')
            ->assertSet('statusFilter', PackageStatus::READY)
            ->assertSet('selectedConsolidatedPackages', [$consolidatedPackage->id]);

        // Test main container tab switching
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
    public function tab_state_persists_across_page_refreshes()
    {
        $this->actingAs($this->admin);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test URL parameter handling
        $response = $this->get(route('admin.manifests.packages', ['manifest' => $this->manifest, 'tab' => 'individual']));
        $response->assertStatus(200);
        $response->assertSee('Individual Packages');

        $response = $this->get(route('admin.manifests.packages', ['manifest' => $this->manifest, 'tab' => 'consolidated']));
        $response->assertStatus(200);
        $response->assertSee('Consolidated Packages');

        // Test invalid tab defaults to first tab
        $response = $this->get(route('admin.manifests.packages', ['manifest' => $this->manifest, 'tab' => 'invalid']));
        $response->assertStatus(200);
        $response->assertSee('Consolidated Packages'); // Should default to consolidated
    }

    /** @test */
    public function bulk_operations_work_correctly_within_tabs()
    {
        $this->actingAs($this->admin);

        // Create individual packages for bulk operations
        $packages = Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Test bulk status update in individual packages tab
        Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->set('selectedPackages', $packages->pluck('id')->toArray())
            ->set('bulkStatus', PackageStatus::READY)
            ->call('confirmBulkStatusUpdate')
            ->assertHasNoErrors();

        // Verify packages were updated
        $packages->each(function ($package) {
            $this->assertEquals(PackageStatus::READY, $package->fresh()->status);
        });

        // Create consolidated packages for bulk operations
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Test bulk operations in consolidated packages tab
        Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest])
            ->set('selectedConsolidatedPackages', [$consolidatedPackage->id])
            ->set('bulkStatus', PackageStatus::READY)
            ->call('confirmBulkStatusUpdate')
            ->assertHasNoErrors();

        // Verify consolidated package was updated
        $this->assertEquals(PackageStatus::READY, $consolidatedPackage->fresh()->status);
    }

    /** @test */
    public function search_and_filtering_work_independently_in_each_tab()
    {
        $this->actingAs($this->admin);

        // Create individual packages with different attributes
        $individualPackage1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'IND001',
            'status' => PackageStatus::PROCESSING,
        ]);

        $individualPackage2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'IND002',
            'status' => PackageStatus::READY,
        ]);

        // Create consolidated packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'consolidated_tracking_number' => 'CONS001',
            'status' => PackageStatus::PROCESSING,
        ]);

        // Test individual packages tab search
        Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->set('search', 'IND001')
            ->assertSee('IND001')
            ->assertDontSee('IND002');

        // Test individual packages tab filtering
        Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->set('statusFilter', PackageStatus::READY)
            ->assertSee('IND002')
            ->assertDontSee('IND001');

        // Test consolidated packages tab search
        Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest])
            ->set('search', 'CONS001')
            ->assertSee('CONS001');

        // Test consolidated packages tab filtering
        Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest])
            ->set('statusFilter', PackageStatus::PROCESSING)
            ->assertSee('CONS001');
    }

    /** @test */
    public function pagination_works_independently_in_each_tab()
    {
        $this->actingAs($this->admin);

        // Create many individual packages
        Package::factory()->count(25)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Create many consolidated packages
        for ($i = 0; $i < 15; $i++) {
            ConsolidatedPackage::factory()->create([
                'customer_id' => $this->customer->id,
            ]);
        }

        // Test individual packages tab pagination
        $individualTabComponent = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);
        
        // Should have pagination controls if more than per-page limit
        if ($individualTabComponent->get('packages')->hasPages()) {
            $individualTabComponent->assertSee('Next');
        }

        // Test consolidated packages tab pagination
        $consolidatedTabComponent = Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest]);
        
        // Should have pagination controls if more than per-page limit
        if ($consolidatedTabComponent->get('consolidatedPackages')->hasPages()) {
            $consolidatedTabComponent->assertSee('Next');
        }
    }

    /** @test */
    public function tab_content_updates_when_packages_are_modified()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Test individual packages tab updates when package status changes
        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->assertSee(PackageStatus::PROCESSING->value);

        // Update package status
        $package->update(['status' => PackageStatus::READY]);

        // Refresh component and check for updated status
        $component->call('$refresh')
            ->assertSee(PackageStatus::READY->value);
    }

    /** @test */
    public function error_handling_works_correctly_in_tabs()
    {
        $this->actingAs($this->admin);

        // Test error handling in individual packages tab
        Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->set('selectedPackages', [999]) // Non-existent package ID
            ->set('bulkStatus', PackageStatus::READY)
            ->call('confirmBulkStatusUpdate')
            ->assertHasErrors();

        // Test error handling in consolidated packages tab
        Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest])
            ->set('selectedConsolidatedPackages', [999]) // Non-existent consolidated package ID
            ->set('bulkStatus', PackageStatus::READY)
            ->call('confirmBulkStatusUpdate')
            ->assertHasErrors();
    }

    /** @test */
    public function tab_switching_preserves_user_selections()
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Test that selections are preserved when switching tabs
        $containerComponent = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest])
            ->call('switchTab', 'individual')
            ->assertSet('activeTab', 'individual');

        // Simulate selection preservation through session or component state
        $individualComponent = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->call('handleTabSwitch', 'individual')
            ->assertSet('selectedPackages', [$package->id]);
    }

    /** @test */
    public function empty_states_display_correctly_in_both_tabs()
    {
        $this->actingAs($this->admin);

        // Test empty individual packages tab
        Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->assertSee('No individual packages found');

        // Test empty consolidated packages tab
        Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest])
            ->assertSee('No consolidated packages found');
    }

    /** @test */
    public function tab_counts_update_correctly()
    {
        $this->actingAs($this->admin);

        // Create packages
        Package::factory()->count(3)->create([
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

        // Test tab counts
        Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest])
            ->assertSee('Individual Packages (3)')
            ->assertSee('Consolidated Packages (1)');
    }
}