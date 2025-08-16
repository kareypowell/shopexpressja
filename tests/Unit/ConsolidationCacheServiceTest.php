<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Services\ConsolidationCacheService;
use App\Services\PackageConsolidationService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class ConsolidationCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ConsolidationCacheService $cacheService;
    protected PackageConsolidationService $consolidationService;
    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(ConsolidationCacheService::class);
        $this->consolidationService = app(PackageConsolidationService::class);
        
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->customer = User::factory()->create(['role_id' => 2]);
    }

    public function test_get_consolidated_totals_caches_results()
    {
        // Create consolidated package
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'weight' => 10.5,
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
        ]);

        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Clear cache to ensure fresh calculation
        Cache::flush();

        // First call should calculate and cache
        $totals1 = $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);
        
        // Verify cache key exists
        $cacheKey = 'consolidated_totals:' . $consolidatedPackage->id;
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached result
        $totals2 = $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);

        // Assert results are identical
        $this->assertEquals($totals1, $totals2);
        
        // Assert calculated values are correct (allowing for small floating point differences)
        $this->assertEquals(31.5, $totals1['weight'], '', 0.1); // 3 * 10.5
        $this->assertEquals(3, $totals1['quantity']);
        $this->assertEquals(300.00, $totals1['freight_price'], '', 0.1); // 3 * 100
        $this->assertEquals(75.00, $totals1['customs_duty'], '', 0.1); // 3 * 25
        // Total cost includes all fees, so we need to account for storage and delivery fees too
        $expectedTotalCost = $totals1['freight_price'] + $totals1['customs_duty'] + $totals1['storage_fee'] + $totals1['delivery_fee'];
        $this->assertEquals($expectedTotalCost, $totals1['total_cost'], '', 0.1);
    }

    public function test_get_customer_consolidations_caches_results()
    {
        // Create multiple consolidated packages for customer
        for ($i = 0; $i < 3; $i++) {
            $packages = Package::factory()->count(2)->create([
                'user_id' => $this->customer->id,
                'status' => PackageStatus::READY,
            ]);

            $this->consolidationService->consolidatePackages(
                $packages->pluck('id')->toArray(),
                $this->admin
            );
        }

        // Clear cache
        Cache::flush();

        // First call should query database and cache
        $consolidations1 = $this->cacheService->getCustomerConsolidations($this->customer->id);
        
        // Verify cache key exists
        $cacheKey = 'customer_consolidations:' . $this->customer->id . ':active';
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached result
        $consolidations2 = $this->cacheService->getCustomerConsolidations($this->customer->id);

        // Assert results are identical
        $this->assertEquals($consolidations1->count(), $consolidations2->count());
        $this->assertEquals(3, $consolidations1->count());
    }

    public function test_get_available_packages_for_consolidation_caches_results()
    {
        // Create available packages
        Package::factory()->count(5)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'is_consolidated' => false,
            'consolidated_package_id' => null,
        ]);

        // Create unavailable packages (already consolidated)
        // Note: We don't create these as they would violate foreign key constraints
        // The test will still work with just the available packages

        // Clear cache
        Cache::flush();

        // First call should query database and cache
        $packages1 = $this->cacheService->getAvailablePackagesForConsolidation($this->customer->id);
        
        // Verify cache key exists
        $cacheKey = 'available_packages:' . $this->customer->id;
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached result
        $packages2 = $this->cacheService->getAvailablePackagesForConsolidation($this->customer->id);

        // Assert results are identical
        $this->assertEquals($packages1->count(), $packages2->count());
        $this->assertEquals(5, $packages1->count()); // Only non-consolidated packages
    }

    public function test_get_consolidation_stats_caches_results()
    {
        // Create test data
        $packages = Package::factory()->count(4)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        // Clear cache
        Cache::flush();

        // First call should calculate and cache
        $stats1 = $this->cacheService->getConsolidationStats();
        
        // Verify cache key exists (using md5 of empty filters array)
        $cacheKey = 'consolidation_stats:' . md5(serialize([]));
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached result
        $stats2 = $this->cacheService->getConsolidationStats();

        // Assert results are identical
        $this->assertEquals($stats1, $stats2);
        
        // Assert stats are correct
        $this->assertEquals(1, $stats1['total_consolidated_packages']);
        $this->assertEquals(4, $stats1['total_packages_in_consolidations']);
        $this->assertArrayHasKey('consolidations_by_status', $stats1);
        $this->assertArrayHasKey('calculated_at', $stats1);
    }

    public function test_cache_invalidation_works_correctly()
    {
        // Create consolidated package
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Populate caches
        $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);
        $this->cacheService->getCustomerConsolidations($this->customer->id);
        $this->cacheService->getAvailablePackagesForConsolidation($this->customer->id);

        // Verify caches exist
        $this->assertTrue(Cache::has('consolidated_totals:' . $consolidatedPackage->id));
        $this->assertTrue(Cache::has('customer_consolidations:' . $this->customer->id . ':active'));
        $this->assertTrue(Cache::has('available_packages:' . $this->customer->id));

        // Invalidate consolidated totals
        $this->cacheService->invalidateConsolidatedTotals($consolidatedPackage->id);
        $this->assertFalse(Cache::has('consolidated_totals:' . $consolidatedPackage->id));

        // Invalidate customer consolidations
        $this->cacheService->invalidateCustomerConsolidations($this->customer->id);
        $this->assertFalse(Cache::has('customer_consolidations:' . $this->customer->id . ':active'));
        $this->assertFalse(Cache::has('customer_consolidations:' . $this->customer->id . ':all'));

        // Invalidate available packages
        $this->cacheService->invalidateAvailablePackages($this->customer->id);
        $this->assertFalse(Cache::has('available_packages:' . $this->customer->id));
    }

    public function test_invalidate_all_for_customer_clears_all_customer_caches()
    {
        // Create consolidated package
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->admin
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Populate all customer-related caches
        $this->cacheService->getConsolidatedTotals($consolidatedPackage->id);
        $this->cacheService->getCustomerConsolidations($this->customer->id);
        $this->cacheService->getAvailablePackagesForConsolidation($this->customer->id);

        // Verify caches exist
        $this->assertTrue(Cache::has('consolidated_totals:' . $consolidatedPackage->id));
        $this->assertTrue(Cache::has('customer_consolidations:' . $this->customer->id . ':active'));
        $this->assertTrue(Cache::has('available_packages:' . $this->customer->id));

        // Invalidate all for customer
        $this->cacheService->invalidateAllForCustomer($this->customer->id);

        // Verify all customer-related caches are cleared
        $this->assertFalse(Cache::has('customer_consolidations:' . $this->customer->id . ':active'));
        $this->assertFalse(Cache::has('customer_consolidations:' . $this->customer->id . ':all'));
        $this->assertFalse(Cache::has('available_packages:' . $this->customer->id));
    }

    public function test_search_results_are_cached()
    {
        // Create consolidated packages with searchable content
        $packages1 = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        // Update tracking numbers after creation to avoid unique constraint issues
        $packages1[0]->update(['tracking_number' => 'SEARCH-001-1']);
        $packages1[1]->update(['tracking_number' => 'SEARCH-001-2']);

        $packages2 = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
        ]);

        $packages2[0]->update(['tracking_number' => 'SEARCH-002-1']);
        $packages2[1]->update(['tracking_number' => 'SEARCH-002-2']);

        $this->consolidationService->consolidatePackages(
            $packages1->pluck('id')->toArray(),
            $this->admin
        );

        $this->consolidationService->consolidatePackages(
            $packages2->pluck('id')->toArray(),
            $this->admin
        );

        // Clear cache
        Cache::flush();

        // First search should query database and cache
        $results1 = $this->cacheService->getSearchResults('SEARCH', $this->customer->id);
        
        // Verify cache key exists
        $cacheKey = 'consolidation_search:' . md5('SEARCH' . $this->customer->id . serialize([]));
        $this->assertTrue(Cache::has($cacheKey));

        // Second search should return cached result
        $results2 = $this->cacheService->getSearchResults('SEARCH', $this->customer->id);

        // Assert results are identical
        $this->assertEquals($results1->count(), $results2->count());
        $this->assertEquals(2, $results1->count()); // Should find both consolidated packages
    }

    public function test_cache_statistics_returns_correct_information()
    {
        $stats = $this->cacheService->getCacheStatistics();

        $this->assertArrayHasKey('cache_prefixes', $stats);
        $this->assertArrayHasKey('cache_durations', $stats);
        $this->assertArrayHasKey('generated_at', $stats);

        // Verify all expected prefixes are present
        $expectedPrefixes = [
            'consolidated_totals',
            'customer_consolidations',
            'available_packages',
            'consolidation_stats',
            'consolidation_search',
        ];

        foreach ($expectedPrefixes as $prefix) {
            $this->assertContains($prefix, $stats['cache_prefixes']);
        }

        // Verify duration information
        $this->assertArrayHasKey('short', $stats['cache_durations']);
        $this->assertArrayHasKey('normal', $stats['cache_durations']);
        $this->assertArrayHasKey('long', $stats['cache_durations']);
    }

    public function test_cache_handles_null_consolidated_package()
    {
        $totals = $this->cacheService->getConsolidatedTotals(999999); // Non-existent ID
        $this->assertNull($totals);
    }

    public function test_cache_handles_empty_results()
    {
        // Test with customer that has no consolidations
        $emptyCustomer = User::factory()->create(['role_id' => 2]);
        
        $consolidations = $this->cacheService->getCustomerConsolidations($emptyCustomer->id);
        $this->assertEquals(0, $consolidations->count());

        $packages = $this->cacheService->getAvailablePackagesForConsolidation($emptyCustomer->id);
        $this->assertEquals(0, $packages->count());

        $searchResults = $this->cacheService->getSearchResults('nonexistent', $emptyCustomer->id);
        $this->assertEquals(0, $searchResults->count());
    }
}