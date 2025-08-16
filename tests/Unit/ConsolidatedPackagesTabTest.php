<?php

namespace Tests\Unit;

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

class ConsolidatedPackagesTabTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manifest;
    protected $consolidatedPackage;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->manifest = Manifest::factory()->create();
        $this->consolidatedPackage = ConsolidatedPackage::factory()->create([
            'status' => PackageStatus::PROCESSING,
            'customer_id' => $this->user->id,
        ]);
        
        // Create packages for the consolidated package
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'user_id' => $this->user->id,
            'status' => PackageStatus::PROCESSING,
        ]);
        
        Auth::login($this->user);
    }

    /** @test */
    public function it_can_mount_with_manifest()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->assertSet('manifest.id', $this->manifest->id);
    }

    /** @test */
    public function it_displays_consolidated_packages_for_manifest()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->assertSee($this->consolidatedPackage->consolidated_tracking_number);
        $component->assertSee($this->user->full_name);
    }

    /** @test */
    public function it_can_search_consolidated_packages()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('search', $this->consolidatedPackage->consolidated_tracking_number);
        
        $component->assertSee($this->consolidatedPackage->consolidated_tracking_number);
    }

    /** @test */
    public function it_can_filter_by_status()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('statusFilter', PackageStatus::PROCESSING);
        
        $component->assertSee($this->consolidatedPackage->consolidated_tracking_number);
    }

    /** @test */
    public function it_can_sort_consolidated_packages()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('sortBy', 'status');
        
        $component->assertSet('sortBy', 'status');
        $component->assertSet('sortDirection', 'asc');
        
        // Sort again to test direction toggle
        $component->call('sortBy', 'status');
        $component->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function it_can_select_all_consolidated_packages()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('selectAll', true);
        
        $component->assertSet('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
    }

    /** @test */
    public function it_can_clear_filters()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('search', 'test');
        $component->set('statusFilter', PackageStatus::PROCESSING);
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
        
        $component->call('clearFilters');
        
        $component->assertSet('search', '');
        $component->assertSet('statusFilter', '');
        $component->assertSet('selectedConsolidatedPackages', []);
    }

    /** @test */
    public function it_validates_bulk_status_update()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('confirmBulkStatusUpdate');
        
        $component->assertHasErrors(['bulkStatus', 'selectedConsolidatedPackages']);
    }

    /** @test */
    public function it_can_confirm_bulk_status_update()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
        $component->set('bulkStatus', PackageStatus::SHIPPED);
        
        $component->call('confirmBulkStatusUpdate');
        
        $component->assertSet('showBulkConfirmModal', true);
        $component->assertSet('confirmingStatus', PackageStatus::SHIPPED);
        $component->assertSet('confirmingStatusLabel', PackageStatus::SHIPPED()->getLabel());
    }

    /** @test */
    public function it_can_cancel_bulk_update()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('showBulkConfirmModal', true);
        $component->set('confirmingStatus', PackageStatus::SHIPPED);
        $component->set('bulkStatus', PackageStatus::SHIPPED);
        
        $component->call('cancelBulkUpdate');
        
        $component->assertSet('showBulkConfirmModal', false);
        $component->assertSet('confirmingStatus', '');
        $component->assertSet('bulkStatus', '');
    }

    /** @test */
    public function it_can_execute_bulk_status_update()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('updateConsolidatedStatus')
                ->once()
                ->andReturn(['success' => true]);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
        $component->set('confirmingStatus', PackageStatus::SHIPPED);
        
        $component->call('executeBulkStatusUpdate');
        
        $component->assertSet('showBulkConfirmModal', false);
        $component->assertSet('selectedConsolidatedPackages', []);
        $component->assertEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_can_update_single_consolidated_package_status()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('updateConsolidatedStatus')
                ->once()
                ->andReturn(['success' => true]);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('updateConsolidatedPackageStatus', $this->consolidatedPackage->id, PackageStatus::SHIPPED);
        
        $component->assertEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_shows_fee_modal_when_transitioning_to_ready()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('updateConsolidatedPackageStatus', $this->consolidatedPackage->id, PackageStatus::READY);
        
        $component->assertSet('showConsolidatedFeeModal', true);
        $component->assertSet('feeConsolidatedPackageId', $this->consolidatedPackage->id);
    }

    /** @test */
    public function it_can_show_consolidated_fee_entry_modal()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('showConsolidatedFeeEntryModal', $this->consolidatedPackage->id);
        
        $component->assertSet('showConsolidatedFeeModal', true);
        $component->assertSet('feeConsolidatedPackageId', $this->consolidatedPackage->id);
        $this->assertNotEmpty($component->get('consolidatedPackagesNeedingFees'));
    }

    /** @test */
    public function it_can_close_consolidated_fee_modal()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('showConsolidatedFeeModal', true);
        $component->set('feeConsolidatedPackageId', $this->consolidatedPackage->id);
        
        $component->call('closeConsolidatedFeeModal');
        
        $component->assertSet('showConsolidatedFeeModal', false);
        $component->assertSet('feeConsolidatedPackageId', null);
        $component->assertSet('consolidatedPackagesNeedingFees', []);
    }

    /** @test */
    public function it_can_process_consolidated_fee_update()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('updateConsolidatedStatus')
                ->once()
                ->andReturn(['success' => true]);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('feeConsolidatedPackageId', $this->consolidatedPackage->id);
        $component->set('consolidatedPackagesNeedingFees', [
            [
                'id' => $this->consolidatedPackage->packages->first()->id,
                'customs_duty' => 10.00,
                'storage_fee' => 5.00,
                'delivery_fee' => 15.00,
            ]
        ]);
        
        $component->call('processConsolidatedFeeUpdate');
        
        $component->assertSet('showConsolidatedFeeModal', false);
        $component->assertEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_can_show_unconsolidation_modal()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('showUnconsolidationModal', $this->consolidatedPackage->id);
        
        $component->assertSet('showUnconsolidationModal', true);
        $component->assertSet('unconsolidatingPackageId', $this->consolidatedPackage->id);
    }

    /** @test */
    public function it_can_cancel_unconsolidation()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('showUnconsolidationModal', true);
        $component->set('unconsolidatingPackageId', $this->consolidatedPackage->id);
        
        $component->call('cancelUnconsolidation');
        
        $component->assertSet('showUnconsolidationModal', false);
        $component->assertSet('unconsolidatingPackageId', null);
    }

    /** @test */
    public function it_can_confirm_unconsolidation()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('unconsolidatePackages')
                ->once()
                ->andReturn(['success' => true]);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('unconsolidatingPackageId', $this->consolidatedPackage->id);
        $component->set('unconsolidationNotes', 'Test reason');
        
        $component->call('confirmUnconsolidation');
        
        $component->assertSet('showUnconsolidationModal', false);
        $component->assertEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_can_toggle_package_details()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('togglePackageDetails', $this->consolidatedPackage->id);
        
        $component->assertDispatchedBrowserEvent('toggle-consolidated-details', [
            'packageId' => $this->consolidatedPackage->id
        ]);
    }

    /** @test */
    public function it_preserves_state_when_tab_switches()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('search', 'test search');
        $component->set('statusFilter', PackageStatus::PROCESSING);
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
        
        $component->call('handlePreserveState', 'individual');
        
        // Check that state is stored in session
        $this->assertNotNull(session('consolidated_tab_state'));
        $this->assertEquals('test search', session('consolidated_tab_state.search'));
    }

    /** @test */
    public function it_restores_state_when_tab_switches_back()
    {
        session()->put('consolidated_tab_state', [
            'search' => 'restored search',
            'statusFilter' => PackageStatus::SHIPPED,
            'selectedConsolidatedPackages' => [$this->consolidatedPackage->id],
            'sortBy' => 'status',
            'sortDirection' => 'desc',
        ]);

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('handleTabSwitch', 'consolidated');
        
        $component->assertSet('search', 'restored search');
        $component->assertSet('statusFilter', PackageStatus::SHIPPED);
        $component->assertSet('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
        $component->assertSet('sortBy', 'status');
        $component->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function it_resets_page_when_search_changes()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('search', 'new search');
        
        $component->assertSet('selectedConsolidatedPackages', []);
    }

    /** @test */
    public function it_resets_page_when_status_filter_changes()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('statusFilter', PackageStatus::SHIPPED);
        
        $component->assertSet('selectedConsolidatedPackages', []);
    }

    /** @test */
    public function it_updates_select_all_when_individual_selections_change()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
        
        // This should trigger the updatedSelectedConsolidatedPackages method
        $component->assertSet('selectAll', true);
    }

    /** @test */
    public function it_identifies_packages_needing_fee_entry()
    {
        $package = Package::factory()->create([
            'consolidated_package_id' => $this->consolidatedPackage->id,
            'customs_duty' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('showConsolidatedFeeEntryModal', $this->consolidatedPackage->id);
        
        $needingFees = $component->get('consolidatedPackagesNeedingFees');
        $packageNeedingFees = collect($needingFees)->firstWhere('id', $package->id);
        
        $this->assertTrue($packageNeedingFees['needs_fees']);
    }

    /** @test */
    public function it_handles_invalid_status_in_bulk_update()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
        $component->set('bulkStatus', 'invalid_status');
        
        $component->call('confirmBulkStatusUpdate');
        
        $component->assertHasErrors(['bulkStatus']);
    }

    /** @test */
    public function it_handles_service_errors_gracefully()
    {
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('updateConsolidatedStatus')
                ->once()
                ->andReturn(['success' => false, 'message' => 'Service error']);
        });

        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->call('updateConsolidatedPackageStatus', $this->consolidatedPackage->id, PackageStatus::SHIPPED);
        
        // Should not emit success event
        $component->assertNotEmitted('packageStatusUpdated');
    }

    /** @test */
    public function it_gets_selected_consolidated_packages_property()
    {
        $component = Livewire::test(ConsolidatedPackagesTab::class, ['manifest' => $this->manifest]);
        
        $component->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id]);
        
        $selectedPackages = $component->instance()->getSelectedConsolidatedPackagesProperty();
        
        $this->assertCount(1, $selectedPackages);
        $this->assertEquals($this->consolidatedPackage->id, $selectedPackages->first()->id);
    }
}