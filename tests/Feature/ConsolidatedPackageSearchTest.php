<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Enums\PackageStatus;
use App\Services\PackageConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class ConsolidatedPackageSearchTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $admin;
    protected $consolidationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customer = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        $this->admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
        
        $this->consolidationService = app(PackageConsolidationService::class);
    }

    /** @test */
    public function it_can_search_individual_packages_by_tracking_number()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'TEST123456',
            'description' => 'Test package 1',
            'status' => PackageStatus::READY,
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'OTHER789',
            'description' => 'Other package',
            'status' => PackageStatus::READY,
        ]);

        $this->actingAs($this->customer);

        $component = Livewire::test('package')
            ->set('search', 'TEST123');
        
        $individualPackages = $component->get('individualPackages');
        $this->assertTrue($individualPackages->contains('tracking_number', 'TEST123456'));
        $this->assertFalse($individualPackages->contains('tracking_number', 'OTHER789'));
    }

    /** @test */
    public function it_can_search_individual_packages_by_description()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'PKG001',
            'description' => 'Electronics and gadgets',
            'status' => PackageStatus::READY,
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'PKG002',
            'description' => 'Clothing items',
            'status' => PackageStatus::READY,
        ]);

        $this->actingAs($this->customer);

        $component = Livewire::test('package')
            ->set('search', 'electronics');
        
        $individualPackages = $component->get('individualPackages');
        $this->assertTrue($individualPackages->contains('description', 'Electronics and gadgets'));
        $this->assertFalse($individualPackages->contains('description', 'Clothing items'));
    }

    /** @test */
    public function it_can_search_consolidated_packages_by_consolidated_tracking_number()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create packages for consolidation
        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'PKG001',
            'description' => 'Package 1',
            'status' => PackageStatus::READY,
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'PKG002',
            'description' => 'Package 2',
            'status' => PackageStatus::READY,
        ]);

        // Consolidate packages
        $result = $this->consolidationService->consolidatePackages(
            [$package1->id, $package2->id],
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        $this->actingAs($this->customer);

        Livewire::test('package')
            ->set('showConsolidatedView', true)
            ->set('search', substr($consolidatedPackage->consolidated_tracking_number, 0, 8))
            ->assertSee($consolidatedPackage->consolidated_tracking_number);
    }

    /** @test */
    public function it_can_search_consolidated_packages_by_individual_tracking_numbers()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create packages for consolidation
        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'SEARCH123',
            'description' => 'Package 1',
            'status' => PackageStatus::READY,
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'PKG002',
            'description' => 'Package 2',
            'status' => PackageStatus::READY,
        ]);

        // Consolidate packages
        $result = $this->consolidationService->consolidatePackages(
            [$package1->id, $package2->id],
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        $this->actingAs($this->customer);

        Livewire::test('package')
            ->set('showConsolidatedView', true)
            ->set('search', 'SEARCH123')
            ->assertSee($consolidatedPackage->consolidated_tracking_number)
            ->assertSee('SEARCH123');
    }

    /** @test */
    public function it_can_filter_packages_by_status()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $readyPackage = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'READY001',
            'status' => PackageStatus::READY,
        ]);

        $shippedPackage = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'SHIPPED001',
            'status' => PackageStatus::SHIPPED,
        ]);

        $this->actingAs($this->customer);

        Livewire::test('package')
            ->set('statusFilter', PackageStatus::READY->value)
            ->assertSee('READY001')
            ->assertDontSee('SHIPPED001');
    }

    /** @test */
    public function it_shows_search_match_indicators()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'MATCH123',
            'description' => 'Test description',
            'status' => PackageStatus::READY,
        ]);

        $this->actingAs($this->customer);

        $component = Livewire::test('package')
            ->set('search', 'MATCH123');

        // Check that search matches are populated
        $this->assertTrue($component->get('hasSearchMatches', $package->id, 'individual'));
        $matches = $component->call('getPackageSearchMatches', $package->id, 'individual');
        $this->assertNotEmpty($matches);
    }

    /** @test */
    public function it_highlights_search_terms_in_results()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'HIGHLIGHT123',
            'description' => 'Test description',
            'status' => PackageStatus::READY,
        ]);

        $this->actingAs($this->customer);

        Livewire::test('package')
            ->set('search', 'HIGHLIGHT')
            ->assertSeeHtml('<mark class="bg-yellow-200 text-yellow-900 px-1 rounded">HIGHLIGHT</mark>');
    }

    /** @test */
    public function it_can_clear_search_and_filters()
    {
        $this->actingAs($this->customer);

        Livewire::test('package')
            ->set('search', 'test search')
            ->set('statusFilter', PackageStatus::READY->value)
            ->call('clearSearch')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSet('showSearchResults', false);
    }

    /** @test */
    public function it_shows_search_results_summary()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create individual packages
        Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => PackageStatus::READY,
        ]);

        // Create consolidated packages
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => PackageStatus::READY,
        ]);

        $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $this->actingAs($this->customer);

        Livewire::test('package')
            ->set('search', 'test')
            ->assertSee('Individual Package')
            ->assertSee('Consolidated Group');
    }

    /** @test */
    public function admin_can_search_all_packages_across_customers()
    {
        $customer1 = User::factory()->create(['first_name' => 'Customer', 'last_name' => 'One']);
        $customer2 = User::factory()->create(['first_name' => 'Customer', 'last_name' => 'Two']);
        
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package1 = Package::factory()->create([
            'user_id' => $customer1->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'ADMIN001',
            'status' => PackageStatus::READY,
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $customer2->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'ADMIN002',
            'status' => PackageStatus::READY,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('admin.package-management')
            ->set('search', 'ADMIN')
            ->assertSee('ADMIN001')
            ->assertSee('ADMIN002')
            ->assertSee('Customer One')
            ->assertSee('Customer Two');
    }

    /** @test */
    public function admin_can_filter_packages_by_customer()
    {
        $customer1 = User::factory()->create(['first_name' => 'Customer', 'last_name' => 'One']);
        $customer2 = User::factory()->create(['first_name' => 'Customer', 'last_name' => 'Two']);
        
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package1 = Package::factory()->create([
            'user_id' => $customer1->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'FILTER001',
            'status' => PackageStatus::READY,
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $customer2->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'FILTER002',
            'status' => PackageStatus::READY,
        ]);

        $this->actingAs($this->admin);

        Livewire::test('admin.package-management')
            ->set('customerFilter', $customer1->id)
            ->assertSee('FILTER001')
            ->assertSee('Customer One')
            ->assertDontSee('FILTER002')
            ->assertDontSee('Customer Two');
    }

    /** @test */
    public function admin_can_filter_by_package_type()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create individual package
        $individualPackage = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'INDIVIDUAL001',
            'status' => PackageStatus::READY,
        ]);

        // Create consolidated packages
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => PackageStatus::READY,
        ]);

        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $this->actingAs($this->admin);

        // Test individual only filter
        Livewire::test('admin.package-management')
            ->set('typeFilter', 'individual')
            ->assertSee('INDIVIDUAL001')
            ->assertDontSee($result['consolidated_package']->consolidated_tracking_number);

        // Test consolidated only filter
        Livewire::test('admin.package-management')
            ->set('typeFilter', 'consolidated')
            ->assertSee($result['consolidated_package']->consolidated_tracking_number)
            ->assertDontSee('INDIVIDUAL001');
    }

    /** @test */
    public function search_is_case_insensitive()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'CaseSensitive123',
            'description' => 'Test Description',
            'status' => PackageStatus::READY,
        ]);

        $this->actingAs($this->customer);

        $component = Livewire::test('package')
            ->set('search', 'casesensitive');
        
        // Check that the search found the package
        $individualPackages = $component->get('individualPackages');
        $this->assertTrue($individualPackages->contains('tracking_number', 'CaseSensitive123'));

        $component2 = Livewire::test('package')
            ->set('search', 'TEST DESCRIPTION');
        
        // Check that the search found the package by description
        $individualPackages2 = $component2->get('individualPackages');
        $this->assertTrue($individualPackages2->contains('description', 'Test Description'));
    }

    /** @test */
    public function search_works_with_partial_matches()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'PARTIAL123456',
            'description' => 'Electronics and gadgets',
            'status' => PackageStatus::READY,
        ]);

        $this->actingAs($this->customer);

        // Test partial tracking number match
        $component = Livewire::test('package')
            ->set('search', 'PARTIAL');
        
        $individualPackages = $component->get('individualPackages');
        $this->assertTrue($individualPackages->contains('tracking_number', 'PARTIAL123456'));

        // Test partial description match
        $component2 = Livewire::test('package')
            ->set('search', 'electronics');
        
        $individualPackages2 = $component2->get('individualPackages');
        $this->assertTrue($individualPackages2->contains('description', 'Electronics and gadgets'));
    }
}