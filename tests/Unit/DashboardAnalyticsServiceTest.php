<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardCacheService;
use App\Models\User;
use App\Models\Package;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardAnalyticsService $analyticsService;
    protected DashboardCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(DashboardCacheService::class);
        $this->analyticsService = app(DashboardAnalyticsService::class);
    }

    /** @test */
    public function it_can_get_customer_metrics()
    {
        // Create test customers
        $customerRole = Role::where('name', 'customer')->first();
        User::factory()->count(5)->create([
            'role_id' => $customerRole->id,
            'created_at' => now()->subDays(15),
            'email_verified_at' => now(),
        ]);
        
        User::factory()->count(3)->create([
            'role_id' => 3, // Customer role
            'created_at' => now()->subDays(5),
            'email_verified_at' => now(),
        ]);

        // Create some admin users (should be excluded)
        User::factory()->count(2)->create([
            'role_id' => 1, // Admin role
            'created_at' => now()->subDays(10),
            'email_verified_at' => now(),
        ]);

        $filters = ['date_range' => 30];
        $metrics = $this->analyticsService->getCustomerMetrics($filters);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total', $metrics);
        $this->assertArrayHasKey('active', $metrics);
        $this->assertArrayHasKey('new_this_period', $metrics);
        $this->assertArrayHasKey('growth_percentage', $metrics);
        
        $this->assertEquals(8, $metrics['total']);
        $this->assertEquals(8, $metrics['active']);
        $this->assertEquals(8, $metrics['new_this_period']);
    }

    /** @test */
    public function it_can_get_shipment_metrics()
    {
        // Create test packages with different statuses
        Package::factory()->count(3)->create([
            'status' => 'pending',
            'created_at' => now()->subDays(10),
        ]);
        
        Package::factory()->count(2)->create([
            'status' => 'shipped',
            'created_at' => now()->subDays(5),
        ]);

        $filters = ['date_range' => 30];
        $metrics = $this->analyticsService->getShipmentMetrics($filters);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total', $metrics);
        $this->assertArrayHasKey('shipped', $metrics);
        $this->assertArrayHasKey('pending', $metrics);
        $this->assertArrayHasKey('processing_time_avg', $metrics);
        
        $this->assertEquals(5, $metrics['total']);
        $this->assertEquals(2, $metrics['shipped']);
        $this->assertEquals(3, $metrics['pending']);
    }

    /** @test */
    public function it_can_get_financial_metrics()
    {
        // Create test customer and admin
        $customer = User::factory()->create(['role_id' => 3]);
        $admin = User::factory()->create(['role_id' => 1]);
        
        // Create customer transactions (charges) - this is what the service actually looks for
        \App\Models\CustomerTransaction::create([
            'user_id' => $customer->id,
            'type' => \App\Models\CustomerTransaction::TYPE_CHARGE,
            'amount' => 145.00,
            'balance_before' => 0.00,
            'balance_after' => -145.00,
            'description' => 'Package distribution charge',
            'reference_type' => 'package_distribution',
            'reference_id' => 1,
            'created_by' => $admin->id,
            'created_at' => now()->subDays(10),
        ]);
        
        \App\Models\CustomerTransaction::create([
            'user_id' => $customer->id,
            'type' => \App\Models\CustomerTransaction::TYPE_CHARGE,
            'amount' => 145.00,
            'balance_before' => -145.00,
            'balance_after' => -290.00,
            'description' => 'Package distribution charge',
            'reference_type' => 'package_distribution',
            'reference_id' => 2,
            'created_by' => $admin->id,
            'created_at' => now()->subDays(5),
        ]);

        $filters = ['date_range' => 30];
        $metrics = $this->analyticsService->getFinancialMetrics($filters);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('current_period', $metrics);
        $this->assertArrayHasKey('previous_period', $metrics);
        $this->assertArrayHasKey('growth_percentage', $metrics);
        $this->assertArrayHasKey('average_order_value', $metrics);
        $this->assertArrayHasKey('total_orders', $metrics);
        
        $this->assertEquals(290.0, $metrics['current_period']); // 145 + 145
        $this->assertEquals(2, $metrics['total_orders']); // 2 package distributions
        $this->assertEquals(145.0, $metrics['average_order_value']); // 290 / 2
    }

    /** @test */
    public function it_can_get_customer_growth_data()
    {
        // Create users on different dates
        User::factory()->create(['created_at' => now()->subDays(5)->startOfDay()]);
        User::factory()->count(2)->create(['created_at' => now()->subDays(3)->startOfDay()]);

        $filters = ['date_range' => 30];
        $growthData = $this->analyticsService->getCustomerGrowthData($filters);

        $this->assertIsArray($growthData);
        $this->assertCount(2, $growthData); // 2 different dates
        
        foreach ($growthData as $dataPoint) {
            $this->assertArrayHasKey('date', $dataPoint);
            $this->assertArrayHasKey('count', $dataPoint);
        }
    }

    /** @test */
    public function it_can_get_revenue_analytics()
    {
        // Create packages on different dates
        Package::factory()->create([
            'freight_price' => 50.00,
            'customs_duty' => 10.00,
            'created_at' => now()->subDays(5)->startOfDay(),
        ]);
        
        Package::factory()->count(2)->create([
            'freight_price' => 75.00,
            'storage_fee' => 5.00,
            'created_at' => now()->subDays(3)->startOfDay(),
        ]);

        $filters = ['date_range' => 30];
        $revenueData = $this->analyticsService->getRevenueAnalytics($filters);

        $this->assertIsArray($revenueData);
        $this->assertCount(2, $revenueData); // 2 different dates
        
        foreach ($revenueData as $dataPoint) {
            $this->assertArrayHasKey('date', $dataPoint);
            $this->assertArrayHasKey('revenue', $dataPoint);
            $this->assertArrayHasKey('orders', $dataPoint);
        }
    }

    /** @test */
    public function it_generates_correct_cache_keys()
    {
        $filters = ['date_range' => 30];
        $cacheKey = $this->analyticsService->cacheKey('customer_metrics', $filters);
        
        $this->assertStringStartsWith('dashboard.customer_metrics.', $cacheKey);
        $this->assertEquals(32, strlen(explode('.', $cacheKey)[2])); // MD5 hash length
    }

    /** @test */
    public function it_handles_custom_date_ranges()
    {
        $filters = [
            'custom_start' => '2024-01-01',
            'custom_end' => '2024-01-31',
        ];

        // This should not throw an exception
        $metrics = $this->analyticsService->getCustomerMetrics($filters);
        $this->assertIsArray($metrics);
    }
}