<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\Profile;
use App\Services\CustomerStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CustomerStatisticsServiceCacheTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerStatisticsService $service;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(CustomerStatisticsService::class);
        
        // Create a test customer
        $this->customer = User::factory()->create([
            'role_id' => 3, // Customer role
        ]);
        
        Profile::factory()->create([
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_customer_statistics_are_cached()
    {
        // Create some packages for the customer
        Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
        ]);

        // Clear any existing cache
        Cache::flush();

        // First call should calculate and cache
        $stats1 = $this->service->getCustomerStatistics($this->customer);
        
        // Verify cache key exists
        $cacheKey = "customer_stats_{$this->customer->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached data
        $stats2 = $this->service->getCustomerStatistics($this->customer);
        
        $this->assertEquals($stats1, $stats2);
    }

    public function test_financial_summary_is_cached()
    {
        // Create packages with financial data
        Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
            'customs_duty' => 50.00,
            'storage_fee' => 25.00,
            'delivery_fee' => 15.00,
        ]);

        // Clear any existing cache
        Cache::flush();

        // First call should calculate and cache
        $financial1 = $this->service->getFinancialSummary($this->customer);
        
        // Verify cache key exists
        $cacheKey = "customer_financial_{$this->customer->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached data
        $financial2 = $this->service->getFinancialSummary($this->customer);
        
        $this->assertEquals($financial1, $financial2);
        $this->assertEquals(380.00, $financial1['total_spent']); // (100+50+25+15) * 2
    }

    public function test_package_metrics_are_cached()
    {
        // Create packages with different statuses
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'delivered',
            'weight' => 10.5,
        ]);
        
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'in_transit',
            'weight' => 15.2,
        ]);

        // Clear any existing cache
        Cache::flush();

        // First call should calculate and cache
        $metrics1 = $this->service->getPackageMetrics($this->customer);
        
        // Verify cache key exists
        $cacheKey = "customer_packages_{$this->customer->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached data
        $metrics2 = $this->service->getPackageMetrics($this->customer);
        
        $this->assertEquals($metrics1, $metrics2);
        $this->assertEquals(2, $metrics1['total_count']);
        $this->assertEquals(1, $metrics1['status_breakdown']['delivered']);
        $this->assertEquals(1, $metrics1['status_breakdown']['in_transit']);
    }

    public function test_shipping_patterns_are_cached()
    {
        // Create packages over different dates
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => now()->subMonths(2),
        ]);
        
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => now()->subMonth(),
        ]);

        // Clear any existing cache
        Cache::flush();

        // First call should calculate and cache
        $patterns1 = $this->service->getShippingPatterns($this->customer);
        
        // Verify cache key exists
        $cacheKey = "customer_patterns_{$this->customer->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached data
        $patterns2 = $this->service->getShippingPatterns($this->customer);
        
        $this->assertEquals($patterns1, $patterns2);
        $this->assertEquals(2, $patterns1['months_active']);
    }

    public function test_cache_can_be_cleared_for_customer()
    {
        // Create some data and cache it
        Package::factory()->create(['user_id' => $this->customer->id]);
        
        $this->service->getCustomerStatistics($this->customer);
        $this->service->getFinancialSummary($this->customer);
        $this->service->getPackageMetrics($this->customer);
        $this->service->getShippingPatterns($this->customer);

        // Verify all cache keys exist
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_packages_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_patterns_{$this->customer->id}"));

        // Clear customer cache
        $this->service->clearCustomerCache($this->customer);

        // Verify all cache keys are cleared
        $this->assertFalse(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_financial_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_packages_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_patterns_{$this->customer->id}"));
    }

    public function test_specific_cache_type_can_be_cleared()
    {
        // Create some data and cache it
        Package::factory()->create(['user_id' => $this->customer->id]);
        
        $this->service->getCustomerStatistics($this->customer);
        $this->service->getFinancialSummary($this->customer);

        // Verify cache keys exist
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));

        // Clear only financial cache
        $this->service->clearCustomerCacheType($this->customer, 'financial');

        // Verify only financial cache is cleared
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertFalse(Cache::has("customer_financial_{$this->customer->id}"));
    }

    public function test_cache_status_returns_correct_information()
    {
        // Create some data and cache it
        Package::factory()->create(['user_id' => $this->customer->id]);
        
        $this->service->getCustomerStatistics($this->customer);
        $this->service->getFinancialSummary($this->customer);

        $status = $this->service->getCacheStatus($this->customer);

        $this->assertArrayHasKey('stats', $status);
        $this->assertArrayHasKey('financial', $status);
        $this->assertArrayHasKey('patterns', $status);
        $this->assertArrayHasKey('packages', $status);

        $this->assertTrue($status['stats']['cached']);
        $this->assertTrue($status['financial']['cached']);
        $this->assertFalse($status['patterns']['cached']);
        $this->assertFalse($status['packages']['cached']);
    }

    public function test_force_refresh_bypasses_cache()
    {
        // Create initial package
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
        ]);

        // Get initial cached data
        $financial1 = $this->service->getFinancialSummary($this->customer);
        $this->assertEquals(100.00, $financial1['total_spent']);

        // Add another package
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 150.00,
        ]);

        // Without force refresh, should return cached data
        $financial2 = $this->service->getFinancialSummary($this->customer, false);
        $this->assertEquals(100.00, $financial2['total_spent']);

        // With force refresh, should recalculate
        $financial3 = $this->service->getFinancialSummary($this->customer, true);
        $this->assertEquals(250.00, $financial3['total_spent']);
    }

    public function test_cache_warm_up_preloads_all_data()
    {
        // Create some packages
        Package::factory()->count(2)->create(['user_id' => $this->customer->id]);

        // Clear any existing cache
        Cache::flush();

        // Warm up cache
        $this->service->warmUpCustomerCache($this->customer);

        // Verify all cache keys exist
        $this->assertTrue(Cache::has("customer_stats_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_financial_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_packages_{$this->customer->id}"));
        $this->assertTrue(Cache::has("customer_patterns_{$this->customer->id}"));
    }

    public function test_multiple_customers_cache_operations()
    {
        // Create additional customers
        $customer2 = User::factory()->create(['role_id' => 3]);
        $customer3 = User::factory()->create(['role_id' => 3]);
        
        Profile::factory()->create(['user_id' => $customer2->id]);
        Profile::factory()->create(['user_id' => $customer3->id]);

        // Create packages for each
        Package::factory()->create(['user_id' => $this->customer->id]);
        Package::factory()->create(['user_id' => $customer2->id]);
        Package::factory()->create(['user_id' => $customer3->id]);

        // Clear cache
        Cache::flush();

        // Warm up cache for multiple customers
        $customerIds = [$this->customer->id, $customer2->id, $customer3->id];
        $this->service->warmUpMultipleCustomersCache($customerIds);

        // Verify all customers have cached data
        foreach ($customerIds as $customerId) {
            $this->assertTrue(Cache::has("customer_stats_{$customerId}"));
            $this->assertTrue(Cache::has("customer_financial_{$customerId}"));
            $this->assertTrue(Cache::has("customer_packages_{$customerId}"));
            $this->assertTrue(Cache::has("customer_patterns_{$customerId}"));
        }

        // Clear cache for multiple customers
        $this->service->clearMultipleCustomersCache($customerIds);

        // Verify all customers' cache is cleared
        foreach ($customerIds as $customerId) {
            $this->assertFalse(Cache::has("customer_stats_{$customerId}"));
            $this->assertFalse(Cache::has("customer_financial_{$customerId}"));
            $this->assertFalse(Cache::has("customer_packages_{$customerId}"));
            $this->assertFalse(Cache::has("customer_patterns_{$customerId}"));
        }
    }

    public function test_cache_performance_metrics()
    {
        // Create multiple customers with packages
        $customers = User::factory()->count(5)->create(['role_id' => 3]);
        
        foreach ($customers as $customer) {
            Profile::factory()->create(['user_id' => $customer->id]);
            Package::factory()->create(['user_id' => $customer->id]);
        }

        // Clear cache
        Cache::flush();

        // Cache data for some customers
        $this->service->getCustomerStatistics($customers[0]);
        $this->service->getFinancialSummary($customers[1]);
        $this->service->getPackageMetrics($customers[2]);

        $metrics = $this->service->getCachePerformanceMetrics();

        $this->assertArrayHasKey('total_customers', $metrics);
        $this->assertArrayHasKey('cached_customers', $metrics);
        $this->assertArrayHasKey('cache_coverage_percentage', $metrics);
        $this->assertArrayHasKey('cache_by_type', $metrics);
        $this->assertArrayHasKey('type_coverage', $metrics);

        // Should have 6 total customers (5 created + 1 from setUp)
        $this->assertEquals(6, $metrics['total_customers']);
        $this->assertEquals(3, $metrics['cached_customers']);
        $this->assertEquals(50.0, $metrics['cache_coverage_percentage']);
    }
}