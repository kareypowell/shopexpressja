<?php

namespace Tests\Unit;

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

class PackageSearchTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $admin;

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
    }

    /** @test */
    public function package_search_scope_works_with_tracking_number()
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

        $results = Package::search('TEST123')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('TEST123456', $results->first()->tracking_number);
    }

    /** @test */
    public function package_search_scope_works_with_description()
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

        $results = Package::search('electronics')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('Electronics and gadgets', $results->first()->description);
    }

    /** @test */
    public function package_search_with_consolidated_scope_works()
    {
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

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

        // Test individual package search
        $results = Package::searchWithConsolidated('SEARCH123')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('SEARCH123', $results->first()->tracking_number);
    }

    /** @test */
    public function consolidated_package_search_scope_works()
    {
        $consolidationService = app(PackageConsolidationService::class);
        
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
            'tracking_number' => 'SEARCH456',
            'description' => 'Package 2',
            'status' => PackageStatus::READY,
        ]);

        // Consolidate packages
        $result = $consolidationService->consolidatePackages(
            [$package1->id, $package2->id],
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Test search by individual package tracking number
        $results = ConsolidatedPackage::search('SEARCH456')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals($consolidatedPackage->id, $results->first()->id);

        // Test search by consolidated tracking number
        $results2 = ConsolidatedPackage::search(substr($consolidatedPackage->consolidated_tracking_number, 0, 8))->get();
        
        $this->assertCount(1, $results2);
        $this->assertEquals($consolidatedPackage->id, $results2->first()->id);
    }

    /** @test */
    public function package_search_match_details_work()
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

        $matches = $package->getSearchMatchDetails('MATCH123');
        
        $this->assertNotEmpty($matches);
        $this->assertEquals('tracking_number', $matches[0]['field']);
        $this->assertEquals('exact', $matches[0]['type']);
    }

    /** @test */
    public function consolidated_package_search_match_details_work()
    {
        $consolidationService = app(PackageConsolidationService::class);
        
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create packages for consolidation
        $package1 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'MATCH001',
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
        $result = $consolidationService->consolidatePackages(
            [$package1->id, $package2->id],
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Test search match details for individual package within consolidated
        $matches = $consolidatedPackage->getSearchMatchDetails('MATCH001');
        
        $this->assertNotEmpty($matches);
        $individualPackageMatch = collect($matches)->where('type', 'individual_package')->first();
        $this->assertNotNull($individualPackageMatch);
        $this->assertEquals('individual_tracking_number', $individualPackageMatch['field']);
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

        // Test case insensitive tracking number search
        $results = Package::search('casesensitive')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('CaseSensitive123', $results->first()->tracking_number);

        // Test case insensitive description search
        $results2 = Package::search('test description')->get();
        $this->assertCount(1, $results2);
        $this->assertEquals('Test Description', $results2->first()->description);
    }
}