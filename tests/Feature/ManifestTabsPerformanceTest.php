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
use App\Services\ManifestSummaryService;
use App\Services\WeightCalculationService;
use App\Services\VolumeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class ManifestTabsPerformanceTest extends TestCase
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
    public function tab_switching_performance_with_large_datasets()
    {
        $this->actingAs($this->admin);

        // Create large dataset
        $individualPackages = Package::factory()->count(500)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Create consolidated packages
        for ($i = 0; $i < 100; $i++) {
            $consolidatedPackage = ConsolidatedPackage::factory()->create([
                'customer_id' => $this->customer->id,
            ]);

            Package::factory()->count(3)->create([
                'manifest_id' => $this->manifest->id,
                'user_id' => $this->customer->id,
                'office_id' => $this->office->id,
                'shipper_id' => $this->shipper->id,
                'consolidated_package_id' => $consolidatedPackage->id,
            ]);
        }

        // Measure tab switching performance
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest]);

        // Switch between tabs multiple times
        for ($i = 0; $i < 5; $i++) {
            $component->call('switchTab', 'individual');
            $component->call('switchTab', 'consolidated');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(2.0, $executionTime, 'Tab switching should complete within 2 seconds');
        $this->assertLessThan(150, $queryCount, 'Tab switching should not generate excessive database queries');
    }

    /** @test */
    public function summary_calculation_performance_with_large_datasets()
    {
        $this->actingAs($this->admin);

        // Create large dataset with weight and volume data
        Package::factory()->count(1000)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'weight' => rand(1, 50),
            'length_inches' => rand(10, 100),
            'width_inches' => rand(10, 100),
            'height_inches' => rand(10, 100),
        ]);

        $summaryService = app(ManifestSummaryService::class);
        $weightService = app(WeightCalculationService::class);
        $volumeService = app(VolumeCalculationService::class);

        // Measure summary calculation performance
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        // Test weight calculation performance
        $packages = $this->manifest->packages;
        $totalWeight = $weightService->calculateTotalWeight($packages);

        // Test volume calculation performance
        $totalVolume = $volumeService->calculateTotalVolume($packages);

        // Test full summary calculation performance
        $summary = $summaryService->calculateAirManifestSummary($packages);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(1.0, $executionTime, 'Summary calculations should complete within 1 second');
        $this->assertLessThan(10, $queryCount, 'Summary calculations should use minimal database queries');
        $this->assertIsFloat($totalWeight);
        $this->assertIsFloat($totalVolume);
        $this->assertIsArray($summary);
    }

    /** @test */
    public function enhanced_manifest_summary_component_performance()
    {
        $this->actingAs($this->admin);

        // Create packages with various data
        Package::factory()->count(200)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'weight' => rand(1, 50),
            'estimated_value' => rand(10, 1000),
        ]);

        // Measure component rendering performance
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $component = Livewire::test('manifests.enhanced-manifest-summary', ['manifest' => $this->manifest]);

        // Test multiple refreshes
        for ($i = 0; $i < 3; $i++) {
            $component->call('$refresh');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(0.5, $executionTime, 'Summary component should render quickly');
        $this->assertLessThan(15, $queryCount, 'Summary component should minimize database queries');
    }

    /** @test */
    public function individual_packages_tab_performance_with_filtering()
    {
        $this->actingAs($this->admin);

        // Create large dataset
        Package::factory()->count(300)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Measure filtering performance
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        // Test search performance
        $component->set('search', 'test');
        $component->set('statusFilter', 'processing');
        $component->call('sortBy', 'tracking_number');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(1.0, $executionTime, 'Individual packages tab filtering should be fast');
        $this->assertLessThan(20, $queryCount, 'Filtering should use efficient queries');
    }

    /** @test */
    public function consolidated_packages_tab_performance_with_filtering()
    {
        $this->actingAs($this->admin);

        // Create large dataset of consolidated packages
        for ($i = 0; $i < 100; $i++) {
            $consolidatedPackage = ConsolidatedPackage::factory()->create([
                'customer_id' => $this->customer->id,
            ]);

            Package::factory()->count(3)->create([
                'manifest_id' => $this->manifest->id,
                'user_id' => $this->customer->id,
                'office_id' => $this->office->id,
                'shipper_id' => $this->shipper->id,
                'consolidated_package_id' => $consolidatedPackage->id,
            ]);
        }

        // Measure filtering performance
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $component = Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest]);

        // Test search and filtering performance
        $component->set('search', 'test');
        $component->set('statusFilter', 'processing');
        $component->call('sortBy', 'consolidated_tracking_number');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(1.0, $executionTime, 'Consolidated packages tab filtering should be fast');
        $this->assertLessThan(25, $queryCount, 'Filtering should use efficient queries');
    }

    /** @test */
    public function bulk_operations_performance()
    {
        $this->actingAs($this->admin);

        // Create packages for bulk operations
        $packages = Package::factory()->count(100)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Measure bulk operation performance
        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest])
            ->set('selectedPackages', $packages->pluck('id')->toArray())
            ->set('bulkStatus', 'ready')
            ->call('confirmBulkStatusUpdate');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertions
        $this->assertLessThan(2.0, $executionTime, 'Bulk operations should complete within 2 seconds');
        $this->assertLessThan(50, $queryCount, 'Bulk operations should use efficient batch queries');
    }

    /** @test */
    public function memory_usage_stays_within_limits()
    {
        $this->actingAs($this->admin);

        $initialMemory = memory_get_usage(true);

        // Create large dataset
        Package::factory()->count(500)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test memory usage during component operations
        $component = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest]);
        
        for ($i = 0; $i < 10; $i++) {
            $component->call('switchTab', 'individual');
            $component->call('switchTab', 'consolidated');
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory usage should not increase excessively (allow for 50MB increase)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory usage should not increase excessively');
    }

    /** @test */
    public function concurrent_tab_operations_performance()
    {
        $this->actingAs($this->admin);

        // Create test data
        Package::factory()->count(100)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Simulate concurrent operations
        $startTime = microtime(true);

        $containerComponent = Livewire::test('manifests.manifest-tabs-container', ['manifest' => $this->manifest]);
        $individualComponent = Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);
        $consolidatedComponent = Livewire::test('manifests.consolidated-packages-tab', ['manifest' => $this->manifest]);
        $summaryComponent = Livewire::test('manifests.enhanced-manifest-summary', ['manifest' => $this->manifest]);

        // Perform operations simultaneously
        $containerComponent->call('switchTab', 'individual');
        $individualComponent->set('search', 'test');
        $consolidatedComponent->set('search', 'consolidated');
        $summaryComponent->call('$refresh');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertion
        $this->assertLessThan(1.5, $executionTime, 'Concurrent operations should complete efficiently');
    }

    /** @test */
    public function database_query_optimization_verification()
    {
        $this->actingAs($this->admin);

        // Create test data
        Package::factory()->count(50)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $queryCount = 0;
        $queries = [];

        DB::listen(function ($query) use (&$queryCount, &$queries) {
            $queryCount++;
            $queries[] = $query->sql;
        });

        // Test individual packages tab
        Livewire::test('manifests.individual-packages-tab', ['manifest' => $this->manifest]);

        // Verify no N+1 queries
        $duplicateQueries = array_count_values($queries);
        $maxDuplicates = max($duplicateQueries);

        $this->assertLessThan(5, $maxDuplicates, 'Should not have excessive duplicate queries (N+1 problem)');
        $this->assertLessThan(15, $queryCount, 'Should use minimal database queries');
    }
}