<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Livewire\Manifests\IndividualPackagesTab;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Office;
use App\Models\Shipper;
use App\Models\ConsolidatedPackage;
use App\Enums\PackageStatus;
use App\Services\PackageStatusService;
use App\Services\PackageConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Illuminate\Support\Facades\Auth;

class IndividualPackagesTabFeatureTest extends TestCase
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
    public function it_renders_individual_packages_tab_successfully()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest]);

        $component->assertStatus(200)
                  ->assertSee('Individual Packages')
                  ->assertSee('Search')
                  ->assertSee('Filter by Status')
                  ->assertSee('Sort By')
                  ->assertSee('Clear Filters');
    }

    /** @test */
    public function it_displays_individual_packages_with_correct_information()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'TEST123456',
            'description' => 'Test Package Description',
            'weight' => 5.5,
            'freight_price' => 25.00,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 15.00,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest]);

        $component->assertSee('TEST123456')
                  ->assertSee('Test Package Description')
                  ->assertSee('5.50 lbs')
                  ->assertSee('$25.00')
                  ->assertSee('$10.00')
                  ->assertSee('$5.00')
                  ->assertSee('$15.00')
                  ->assertSee($this->user->full_name);
    }

    /** @test */
    public function it_filters_out_consolidated_packages()
    {
        // Create individual package
        $individualPackage = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'INDIVIDUAL123',
            'consolidated_package_id' => null
        ]);

        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);

        $packageInConsolidation = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'CONSOLIDATED123',
            'consolidated_package_id' => $consolidatedPackage->id
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest]);

        $component->assertSee('INDIVIDUAL123')
                  ->assertDontSee('CONSOLIDATED123');
    }

    /** @test */
    public function it_can_search_packages_by_tracking_number()
    {
        $package1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'SEARCH123',
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
            ->set('search', 'SEARCH123');

        $component->assertSee('SEARCH123')
                  ->assertDontSee('DIFFERENT456');
    }

    /** @test */
    public function it_can_search_packages_by_customer_name()
    {
        $customer1 = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
        Profile::factory()->create(['user_id' => $customer1->id]);

        $customer2 = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ]);
        Profile::factory()->create(['user_id' => $customer2->id]);

        $package1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $customer1->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'PKG001',
            'consolidated_package_id' => null
        ]);

        $package2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $customer2->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'PKG002',
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('search', 'John');

        $component->assertSee('PKG001')
                  ->assertSee('John Doe')
                  ->assertDontSee('PKG002')
                  ->assertDontSee('Jane Smith');
    }

    /** @test */
    public function it_can_filter_packages_by_status()
    {
        $readyPackage = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'READY123',
            'status' => PackageStatus::READY,
            'consolidated_package_id' => null
        ]);

        $processingPackage = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'PROCESSING456',
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('statusFilter', 'ready');

        $component->assertSee('READY123')
                  ->assertDontSee('PROCESSING456');
    }

    /** @test */
    public function it_can_sort_packages_by_different_fields()
    {
        $package1 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'AAA123',
            'weight' => 10.0,
            'consolidated_package_id' => null
        ]);

        $package2 = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'ZZZ456',
            'weight' => 5.0,
            'consolidated_package_id' => null
        ]);

        // Test sorting by tracking number ascending
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->call('sortBy', 'tracking_number');

        $component->assertViewHas('packages');
        $packages = $component->viewData('packages');
        $this->assertEquals('AAA123', $packages->first()->tracking_number);

        // Test sorting by weight descending (first click sets to asc, second click sets to desc)
        $component->call('sortBy', 'weight');
        $component->call('sortBy', 'weight'); // Second click to get descending
        $component->assertViewHas('packages');
        $packages = $component->viewData('packages');
        $this->assertEquals('10.00', $packages->first()->weight);
    }

    /** @test */
    public function it_can_select_and_deselect_all_packages()
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

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest]);

        // Select all
        $component->set('selectAll', true);
        $selectedPackages = $component->get('selectedPackages');
        $this->assertContains($package1->id, $selectedPackages);
        $this->assertContains($package2->id, $selectedPackages);

        // Deselect all
        $component->set('selectAll', false);
        $this->assertEmpty($component->get('selectedPackages'));
    }

    /** @test */
    public function it_shows_bulk_actions_when_packages_are_selected()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id]);

        $component->assertSee('1 package(s) selected')
                  ->assertSee('Update Status')
                  ->assertSee('Clear Selection');
    }

    /** @test */
    public function it_shows_consolidation_option_when_multiple_packages_selected()
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

        $component->assertSee('Consolidate');
    }

    /** @test */
    public function it_can_clear_filters_and_reset_state()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('search', 'test search')
            ->set('statusFilter', 'ready')
            ->set('sortBy', 'tracking_number')
            ->set('sortDirection', 'asc')
            ->set('selectedPackages', [1, 2, 3])
            ->call('clearFilters');

        $component->assertSet('search', '')
                  ->assertSet('statusFilter', '')
                  ->assertSet('sortBy', 'created_at')
                  ->assertSet('sortDirection', 'desc')
                  ->assertSet('selectedPackages', []);
    }

    /** @test */
    public function it_validates_bulk_status_update_requirements()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        // Test without selecting status
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->call('confirmBulkStatusUpdate');

        $component->assertHasErrors(['bulkStatus']);

        // Test without selecting packages
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('bulkStatus', 'ready')
            ->call('confirmBulkStatusUpdate');

        $component->assertHasErrors(['selectedPackages']);
    }

    /** @test */
    public function it_shows_bulk_confirmation_modal_with_correct_information()
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
            ->set('selectedPackages', [$package1->id, $package2->id])
            ->set('bulkStatus', 'ready')
            ->call('confirmBulkStatusUpdate');

        $component->assertSet('showBulkConfirmModal', true)
                  ->assertSee('Confirm Bulk Status Update')
                  ->assertSee('2 package(s)')
                  ->assertSee('Ready for Pickup');
    }

    /** @test */
    public function it_can_execute_bulk_status_update()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => null
        ]);

        // Mock the package status service
        $this->mock(PackageStatusService::class, function ($mock) {
            $mock->shouldReceive('updateStatus')
                 ->once()
                 ->andReturn(true);
        });

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->set('confirmingStatus', 'ready')
            ->call('executeBulkStatusUpdate');

        $component->assertSet('showBulkConfirmModal', false)
                  ->assertSet('selectedPackages', [])
                  ->assertDispatchedBrowserEvent('toastr:success');
    }

    /** @test */
    public function it_can_show_fee_entry_modal()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 15.00,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->call('showFeeEntryModal', $package->id);

        $component->assertSet('showFeeModal', true)
                  ->assertSee('Update Fees for Package')
                  ->assertSee($package->tracking_number)
                  ->assertSee('Customs Duty')
                  ->assertSee('Storage Fee')
                  ->assertSee('Delivery Fee');
    }

    /** @test */
    public function it_can_update_package_fees()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'customs_duty' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
            'status' => PackageStatus::CUSTOMS, // Status that can transition to READY
            'consolidated_package_id' => null
        ]);

        // Let the actual service run for this test

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('feePackageId', $package->id)
            ->set('customsDuty', 20.00)
            ->set('storageFee', 10.00)
            ->set('deliveryFee', 25.00)
            ->call('processFeeUpdate');

        $package->refresh();
        $this->assertEquals(20.00, $package->customs_duty);
        $this->assertEquals(10.00, $package->storage_fee);
        $this->assertEquals(25.00, $package->delivery_fee);

        $component->assertSet('showFeeModal', false)
                  ->assertDispatchedBrowserEvent('toastr:success');
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

        // Test with only one package
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [$package->id])
            ->call('showConsolidationModal');

        $component->assertDispatchedBrowserEvent('toastr:warning');

        // Test with no packages
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('selectedPackages', [])
            ->call('showConsolidationModal');

        $component->assertDispatchedBrowserEvent('toastr:warning');
    }

    /** @test */
    public function it_can_show_consolidation_modal_with_valid_packages()
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

        $component->assertSet('showConsolidationModal', true)
                  ->assertSee('Consolidate Selected Packages')
                  ->assertSee($package1->tracking_number)
                  ->assertSee($package2->tracking_number);
    }

    /** @test */
    public function it_preserves_tab_state_correctly()
    {
        $package = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('search', 'test search')
            ->set('statusFilter', 'ready')
            ->set('sortBy', 'tracking_number')
            ->set('sortDirection', 'asc')
            ->set('selectedPackages', [$package->id])
            ->call('handlePreserveState', 'consolidated');

        // Verify state was saved to session
        $state = session()->get('individual_tab_state');
        $this->assertEquals('test search', $state['search']);
        $this->assertEquals('ready', $state['statusFilter']);
        $this->assertEquals('tracking_number', $state['sortBy']);
        $this->assertEquals('asc', $state['sortDirection']);
        $this->assertEquals([$package->id], $state['selectedPackages']);
    }

    /** @test */
    public function it_restores_tab_state_correctly()
    {
        // Set up session state
        session()->put('individual_tab_state', [
            'search' => 'restored search',
            'statusFilter' => 'processing',
            'sortBy' => 'weight',
            'sortDirection' => 'desc',
            'selectedPackages' => [1, 2, 3],
            'page' => 2
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->call('handleTabSwitch', 'individual');

        $component->assertSet('search', 'restored search')
                  ->assertSet('statusFilter', 'processing')
                  ->assertSet('sortBy', 'weight')
                  ->assertSet('sortDirection', 'desc')
                  ->assertSet('selectedPackages', [1, 2, 3]);
    }

    /** @test */
    public function it_displays_no_packages_message_when_empty()
    {
        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest]);

        $component->assertSee('No individual packages found')
                  ->assertSee('This manifest has no individual packages');
    }

    /** @test */
    public function it_displays_no_results_message_when_filtered()
    {
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'tracking_number' => 'TEST123',
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest])
            ->set('search', 'NONEXISTENT');

        $component->assertSee('No individual packages found')
                  ->assertSee('Try adjusting your search or filter criteria');
    }

    /** @test */
    public function it_shows_correct_package_count_in_header()
    {
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(IndividualPackagesTab::class, ['manifest' => $this->manifest]);

        $component->assertSee('Individual Packages (3)');
    }
}