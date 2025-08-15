<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\ConsolidatedPackage;
use App\Models\Office;
use App\Models\Shipper;
use App\Services\PackageConsolidationService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class ConsolidatedPackageManifestIntegrationTest extends TestCase
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
            'role_id' => 1, // Admin role
        ]);

        // Create customer
        $this->customer = User::factory()->create([
            'role_id' => 3, // Customer role
        ]);

        // Create office and shipper
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();

        // Create manifest
        $this->manifest = Manifest::factory()->create([
            'type' => 'air',
            'is_open' => true,
        ]);
    }

    /** @test */
    public function manifest_package_component_displays_consolidated_packages()
    {
        // Create packages for consolidation
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Consolidate packages
        $consolidationService = app(PackageConsolidationService::class);
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Test the manifest package component
        $component = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\Packages\ManifestPackage::class, [
                'manifest' => $this->manifest
            ]);

        $component->assertSee($consolidatedPackage->consolidated_tracking_number)
                  ->assertSee('Consolidated Groups')
                  ->assertSee('3 packages');
    }

    /** @test */
    public function manifest_package_component_calculates_totals_with_consolidated_packages()
    {
        // Create individual packages
        $individualPackages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'weight' => 10,
            'freight_price' => 100,
        ]);

        // Create packages for consolidation
        $packagesForConsolidation = Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'weight' => 5,
            'freight_price' => 50,
        ]);

        // Consolidate packages
        $consolidationService = app(PackageConsolidationService::class);
        $result = $consolidationService->consolidatePackages(
            $packagesForConsolidation->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result['success']);

        // Test the manifest package component
        $component = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\Packages\ManifestPackage::class, [
                'manifest' => $this->manifest
            ]);

        $totals = $component->get('manifestTotals');

        $this->assertEquals(2, $totals['individual_packages']);
        $this->assertEquals(1, $totals['consolidated_packages']);
        $this->assertEquals(3, $totals['total_packages_in_consolidated']);
        $this->assertEquals(35, $totals['total_weight']); // 2*10 + 3*5
        $this->assertEquals(350, $totals['total_freight_price']); // 2*100 + 3*50
    }

    /** @test */
    public function manifest_package_component_can_update_consolidated_package_status()
    {
        // Create packages for consolidation
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Consolidate packages
        $consolidationService = app(PackageConsolidationService::class);
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Test updating consolidated package status
        $component = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\Packages\ManifestPackage::class, [
                'manifest' => $this->manifest
            ]);

        $component->call('updateConsolidatedPackageStatus', $consolidatedPackage->id, PackageStatus::SHIPPED);

        // Verify status was updated
        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::SHIPPED, $consolidatedPackage->status);

        // Verify individual packages were also updated
        foreach ($packages as $package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::SHIPPED, $package->status);
        }
    }

    /** @test */
    public function package_workflow_component_displays_consolidated_packages()
    {
        // Create packages for consolidation
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Consolidate packages
        $consolidationService = app(PackageConsolidationService::class);
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Test the package workflow component
        $component = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\PackageWorkflow::class, [
                'manifest' => $this->manifest
            ]);

        $component->assertSee($consolidatedPackage->consolidated_tracking_number)
                  ->assertSee('Consolidated Packages')
                  ->assertSee('3 packages');
    }

    /** @test */
    public function package_workflow_component_can_update_consolidated_package_status()
    {
        // Create packages for consolidation
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Consolidate packages
        $consolidationService = app(PackageConsolidationService::class);
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Test updating consolidated package status through workflow
        $component = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\PackageWorkflow::class, [
                'manifest' => $this->manifest
            ]);

        $component->call('updateConsolidatedPackageStatus', $consolidatedPackage->id, PackageStatus::CUSTOMS);

        // Verify status was updated
        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::CUSTOMS, $consolidatedPackage->status);

        // Verify individual packages were also updated
        foreach ($packages as $package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::CUSTOMS, $package->status);
        }
    }

    /** @test */
    public function bulk_status_update_handles_consolidated_packages()
    {
        // Create individual packages
        $individualPackages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Create packages for consolidation
        $packagesForConsolidation = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Consolidate packages
        $consolidationService = app(PackageConsolidationService::class);
        $result = $consolidationService->consolidatePackages(
            $packagesForConsolidation->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result['success']);

        // Test bulk status update in workflow
        $allPackageIds = $individualPackages->pluck('id')
            ->merge($packagesForConsolidation->pluck('id'))
            ->toArray();

        $component = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\PackageWorkflow::class, [
                'manifest' => $this->manifest
            ]);

        $component->set('selectedPackages', $allPackageIds)
                  ->set('bulkStatus', PackageStatus::SHIPPED)
                  ->call('confirmBulkStatusUpdate')
                  ->call('executeBulkStatusUpdate');

        // Verify individual packages were updated
        foreach ($individualPackages as $package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::SHIPPED, $package->status);
        }

        // Verify consolidated packages were updated
        foreach ($packagesForConsolidation as $package) {
            $package->refresh();
            $this->assertEquals(PackageStatus::SHIPPED, $package->status);
        }
    }

    /** @test */
    public function manifest_packages_table_handles_consolidated_packages()
    {
        // Create packages for consolidation
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Consolidate packages
        $consolidationService = app(PackageConsolidationService::class);
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result['success']);

        // Test the manifest packages table component
        $component = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\Packages\ManifestPackagesTable::class)
            ->set('manifest_id', $this->manifest->id);

        // Verify the component loads without errors and shows packages
        $component->assertSuccessful();

        // Test that consolidated packages are handled properly by checking the query
        $query = $component->instance()->query();
        $this->assertNotNull($query);
        
        // Verify packages are loaded with consolidated package relationships
        $packagesFromQuery = $query->get();
        $this->assertGreaterThan(0, $packagesFromQuery->count());
        
        // Verify consolidated packages have the relationship loaded
        foreach ($packagesFromQuery as $package) {
            if ($package->isConsolidated()) {
                $this->assertNotNull($package->consolidatedPackage);
            }
        }
    }

    /** @test */
    public function search_functionality_works_with_consolidated_packages()
    {
        // Create packages for consolidation with unique tracking numbers
        $packages = collect();
        for ($i = 1; $i <= 2; $i++) {
            $packages->push(Package::factory()->create([
                'user_id' => $this->customer->id,
                'manifest_id' => $this->manifest->id,
                'office_id' => $this->office->id,
                'shipper_id' => $this->shipper->id,
                'tracking_number' => 'TEST123-' . $i,
                'status' => PackageStatus::PROCESSING,
            ]));
        }

        // Consolidate packages
        $consolidationService = app(PackageConsolidationService::class);
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Test search in manifest package component
        $component = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\Packages\ManifestPackage::class, [
                'manifest' => $this->manifest
            ]);

        // Search by individual tracking number should find consolidated package
        $component->set('searchTerm', 'TEST123-1');

        $packages = $component->get('packages');
        $this->assertGreaterThan(0, $packages->count());

        // Search by consolidated tracking number
        $component->set('searchTerm', $consolidatedPackage->consolidated_tracking_number);

        $packages = $component->get('packages');
        $this->assertGreaterThan(0, $packages->count());
    }

    /** @test */
    public function consolidated_packages_maintain_manifest_relationship()
    {
        // Create packages in different manifests
        $manifest2 = Manifest::factory()->create(['type' => 'air']);

        $packagesManifest1 = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $this->manifest->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        $packagesManifest2 = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest2->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'status' => PackageStatus::PROCESSING,
        ]);

        // Consolidate packages from manifest 1
        $consolidationService = app(PackageConsolidationService::class);
        $result1 = $consolidationService->consolidatePackages(
            $packagesManifest1->pluck('id')->toArray(),
            $this->admin
        );

        $this->assertTrue($result1['success']);

        // Test that manifest 1 component only shows its consolidated packages
        $component1 = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\Packages\ManifestPackage::class, [
                'manifest' => $this->manifest
            ]);

        $consolidatedPackages1 = $component1->get('consolidatedPackages');
        $this->assertEquals(1, $consolidatedPackages1->count());

        // Test that manifest 2 component shows no consolidated packages
        $component2 = Livewire::actingAs($this->admin)
            ->test(\App\Http\Livewire\Manifests\Packages\ManifestPackage::class, [
                'manifest' => $manifest2
            ]);

        $consolidatedPackages2 = $component2->get('consolidatedPackages');
        $this->assertEquals(0, $consolidatedPackages2->count());
    }
}