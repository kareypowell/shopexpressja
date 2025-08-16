<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Manifests\IndividualPackagesTab;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Office;
use App\Models\Shipper;
use App\Enums\PackageStatus;
use App\Services\PackageStatusService;
use App\Services\PackageConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Illuminate\Support\Facades\Auth;

class IndividualPackagesTabTest extends TestCase
{
    use RefreshDatabase;

    protected $manifest;
    protected $user;
    protected $adminUser;
    protected $office;
    protected $shipper;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'Admin']);
        $customerRole = Role::factory()->create(['name' => 'Customer']);

        // Create admin user
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create customer user with profile
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        Profile::factory()->create(['user_id' => $this->user->id]);

        // Create office and shipper
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();

        // Create manifest
        $this->manifest = Manifest::factory()->create();

        // Authenticate as admin
        Auth::login($this->adminUser);
    }

    /** @test */
    public function it_can_mount_with_manifest()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest]);

        $component->assertSet('manifest.id', $this->manifest->id);
    }

    /** @test */
    public function it_displays_individual_packages_only()
    {
        // Create individual packages
        $individualPackage1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $individualPackage2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        // Create consolidated package (should not appear)
        $consolidatedPackageRecord = \App\Models\ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);
        
        $consolidatedPackage = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackageRecord->id
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest]);

        $component->assertViewHas('packages');
        $packages = $component->viewData('packages');
        
        $this->assertEquals(2, $packages->count());
        $this->assertTrue($packages->contains('id', $individualPackage1->id));
        $this->assertTrue($packages->contains('id', $individualPackage2->id));
        $this->assertFalse($packages->contains('id', $consolidatedPackage->id));
    }

    /** @test */
    public function it_can_search_packages()
    {
        $package1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'TEST123',
            'consolidated_package_id' => null
        ]);

        $package2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'DIFFERENT456',
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('search', 'TEST123');

        $component->assertViewHas('packages');
        $packages = $component->viewData('packages');
        
        $this->assertEquals(1, $packages->count());
        $this->assertTrue($packages->contains('id', $package1->id));
        $this->assertFalse($packages->contains('id', $package2->id));
    }

    /** @test */
    public function it_can_filter_by_status()
    {
        $readyPackage = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::READY,
            'consolidated_package_id' => null
        ]);

        $processingPackage = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('statusFilter', 'ready');

        $component->assertViewHas('packages');
        $packages = $component->viewData('packages');
        
        $this->assertEquals(1, $packages->count());
        $this->assertTrue($packages->contains('id', $readyPackage->id));
        $this->assertFalse($packages->contains('id', $processingPackage->id));
    }

    /** @test */
    public function it_can_sort_packages()
    {
        $package1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'AAA123',
            'consolidated_package_id' => null
        ]);

        $package2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'ZZZ456',
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('sortBy', 'tracking_number')
            ->set('sortDirection', 'asc');

        $component->assertViewHas('packages');
        $packages = $component->viewData('packages');
        
        $this->assertEquals($package1->id, $packages->first()->id);
        $this->assertEquals($package2->id, $packages->last()->id);
    }

    /** @test */
    public function it_can_select_all_packages()
    {
        $package1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $package2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectAll', true);

        $this->assertContains($package1->id, $component->get('selectedPackages'));
        $this->assertContains($package2->id, $component->get('selectedPackages'));
    }

    /** @test */
    public function it_can_clear_filters()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('search', 'test')
            ->set('statusFilter', 'ready')
            ->set('sortBy', 'tracking_number')
            ->call('clearFilters');

        $component->assertSet('search', '')
                  ->assertSet('statusFilter', '')
                  ->assertSet('sortBy', 'created_at')
                  ->assertSet('sortDirection', 'desc')
                  ->assertSet('selectedPackages', []);
    }

    /** @test */
    public function it_can_clear_selection()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->set('selectAll', true)
            ->call('clearSelection');

        $component->assertSet('selectedPackages', [])
                  ->assertSet('selectAll', false);
    }

    /** @test */
    public function it_validates_bulk_status_update()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->call('confirmBulkStatusUpdate');

        $component->assertHasErrors(['bulkStatus']);
    }

    /** @test */
    public function it_can_show_bulk_confirmation_modal()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->set('bulkStatus', 'ready')
            ->call('confirmBulkStatusUpdate');

        $component->assertSet('showBulkConfirmModal', true)
                  ->assertSet('confirmingStatus', 'ready')
                  ->assertSet('confirmingStatusLabel', 'Ready for Pickup');
    }

    /** @test */
    public function it_can_cancel_bulk_update()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('showBulkConfirmModal', true)
            ->set('confirmingStatus', 'ready')
            ->set('bulkStatus', 'ready')
            ->call('cancelBulkUpdate');

        $component->assertSet('showBulkConfirmModal', false)
                  ->assertSet('confirmingStatus', '')
                  ->assertSet('confirmingStatusLabel', '')
                  ->assertSet('bulkStatus', '')
                  ->assertSet('selectedPackages', []);
    }

    /** @test */
    public function it_can_show_fee_entry_modal()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 15.00
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->call('showFeeEntryModal', $package->id);

        $component->assertSet('showFeeModal', true)
                  ->assertSet('feePackageId', $package->id)
                  ->assertSet('customsDuty', 10.00)
                  ->assertSet('storageFee', 5.00)
                  ->assertSet('deliveryFee', 15.00);
    }

    /** @test */
    public function it_can_close_fee_modal()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('showFeeModal', true)
            ->set('feePackageId', 1)
            ->set('customsDuty', 10.00)
            ->call('closeFeeModal');

        $component->assertSet('showFeeModal', false)
                  ->assertSet('feePackageId', null)
                  ->assertSet('customsDuty', 0)
                  ->assertSet('storageFee', 0)
                  ->assertSet('deliveryFee', 0);
    }

    /** @test */
    public function it_validates_consolidation_requirements()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        // Test with only one package selected
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->call('showConsolidationModal');

        // Should show warning about needing at least 2 packages
        $component->assertDispatchedBrowserEvent('toastr:warning');
    }

    /** @test */
    public function it_can_show_consolidation_modal_with_valid_selection()
    {
        $package1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $package2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        // Mock the consolidation service
        $this->mock(PackageConsolidationService::class, function ($mock) {
            $mock->shouldReceive('validateConsolidation')
                 ->once()
                 ->andReturn(['valid' => true]);
        });

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package1->id, $package2->id])
            ->call('showConsolidationModal');

        $component->assertSet('showConsolidationModal', true);
        $this->assertCount(2, $component->get('packagesForConsolidation'));
    }

    /** @test */
    public function it_can_cancel_consolidation()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('showConsolidationModal', true)
            ->set('consolidationNotes', 'test notes')
            ->call('cancelConsolidation');

        $component->assertSet('showConsolidationModal', false)
                  ->assertSet('consolidationNotes', '')
                  ->assertSet('packagesForConsolidation', []);
    }

    /** @test */
    public function it_preserves_state_when_tab_switches()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('search', 'test search')
            ->set('statusFilter', 'ready')
            ->set('sortBy', 'tracking_number')
            ->set('selectedPackages', [1, 2, 3])
            ->call('handlePreserveState', 'consolidated');

        // Check that state was stored in session
        $state = session()->get('individual_tab_state');
        $this->assertEquals('test search', $state['search']);
        $this->assertEquals('ready', $state['statusFilter']);
        $this->assertEquals('tracking_number', $state['sortBy']);
        $this->assertEquals([1, 2, 3], $state['selectedPackages']);
    }

    /** @test */
    public function it_restores_state_when_tab_switches_back()
    {
        // Set up session state
        session()->put('individual_tab_state', [
            'search' => 'restored search',
            'statusFilter' => 'processing',
            'sortBy' => 'weight',
            'sortDirection' => 'asc',
            'selectedPackages' => [4, 5, 6],
            'page' => 2
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->call('handleTabSwitch', 'individual');

        $component->assertSet('search', 'restored search')
                  ->assertSet('statusFilter', 'processing')
                  ->assertSet('sortBy', 'weight')
                  ->assertSet('sortDirection', 'asc')
                  ->assertSet('selectedPackages', [4, 5, 6]);
    }

    /** @test */
    public function it_resets_page_when_search_changes()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('page', 2)
            ->set('search', 'new search');

        // Page should be reset and selection cleared
        $component->assertSet('selectedPackages', []);
    }

    /** @test */
    public function it_resets_page_when_status_filter_changes()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('page', 2)
            ->set('statusFilter', 'ready');

        // Page should be reset and selection cleared
        $component->assertSet('selectedPackages', []);
    }

    /** @test */
    public function it_updates_select_all_based_on_selected_packages()
    {
        $package1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $package2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package1->id, $package2->id]);

        // Should set selectAll to true when all packages are selected
        $component->assertSet('selectAll', true);
    }
}