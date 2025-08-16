<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Http\Livewire\Manifests\ConsolidatedPackagesTab;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use App\Services\PackageConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class ConsolidatedPackagesTabFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manifest;
    protected $consolidatedPackages;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->manifest = Manifest::factory()->create();
        
        // Create multiple consolidated packages for testing
        $this->consolidatedPackages = ConsolidatedPackage::factory()->count(3)->create([
            'customer_id' => $this->user->id,
        ]);
        
        // Create packages for each consolidated package
        foreach ($this->consolidatedPackages as $consolidatedPackage) {
            Package::factory()->count(2)->create([
                'manifest_id' => $this->manifest->id,
                'consolidated_package_id' => $consolidatedPackage->id,
                'user_id' => $this->user->id,
                'status' => PackageStatus::PROCESSING,
            ]);
        }
        
        Auth::login($this->user);
    }

    /** @test */
    public function it_displays_consolidated_packages_tab_correctly()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        // Should display all consolidated packages
        foreach ($this->consolidatedPackages as $consolidatedPackage) {
            $component->assertSee($consolidatedPackage->consolidated_tracking_number);
        }
        
        // Should display search and filter controls
        $component->assertSee('Search');
        $component->assertSee('Filter by Status');
        $component->assertSee('Sort By');
        $component->assertSee('Clear Filters');
    }

    /** @test */
    public function it_can_search_consolidated_packages_by_tracking_number()
    {
        $searchPackage = $this->consolidatedPackages->first();
        
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('search', $searchPackage->consolidated_tracking_number);
        
        $component->assertSee($searchPackage->consolidated_tracking_number);
        
        // Should not see other packages
        foreach ($this->consolidatedPackages->skip(1) as $otherPackage) {
            $component->assertDontSee($otherPackage->consolidated_tracking_number);
        }
    }

    /** @test */
    public function it_can_search_consolidated_packages_by_customer_name()
    {
        $customer = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $consolidatedPackage = ConsolidatedPackage::factory()->create(['customer_id' => $customer->id]);
        
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'user_id' => $customer->id,
        ]);
        
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('search', 'John');
        
        $component->assertSee($consolidatedPackage->consolidated_tracking_number);
    }

    /** @test */
    public function it_can_filter_consolidated_packages_by_status()
    {
        // Update one package to a different status
        $shippedPackage = $this->consolidatedPackages->first();
        $shippedPackage->update(['status' => PackageStatus::SHIPPED]);
        
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('statusFilter', PackageStatus::SHIPPED);
        
        $component->assertSee($shippedPackage->consolidated_tracking_number);
        
        // Should not see processing packages
        foreach ($this->consolidatedPackages->skip(1) as $processingPackage) {
            $component->assertDontSee($processingPackage->consolidated_tracking_number);
        }
    }

    /** @test */
    public function it_can_sort_consolidated_packages()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        // Test sorting by status
        $component->call('sortBy', 'status');
        $component->assertSet('sortBy', 'status');
        $component->assertSet('sortDirection', 'asc');
        
        // Test toggling sort direction
        $component->call('sortBy', 'status');
        $component->assertSet('sortDirection', 'desc');
        
        // Test sorting by different field
        $component->call('sortBy', 'total_weight');
        $component->assertSet('sortBy', 'total_weight');
        $component->assertSet('sortDirection', 'asc');
    }

    /** @test */
    public function it_can_select_and_deselect_all_consolidated_packages()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        // Select all
        $component->set('selectAll', true);
        
        $expectedIds = $this->consolidatedPackages->pluck('id')->sort()->values()->toArray();
        $actualIds = collect($component->get('selectedConsolidatedPackages'))->sort()->values()->toArray();
        $this->assertEquals($expectedIds, $actualIds);
        
        // Deselect all
        $component->set('selectAll', false);
        $component->assertSet('selectedConsolidatedPackages', []);
    }

    /** @test */
    public function it_shows_bulk_actions_when_packages_are_selected()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackages->first()->id]);
        
        $component->assertSee('1 consolidated package(s) selected');
        $component->assertSee('Select status...');
        $component->assertSee('Update Status');
        $component->assertSee('Clear Selection');
    }

    /** @test */
    public function it_can_perform_bulk_status_update()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('updateConsolidatedStatus')
                ->times(2)
                ->andReturn(['success' => true]);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $selectedIds = $this->consolidatedPackages->take(2)->pluck('id')->toArray();
        $component->set('selectedConsolidatedPackages', $selectedIds);
        $component->set('bulkStatus', PackageStatus::SHIPPED);
        
        // Confirm bulk update
        $component->call('confirmBulkStatusUpdate');
        $component->assertSet('showBulkConfirmModal', true);
        
        // Execute bulk update
        $component->call('executeBulkStatusUpdate');
        
        $component->assertSet('showBulkConfirmModal', false);
        $component->assertSet('selectedConsolidatedPackages', []);
        $component->assertEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_validates_bulk_status_update_requirements()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        // Try to update without selecting packages
        $component->call('confirmBulkStatusUpdate');
        $component->assertHasErrors(['bulkStatus', 'selectedConsolidatedPackages']);
        
        // Try to update without selecting status
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackages->first()->id]);
        $component->call('confirmBulkStatusUpdate');
        $component->assertHasErrors(['bulkStatus']);
    }

    /** @test */
    public function it_can_update_individual_consolidated_package_status()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('updateConsolidatedStatus')
                ->once()
                ->andReturn(['success' => true]);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $consolidatedPackage = $this->consolidatedPackages->first();
        
        $component->call('updateConsolidatedPackageStatus', $consolidatedPackage->id, PackageStatus::SHIPPED);
        
        $component->assertEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_shows_fee_modal_when_updating_to_ready_status()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $consolidatedPackage = $this->consolidatedPackages->first();
        
        $component->call('updateConsolidatedPackageStatus', $consolidatedPackage->id, PackageStatus::READY);
        
        $component->assertSet('showConsolidatedFeeModal', true);
        $component->assertSet('feeConsolidatedPackageId', $consolidatedPackage->id);
    }

    /** @test */
    public function it_can_process_consolidated_fee_updates()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('updateConsolidatedStatus')
                ->once()
                ->andReturn(['success' => true]);
        });

        $consolidatedPackage = $this->consolidatedPackages->first();
        $packages = $consolidatedPackage->packages;
        
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('feeConsolidatedPackageId', $consolidatedPackage->id);
        $component->set('consolidatedPackagesNeedingFees', [
            [
                'id' => $packages->first()->id,
                'tracking_number' => $packages->first()->tracking_number,
                'description' => $packages->first()->description,
                'customs_duty' => 10.00,
                'storage_fee' => 5.00,
                'delivery_fee' => 15.00,
                'needs_fees' => true,
            ]
        ]);
        
        $component->call('processConsolidatedFeeUpdate');
        
        $component->assertSet('showConsolidatedFeeModal', false);
        $component->assertEmitted('packageStatusUpdated');
        
        // Verify package fees were updated
        $packages->first()->refresh();
        $this->assertEquals(10.00, $packages->first()->customs_duty);
        $this->assertEquals(5.00, $packages->first()->storage_fee);
        $this->assertEquals(15.00, $packages->first()->delivery_fee);
    }

    /** @test */
    public function it_can_show_and_process_unconsolidation()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('unconsolidatePackages')
                ->once()
                ->andReturn(['success' => true]);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $consolidatedPackage = $this->consolidatedPackages->first();
        
        // Show unconsolidation modal
        $component->call('showUnconsolidationModal', $consolidatedPackage->id);
        $component->assertSet('showUnconsolidationModal', true);
        $component->assertSet('unconsolidatingPackageId', $consolidatedPackage->id);
        
        // Process unconsolidation
        $component->set('unconsolidationNotes', 'Test unconsolidation reason');
        $component->call('confirmUnconsolidation');
        
        $component->assertSet('showUnconsolidationModal', false);
        $component->assertEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_can_toggle_package_details()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $consolidatedPackage = $this->consolidatedPackages->first();
        
        $component->call('togglePackageDetails', $consolidatedPackage->id);
        
        $component->assertDispatchedBrowserEvent('toggle-consolidated-details', [
            'packageId' => $consolidatedPackage->id
        ]);
    }

    /** @test */
    public function it_displays_package_information_correctly()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $consolidatedPackage = $this->consolidatedPackages->first();
        
        // Should display consolidated package information
        $component->assertSee($consolidatedPackage->consolidated_tracking_number);
        $component->assertSee($consolidatedPackage->customer->full_name);
        $component->assertSee(number_format($consolidatedPackage->total_weight, 2) . ' lbs');
        
        if ($consolidatedPackage->total_cost > 0) {
            $component->assertSee('$' . number_format($consolidatedPackage->total_cost, 2));
        }
        
        // Should display package count
        $component->assertSee($consolidatedPackage->total_quantity . ' packages');
    }

    /** @test */
    public function it_displays_empty_state_when_no_packages_found()
    {
        // Create a manifest with no consolidated packages
        $emptyManifest = Manifest::factory()->create();
        
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $emptyManifest]);
        
        $component->assertSee('No consolidated packages found');
        $component->assertSee('This manifest has no consolidated packages.');
    }

    /** @test */
    public function it_displays_empty_state_with_search_message()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('search', 'nonexistent');
        
        $component->assertSee('No consolidated packages found');
        $component->assertSee('Try adjusting your search or filter criteria.');
    }

    /** @test */
    public function it_preserves_state_across_tab_switches()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        // Set some state
        $component->set('search', 'test search');
        $component->set('statusFilter', PackageStatus::PROCESSING);
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackages->first()->id]);
        
        // Simulate tab switch away
        $component->call('handlePreserveState', 'individual');
        
        // Verify state is preserved in session
        $this->assertNotNull(session('consolidated_tab_state'));
        $this->assertEquals('test search', session('consolidated_tab_state.search'));
        $this->assertEquals(PackageStatus::PROCESSING, session('consolidated_tab_state.statusFilter'));
        
        // Simulate tab switch back
        $component->call('handleTabSwitch', 'consolidated');
        
        // Verify state is restored
        $component->assertSet('search', 'test search');
        $component->assertSet('statusFilter', PackageStatus::PROCESSING);
        $component->assertSet('selectedConsolidatedPackages', [$this->consolidatedPackages->first()->id]);
    }

    /** @test */
    public function it_handles_pagination_correctly()
    {
        // Create many consolidated packages to test pagination
        $manyPackages = ConsolidatedPackage::factory()->count(15)->create([
            'customer_id' => $this->user->id,
        ]);
        
        foreach ($manyPackages as $consolidatedPackage) {
            Package::factory()->create([
                'manifest_id' => $this->manifest->id,
                'consolidated_package_id' => $consolidatedPackage->id,
                'user_id' => $this->user->id,
            ]);
        }
        
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        // Should show pagination links
        $component->assertSee('Next');
    }

    /** @test */
    public function it_clears_selection_when_filters_change()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackages->first()->id]);
        
        // Change search - should clear selection
        $component->set('search', 'new search');
        $component->assertSet('selectedConsolidatedPackages', []);
        
        // Set selection again
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackages->first()->id]);
        
        // Change status filter - should clear selection
        $component->set('statusFilter', PackageStatus::SHIPPED);
        $component->assertSet('selectedConsolidatedPackages', []);
    }

    /** @test */
    public function it_handles_service_errors_gracefully()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('updateConsolidatedStatus')
                ->once()
                ->andReturn(['success' => false, 'message' => 'Service error occurred']);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $consolidatedPackage = $this->consolidatedPackages->first();
        
        $component->call('updateConsolidatedPackageStatus', $consolidatedPackage->id, PackageStatus::SHIPPED);
        
        // Should not emit success event when service fails
        $component->assertNotEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_displays_status_badges_correctly()
    {
        // Create packages with different statuses
        $statuses = [
            PackageStatus::PENDING(),
            PackageStatus::PROCESSING(),
            PackageStatus::SHIPPED(),
            PackageStatus::READY(),
        ];
        
        foreach ($statuses as $status) {
            $consolidatedPackage = ConsolidatedPackage::factory()->create([
                'customer_id' => $this->user->id,
                'status' => $status->value,
            ]);
            
            Package::factory()->create([
                'manifest_id' => $this->manifest->id,
                'consolidated_package_id' => $consolidatedPackage->id,
                'user_id' => $this->user->id,
                'status' => $status->value,
            ]);
        }
        
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        // Should display status labels for each status
        foreach ($statuses as $status) {
            $component->assertSee($status->getLabel());
        }
    }
}