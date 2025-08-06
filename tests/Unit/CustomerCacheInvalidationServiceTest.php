<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\Profile;
use App\Services\CustomerCacheInvalidationService;
use App\Services\CustomerStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CustomerCacheInvalidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerCacheInvalidationService $invalidationService;
    protected CustomerStatisticsService $statisticsService;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->statisticsService = app(CustomerStatisticsService::class);
        $this->invalidationService = app(CustomerCacheInvalidationService::class);
        
        // Create a test customer
        $this->customer = User::factory()->create([
            'role_id' => 3, // Customer role
        ]);
        
        Profile::factory()->create([
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_customer_profile_update_clears_cache()
    {
        // Create some packages and cache data
        Package::factory()->create(['user_id' => $this->customer->id]);
        $this->statisticsService->getCustomerStatistics($this->customer);
        $this->statisticsService->getFinancialSummary($this->customer);

        // Verify cache exists
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));

        // Handle profile update
        $this->invalidationService->handleCustomerProfileUpdate($this->customer);

        // Verify cache is cleared
        $this->assertFalse(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_financial_{$this->customer->id}"));
    }

    public function test_customer_creation_warms_up_cache()
    {
        // Clear any existing cache
        Cache::flush();

        // Handle customer creation
        $this->invalidationService->handleCustomerCreation($this->customer);

        // Verify cache is warmed up (though data might be minimal for new customer)
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_packages_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_patterns_{$this->customer->id}"));
    }

    public function test_customer_deletion_clears_cache()
    {
        // Create some packages and cache data
        Package::factory()->create(['user_id' => $this->customer->id]);
        $this->statisticsService->getCustomerStatistics($this->customer);

        // Verify cache exists
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));

        // Handle customer deletion
        $this->invalidationService->handleCustomerDeletion($this->customer);

        // Verify cache is cleared
        $this->assertFalse(Cache::has("customer_stats_{$this->customer->id}"));
    }

    public function test_package_creation_clears_customer_cache()
    {
        // Cache customer data
        $this->statisticsService->getCustomerStatistics($this->customer);
        $this->statisticsService->getFinancialSummary($this->customer);

        // Verify cache exists
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));

        // Create a package
        $package = Package::factory()->create(['user_id' => $this->customer->id]);

        // Handle package creation
        $this->invalidationService->handlePackageCreation($package);

        // Verify cache is cleared
        $this->assertFalse(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_financial_{$this->customer->id}"));
    }

    public function test_package_update_with_financial_changes_clears_all_cache()
    {
        // Create a package and cache data
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
        ]);
        
        $this->statisticsService->getCustomerStatistics($this->customer);
        $this->statisticsService->getFinancialSummary($this->customer);
        $this->statisticsService->getPackageMetrics($this->customer);

        // Verify cache exists
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_packages_{$this->customer->id}"));

        // Handle package update with financial changes
        $changes = ['freight_price' => 150.00];
        $this->invalidationService->handlePackageUpdate($package, $changes);

        // Verify all cache is cleared
        $this->assertFalse(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_financial_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_packages_{$this->customer->id}"));
    }

    public function test_package_update_without_financial_changes_clears_only_package_cache()
    {
        // Create a package and cache data
        $package = Package::factory()->create(['user_id' => $this->customer->id]);
        
        $this->statisticsService->getCustomerStatistics($this->customer);
        $this->statisticsService->getFinancialSummary($this->customer);
        $this->statisticsService->getPackageMetrics($this->customer);

        // Verify cache exists
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_packages_{$this->customer->id}"));

        // Handle package update without financial changes
        $changes = ['description' => 'Updated description'];
        $this->invalidationService->handlePackageUpdate($package, $changes);

        // Verify only package cache is cleared
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_packages_{$this->customer->id}"));
    }

    public function test_bulk_package_operations_clear_multiple_customer_caches()
    {
        // Create additional customers
        $customer2 = User::factory()->create(['role_id' => 3]);
        $customer3 = User::factory()->create(['role_id' => 3]);
        
        Profile::factory()->create(['user_id' => $customer2->id]);
        Profile::factory()->create(['user_id' => $customer3->id]);

        // Create packages for each customer
        $package1 = Package::factory()->create(['user_id' => $this->customer->id]);
        $package2 = Package::factory()->create(['user_id' => $customer2->id]);
        $package3 = Package::factory()->create(['user_id' => $customer3->id]);

        // Cache data for all customers
        $this->statisticsService->getCustomerStatistics($this->customer);
        $this->statisticsService->getCustomerStatistics($customer2);
        $this->statisticsService->getCustomerStatistics($customer3);

        // Verify cache exists
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_stats_{$customer2->id}"));
        $this->assertTrue(Cache::has("customer_stats_{$customer3->id}"));

        // Handle bulk package operations
        $packageIds = [$package1->id, $package2->id, $package3->id];
        $this->invalidationService->handleBulkPackageOperations($packageIds);

        // Verify all customer caches are cleared
        $this->assertFalse(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_stats_{$customer2->id}"));
        $this->assertFalse(Cache::has("customer_stats_{$customer3->id}"));
    }

    public function test_system_cache_refresh_clears_all_customer_cache()
    {
        // Create multiple customers with packages
        $customers = User::factory()->count(3)->create(['role_id' => 3]);
        
        foreach ($customers as $customer) {
            Profile::factory()->create(['user_id' => $customer->id]);
            Package::factory()->create(['user_id' => $customer->id]);
            $this->statisticsService->getCustomerStatistics($customer);
        }

        // Also cache data for the main test customer
        Package::factory()->create(['user_id' => $this->customer->id]);
        $this->statisticsService->getCustomerStatistics($this->customer);

        // Verify cache exists for all customers
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        foreach ($customers as $customer) {
            $this->assertTrue(Cache::has("customer_stats_{$customer->id}"));
        }

        // Handle system cache refresh
        $this->invalidationService->handleSystemCacheRefresh();

        // Verify all cache is cleared
        $this->assertFalse(Cache::has("customer_stats_{$this->customer->id}"));
        foreach ($customers as $customer) {
            $this->assertFalse(Cache::has("customer_stats_{$customer->id}"));
        }
    }

    public function test_active_customer_cache_warm_up()
    {
        // Create customers with recent packages
        $activeCustomers = User::factory()->count(3)->create(['role_id' => 3]);
        $inactiveCustomer = User::factory()->create(['role_id' => 3]);
        
        foreach ($activeCustomers as $customer) {
            Profile::factory()->create(['user_id' => $customer->id]);
            // Create recent packages
            Package::factory()->count(2)->create([
                'user_id' => $customer->id,
                'created_at' => now()->subWeeks(2),
            ]);
        }
        
        // Create inactive customer with old packages
        Profile::factory()->create(['user_id' => $inactiveCustomer->id]);
        Package::factory()->create([
            'user_id' => $inactiveCustomer->id,
            'created_at' => now()->subMonths(6),
        ]);

        // Clear cache
        Cache::flush();

        // Schedule cache warm-up for active customers
        $this->invalidationService->scheduleActiveCustomerCacheWarmUp(10);

        // Verify active customers have cached data
        foreach ($activeCustomers as $customer) {
            $this->assertTrue(Cache::has("customer_stats_{$customer->id}"));
        }

        // Inactive customer should not have cached data
        $this->assertFalse(Cache::has("customer_stats_{$inactiveCustomer->id}"));
    }

    public function test_cache_invalidation_stats()
    {
        // Create some test data
        Package::factory()->create(['user_id' => $this->customer->id]);
        $this->statisticsService->getCustomerStatistics($this->customer);

        $stats = $this->invalidationService->getCacheInvalidationStats();

        $this->assertArrayHasKey('cache_performance', $stats);
        $this->assertArrayHasKey('last_system_refresh', $stats);
        $this->assertArrayHasKey('invalidation_events_today', $stats);

        $this->assertIsArray($stats['cache_performance']);
        $this->assertArrayHasKey('total_customers', $stats['cache_performance']);
        $this->assertArrayHasKey('cached_customers', $stats['cache_performance']);
    }
}