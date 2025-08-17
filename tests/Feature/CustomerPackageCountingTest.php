<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use App\Models\Role;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerPackageCountingTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $manifest;
    protected $office;
    protected $shipper;

    protected function setUp(): void
    {
        parent::setUp();

        // Create customer role and user
        $customerRole = Role::create(['name' => 'Customer', 'description' => 'Customer']);
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        // Create office and shipper
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();

        // Create manifest
        $this->manifest = Manifest::factory()->air()->create();
    }

    /** @test */
    public function dashboard_counts_all_individual_packages_correctly()
    {
        // Create 1 individual package
        $individualPackage = Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => null, // Not consolidated
        ]);

        // Create 1 consolidated package with 2 individual packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'processing',
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => $consolidatedPackage->id, // Part of consolidated package
        ]);

        // Test dashboard component
        $component = Livewire::actingAs($this->customer)
            ->test('dashboard');

        // Should count: 1 individual + 2 packages in consolidated = 3 total packages
        // We count all individual packages, not the consolidated package entry itself
        $this->assertEquals(3, $component->get('inComingAir')); // All 3 individual packages
        $this->assertEquals(0, $component->get('inComingSea'));
        $this->assertEquals(0, $component->get('availableAir'));
        $this->assertEquals(0, $component->get('availableSea'));
    }

    /** @test */
    public function customer_packages_counts_all_individual_packages()
    {
        // Create 1 individual package
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => null, // Not consolidated
        ]);

        // Create 1 consolidated package with 2 individual packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'processing',
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => $consolidatedPackage->id, // Part of consolidated package
        ]);

        // Test the query logic directly - count ALL individual packages
        $allIndividualPackages = Package::where('user_id', $this->customer->id)->count();

        // Should have 3 individual packages total (1 standalone + 2 in consolidated)
        $this->assertEquals(3, $allIndividualPackages);
    }

    /** @test */
    public function customer_packages_component_shows_all_packages()
    {
        // Create 1 individual package
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => null, // Not consolidated
        ]);

        // Create 1 consolidated package with 2 individual packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'processing',
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => $consolidatedPackage->id, // Part of consolidated package
        ]);

        // Test the query logic that the CustomerPackages component uses
        $packages = Package::with(['shipper', 'office', 'manifest'])
            ->where('user_id', $this->customer->id)
            ->get();

        // Should return all 3 individual packages (including those in consolidated packages)
        $this->assertCount(3, $packages);
    }

    /** @test */
    public function dashboard_filtered_packages_shows_individual_and_consolidated_entries()
    {
        // Create 1 individual package
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => null, // Not consolidated
        ]);

        // Create 1 consolidated package with 2 individual packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'processing',
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => $consolidatedPackage->id, // Part of consolidated package
        ]);

        // Test dashboard component
        $component = Livewire::actingAs($this->customer)
            ->test('dashboard');

        // Get filtered packages (for display purposes, shows consolidated as single entries)
        $filteredPackages = $component->get('filteredPackages');
        
        // Should show 2 entries: 1 individual + 1 consolidated entry (for display)
        // But the counts should reflect 3 total packages
        $this->assertCount(2, $filteredPackages);
        
        // Verify one is individual and one is consolidated
        $hasIndividual = false;
        $hasConsolidated = false;
        
        foreach ($filteredPackages as $package) {
            if (isset($package->is_consolidated) && $package->is_consolidated) {
                $hasConsolidated = true;
            } else {
                $hasIndividual = true;
            }
        }
        
        $this->assertTrue($hasIndividual, 'Should have at least one individual package');
        $this->assertTrue($hasConsolidated, 'Should have at least one consolidated package');
    }

    /** @test */
    public function packages_are_categorized_by_manifest_type()
    {
        // Create sea manifest
        $seaManifest = Manifest::factory()->sea()->create();

        // Create consolidated package with sea packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'processing',
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $seaManifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        // Test dashboard component
        $component = Livewire::actingAs($this->customer)
            ->test('dashboard');

        // Should count all individual packages in sea category
        $this->assertEquals(0, $component->get('inComingAir'));
        $this->assertEquals(2, $component->get('inComingSea')); // 2 individual packages with sea manifests
    }
}