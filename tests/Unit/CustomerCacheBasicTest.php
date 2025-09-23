<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\Profile;
use App\Services\CustomerStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CustomerCacheBasicTest extends TestCase
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

    public function test_customer_statistics_service_exists()
    {
        $this->assertInstanceOf(CustomerStatisticsService::class, $this->service);
    }

    public function test_cache_keys_are_generated_correctly()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);
        
        $key = $method->invoke($this->service, 'stats', 123);
        $this->assertEquals('customer_stats_123', $key);
        
        $key = $method->invoke($this->service, 'financial', 456);
        $this->assertEquals('customer_financial_456', $key);
    }

    public function test_cache_can_be_cleared()
    {
        // Set some cache data
        Cache::put('customer_stats_' . $this->customer->id, ['test' => 'data'], 60);
        Cache::put('customer_financial_' . $this->customer->id, ['test' => 'data'], 60);
        
        // Verify cache exists
        $this->assertTrue(Cache::has('customer_stats_' . $this->customer->id));
        $this->assertTrue(Cache::has('customer_financial_' . $this->customer->id));
        
        // Clear cache
        $this->service->clearCustomerCache($this->customer);
        
        // Verify cache is cleared
        $this->assertFalse(Cache::has('customer_stats_' . $this->customer->id));
        $this->assertFalse(Cache::has('customer_financial_' . $this->customer->id));
    }

    public function test_cache_status_returns_correct_structure()
    {
        $status = $this->service->getCacheStatus($this->customer);
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('stats', $status);
        $this->assertArrayHasKey('financial', $status);
        $this->assertArrayHasKey('patterns', $status);
        $this->assertArrayHasKey('packages', $status);
        
        // Each status should have the required structure
        foreach ($status as $type => $info) {
            $this->assertArrayHasKey('cached', $info);
            $this->assertArrayHasKey('key', $info);
            $this->assertArrayHasKey('ttl', $info);
            $this->assertIsBool($info['cached']);
            $this->assertIsString($info['key']);
        }
    }

    public function test_cache_performance_metrics_structure()
    {
        $metrics = $this->service->getCachePerformanceMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_customers', $metrics);
        $this->assertArrayHasKey('cached_customers', $metrics);
        $this->assertArrayHasKey('cache_coverage_percentage', $metrics);
        $this->assertArrayHasKey('cache_by_type', $metrics);
        $this->assertArrayHasKey('type_coverage', $metrics);
        
        $this->assertIsInt($metrics['total_customers']);
        $this->assertIsInt($metrics['cached_customers']);
        $this->assertIsFloat($metrics['cache_coverage_percentage']);
        $this->assertIsArray($metrics['cache_by_type']);
        $this->assertIsArray($metrics['type_coverage']);
    }

    public function test_force_refresh_parameter_works()
    {
        // Create a package for the customer with specific financial values
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Get initial data (should cache it)
        $financial1 = $this->service->getFinancialSummary($this->customer);
        $this->assertEquals(100.00, $financial1['total_spent']);

        // Manually set different cache data
        Cache::put('customer_financial_' . $this->customer->id, ['total_spent' => 999.99], 60);
        
        // Without force refresh, should return cached data
        $financial2 = $this->service->getFinancialSummary($this->customer, false);
        $this->assertEquals(999.99, $financial2['total_spent']);

        // With force refresh, should recalculate
        $financial3 = $this->service->getFinancialSummary($this->customer, true);
        $this->assertEquals(100.00, $financial3['total_spent']);
    }

    public function test_multiple_customers_cache_clearing()
    {
        // Create additional customers
        $customer2 = User::factory()->create(['role_id' => 3]);
        $customer3 = User::factory()->create(['role_id' => 3]);
        
        Profile::factory()->create(['user_id' => $customer2->id]);
        Profile::factory()->create(['user_id' => $customer3->id]);

        // Set cache for all customers
        $customerIds = [$this->customer->id, $customer2->id, $customer3->id];
        foreach ($customerIds as $customerId) {
            Cache::put('customer_stats_' . $customerId, ['test' => 'data'], 60);
            Cache::put('customer_financial_' . $customerId, ['test' => 'data'], 60);
        }

        // Verify cache exists
        foreach ($customerIds as $customerId) {
            $this->assertTrue(Cache::has('customer_stats_' . $customerId));
            $this->assertTrue(Cache::has('customer_financial_' . $customerId));
        }

        // Clear cache for multiple customers
        $this->service->clearMultipleCustomersCache($customerIds);

        // Verify cache is cleared for all
        foreach ($customerIds as $customerId) {
            $this->assertFalse(Cache::has('customer_stats_' . $customerId));
            $this->assertFalse(Cache::has('customer_financial_' . $customerId));
        }
    }
}