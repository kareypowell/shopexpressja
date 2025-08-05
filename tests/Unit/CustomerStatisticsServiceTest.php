<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\Role;
use App\Services\CustomerStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CustomerStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerStatisticsService $service;
    private User $customer;
    private Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CustomerStatisticsService();
        
        // Create customer role
        $this->customerRole = Role::factory()->create([
            'name' => 'customer',
            'description' => 'Customer role'
        ]);
        
        // Create a test customer
        $this->customer = User::factory()->create([
            'role_id' => $this->customerRole->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ]);
    }

    public function test_get_customer_statistics_returns_comprehensive_data()
    {
        // Create test packages with different statuses and costs
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'delivered',
            'weight' => 10.5,
            'cubic_feet' => 2.5,
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
            'created_at' => Carbon::now()->subDays(30)
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'in_transit',
            'weight' => 5.0,
            'cubic_feet' => 1.2,
            'freight_price' => 50.00,
            'customs_duty' => 12.50,
            'storage_fee' => 5.00,
            'delivery_fee' => 7.50,
            'created_at' => Carbon::now()->subDays(15)
        ]);

        $statistics = $this->service->getCustomerStatistics($this->customer);

        $this->assertIsArray($statistics);
        $this->assertEquals($this->customer->id, $statistics['customer_id']);
        $this->assertArrayHasKey('packages', $statistics);
        $this->assertArrayHasKey('financial', $statistics);
        $this->assertArrayHasKey('patterns', $statistics);
        $this->assertArrayHasKey('generated_at', $statistics);
        
        // Test package metrics
        $this->assertEquals(2, $statistics['packages']['total_count']);
        $this->assertEquals(1, $statistics['packages']['status_breakdown']['delivered']);
        $this->assertEquals(1, $statistics['packages']['status_breakdown']['in_transit']);
        
        // Test financial data
        $this->assertEquals(225.00, $statistics['financial']['total_spent']); // Total of both packages
        $this->assertEquals(112.50, $statistics['financial']['average_per_package']);
    }

    public function test_get_package_metrics_calculates_correctly()
    {
        // Create packages with known values
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'delivered',
            'weight' => 10.0,
            'cubic_feet' => 2.0
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'delivered',
            'weight' => 20.0,
            'cubic_feet' => 4.0
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'in_transit',
            'weight' => 15.0,
            'cubic_feet' => 3.0
        ]);

        $metrics = $this->service->getPackageMetrics($this->customer);

        $this->assertEquals(3, $metrics['total_count']);
        $this->assertEquals(2, $metrics['status_breakdown']['delivered']);
        $this->assertEquals(1, $metrics['status_breakdown']['in_transit']);
        $this->assertEquals(45.0, $metrics['weight_statistics']['total_weight']);
        $this->assertEquals(15.0, $metrics['weight_statistics']['average_weight']);
        $this->assertEquals(20.0, $metrics['weight_statistics']['max_weight']);
        $this->assertEquals(10.0, $metrics['weight_statistics']['min_weight']);
        $this->assertEquals(9.0, $metrics['volume_statistics']['total_cubic_feet']);
        $this->assertEquals(3.0, $metrics['volume_statistics']['average_cubic_feet']);
        $this->assertEquals(66.67, $metrics['delivery_rate']); // 2 delivered out of 3 total
    }

    public function test_get_financial_summary_calculates_correctly()
    {
        // Create packages with known financial values
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 200.00,
            'customs_duty' => 50.00,
            'storage_fee' => 20.00,
            'delivery_fee' => 30.00
        ]);

        $financial = $this->service->getFinancialSummary($this->customer);

        $this->assertEquals(450.00, $financial['total_spent']);
        $this->assertEquals(225.00, $financial['average_per_package']);
        $this->assertEquals(300.00, $financial['cost_breakdown']['freight']);
        $this->assertEquals(75.00, $financial['cost_breakdown']['customs']);
        $this->assertEquals(30.00, $financial['cost_breakdown']['storage']);
        $this->assertEquals(45.00, $financial['cost_breakdown']['delivery']);
        
        // Test percentages
        $this->assertEquals(66.7, $financial['cost_percentages']['freight']);
        $this->assertEquals(16.7, $financial['cost_percentages']['customs']);
        $this->assertEquals(6.7, $financial['cost_percentages']['storage']);
        $this->assertEquals(10.0, $financial['cost_percentages']['delivery']);
        
        // Test averages
        $this->assertEquals(150.00, $financial['average_costs']['freight']);
        $this->assertEquals(37.50, $financial['average_costs']['customs']);
        $this->assertEquals(15.00, $financial['average_costs']['storage']);
        $this->assertEquals(22.50, $financial['average_costs']['delivery']);
    }

    public function test_get_shipping_patterns_analyzes_correctly()
    {
        // Create packages over different time periods
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => Carbon::now()->subMonths(6)
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => Carbon::now()->subMonths(3)
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => Carbon::now()->subMonth()
        ]);

        $patterns = $this->service->getShippingPatterns($this->customer);

        $this->assertIsArray($patterns);
        $this->assertArrayHasKey('shipping_frequency', $patterns);
        $this->assertArrayHasKey('months_active', $patterns);
        $this->assertArrayHasKey('first_shipment', $patterns);
        $this->assertArrayHasKey('last_shipment', $patterns);
        $this->assertArrayHasKey('monthly_breakdown', $patterns);
        $this->assertArrayHasKey('seasonal_patterns', $patterns);
        $this->assertArrayHasKey('average_days_between_shipments', $patterns);
        
        $this->assertEquals(6, $patterns['months_active']); // 6 months difference
        $this->assertGreaterThan(0, $patterns['shipping_frequency']);
        $this->assertIsString($patterns['first_shipment']);
        $this->assertIsString($patterns['last_shipment']);
    }

    public function test_shipping_patterns_handles_no_packages()
    {
        $patterns = $this->service->getShippingPatterns($this->customer);

        $this->assertEquals(0, $patterns['shipping_frequency']);
        $this->assertEquals(0, $patterns['months_active']);
        $this->assertNull($patterns['first_shipment']);
        $this->assertNull($patterns['last_shipment']);
        $this->assertEmpty($patterns['monthly_breakdown']);
        $this->assertEmpty($patterns['seasonal_patterns']);
        $this->assertEquals(0, $patterns['average_days_between_shipments']);
    }

    public function test_caching_works_correctly()
    {
        Cache::flush(); // Clear any existing cache
        
        // Create a package
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00
        ]);

        // First call should cache the result
        $firstCall = $this->service->getCustomerStatistics($this->customer);
        
        // Verify cache exists
        $cacheStatus = $this->service->getCacheStatus($this->customer);
        $this->assertTrue($cacheStatus['stats']['cached']);
        
        // Second call should return cached result
        $secondCall = $this->service->getCustomerStatistics($this->customer);
        
        $this->assertEquals($firstCall, $secondCall);
    }

    public function test_clear_customer_cache_works()
    {
        // Create cached data
        $this->service->getCustomerStatistics($this->customer);
        
        // Verify cache exists
        $cacheStatus = $this->service->getCacheStatus($this->customer);
        $this->assertTrue($cacheStatus['stats']['cached']);
        
        // Clear cache
        $this->service->clearCustomerCache($this->customer);
        
        // Verify cache is cleared
        $cacheStatus = $this->service->getCacheStatus($this->customer);
        $this->assertFalse($cacheStatus['stats']['cached']);
    }

    public function test_monthly_breakdown_includes_recent_months()
    {
        // Create packages in different months
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
            'created_at' => Carbon::now()->subMonths(2)
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 150.00,
            'created_at' => Carbon::now()->subMonth()
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 200.00,
            'created_at' => Carbon::now()
        ]);

        $patterns = $this->service->getShippingPatterns($this->customer);
        $monthlyBreakdown = $patterns['monthly_breakdown'];

        $this->assertNotEmpty($monthlyBreakdown);
        $this->assertIsArray($monthlyBreakdown);
        
        // Should have entries for the months with packages
        $this->assertGreaterThanOrEqual(1, count($monthlyBreakdown));
        
        // Each entry should have required fields
        foreach ($monthlyBreakdown as $month) {
            $this->assertArrayHasKey('month', $month);
            $this->assertArrayHasKey('year', $month);
            $this->assertArrayHasKey('month_number', $month);
            $this->assertArrayHasKey('package_count', $month);
            $this->assertArrayHasKey('total_spent', $month);
        }
    }

    public function test_seasonal_patterns_categorizes_correctly()
    {
        // Create packages in different seasons
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
            'created_at' => Carbon::createFromDate(2024, 1, 15) // Winter
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 150.00,
            'created_at' => Carbon::createFromDate(2024, 4, 15) // Spring
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 200.00,
            'created_at' => Carbon::createFromDate(2024, 7, 15) // Summer
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 175.00,
            'created_at' => Carbon::createFromDate(2024, 10, 15) // Fall
        ]);

        $patterns = $this->service->getShippingPatterns($this->customer);
        $seasonalPatterns = $patterns['seasonal_patterns'];

        $this->assertArrayHasKey('Winter', $seasonalPatterns);
        $this->assertArrayHasKey('Spring', $seasonalPatterns);
        $this->assertArrayHasKey('Summer', $seasonalPatterns);
        $this->assertArrayHasKey('Fall', $seasonalPatterns);
        
        $this->assertEquals(1, $seasonalPatterns['Winter']['package_count']);
        $this->assertEquals(1, $seasonalPatterns['Spring']['package_count']);
        $this->assertEquals(1, $seasonalPatterns['Summer']['package_count']);
        $this->assertEquals(1, $seasonalPatterns['Fall']['package_count']);
    }

    public function test_average_days_between_shipments_calculates_correctly()
    {
        // Create packages with known date intervals
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => Carbon::now()->subDays(30)
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => Carbon::now()->subDays(20)
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => Carbon::now()->subDays(10)
        ]);

        $patterns = $this->service->getShippingPatterns($this->customer);
        
        // Should be 10 days average (30->20 = 10 days, 20->10 = 10 days)
        $this->assertEquals(10, $patterns['average_days_between_shipments']);
    }

    public function test_financial_summary_handles_zero_values()
    {
        // Create package with zero costs
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 0,
            'customs_duty' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0
        ]);

        $financial = $this->service->getFinancialSummary($this->customer);

        $this->assertEquals(0, $financial['total_spent']);
        $this->assertEquals(0, $financial['average_per_package']);
        $this->assertEquals(0, $financial['cost_breakdown']['freight']);
        $this->assertEquals(0, $financial['cost_percentages']['freight']);
    }

    public function test_package_metrics_handles_empty_data()
    {
        $metrics = $this->service->getPackageMetrics($this->customer);

        $this->assertEquals(0, $metrics['total_count']);
        $this->assertEquals(0, $metrics['status_breakdown']['delivered']);
        $this->assertEquals(0, $metrics['weight_statistics']['total_weight']);
        $this->assertEquals(0, $metrics['delivery_rate']);
    }

    public function test_cache_status_returns_correct_information()
    {
        Cache::flush();
        
        // Initially no cache
        $status = $this->service->getCacheStatus($this->customer);
        $this->assertFalse($status['stats']['cached']);
        $this->assertFalse($status['financial']['cached']);
        $this->assertFalse($status['patterns']['cached']);
        $this->assertFalse($status['packages']['cached']);
        
        // After calling service methods, cache should exist
        $this->service->getCustomerStatistics($this->customer);
        
        $status = $this->service->getCacheStatus($this->customer);
        $this->assertTrue($status['stats']['cached']);
    }
}