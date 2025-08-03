<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\FinancialAnalytics;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardCacheService;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class FinancialAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['id' => 1, 'name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['id' => 2, 'name' => 'admin', 'description' => 'Administrator']);
        Role::create(['id' => 3, 'name' => 'customer', 'description' => 'Customer']);
    }

    /** @test */
    public function it_can_render_financial_analytics_component()
    {
        $this->createTestData();

        Livewire::test(FinancialAnalytics::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.financial-analytics');
    }

    /** @test */
    public function it_calculates_revenue_trend_data_correctly()
    {
        $this->createTestData();

        $component = Livewire::test(FinancialAnalytics::class);
        $revenueTrends = $component->instance()->getRevenueTrendData();

        $this->assertIsArray($revenueTrends);
        $this->assertNotEmpty($revenueTrends);
        
        foreach ($revenueTrends as $trend) {
            $this->assertArrayHasKey('period', $trend);
            $this->assertArrayHasKey('total_revenue', $trend);
            $this->assertArrayHasKey('freight_revenue', $trend);
            $this->assertArrayHasKey('customs_revenue', $trend);
            $this->assertArrayHasKey('storage_revenue', $trend);
            $this->assertArrayHasKey('delivery_revenue', $trend);
            $this->assertArrayHasKey('order_count', $trend);
            $this->assertArrayHasKey('avg_order_value', $trend);
        }
    }

    /** @test */
    public function it_calculates_revenue_by_service_type_correctly()
    {
        $this->createTestData();

        $component = Livewire::test(FinancialAnalytics::class);
        $revenueByService = $component->instance()->getRevenueByServiceType();

        $this->assertIsArray($revenueByService);
        $this->assertNotEmpty($revenueByService);
        
        foreach ($revenueByService as $service) {
            $this->assertArrayHasKey('service_type', $service);
            $this->assertArrayHasKey('total_revenue', $service);
            $this->assertArrayHasKey('order_count', $service);
            $this->assertArrayHasKey('avg_order_value', $service);
            $this->assertArrayHasKey('breakdown', $service);
            $this->assertArrayHasKey('percentage', $service);
        }
    }

    /** @test */
    public function it_calculates_revenue_by_customer_segment_correctly()
    {
        $this->createTestData();

        $component = Livewire::test(FinancialAnalytics::class);
        $revenueBySegment = $component->instance()->getRevenueByCustomerSegment();

        $this->assertIsArray($revenueBySegment);
        
        foreach ($revenueBySegment as $segment) {
            $this->assertArrayHasKey('segment', $segment);
            $this->assertArrayHasKey('total_revenue', $segment);
            $this->assertArrayHasKey('customer_count', $segment);
            $this->assertArrayHasKey('package_count', $segment);
            $this->assertArrayHasKey('avg_revenue_per_customer', $segment);
            $this->assertArrayHasKey('avg_order_value', $segment);
            $this->assertArrayHasKey('percentage', $segment);
        }
    }

    /** @test */
    public function it_calculates_financial_kpis_correctly()
    {
        $this->createTestData();

        $component = Livewire::test(FinancialAnalytics::class);
        $kpis = $component->instance()->getFinancialKPIs();

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('total_revenue', $kpis);
        $this->assertArrayHasKey('average_order_value', $kpis);
        $this->assertArrayHasKey('customer_lifetime_value', $kpis);
        $this->assertArrayHasKey('customer_metrics', $kpis);
        $this->assertArrayHasKey('order_metrics', $kpis);

        // Test total revenue structure
        $this->assertArrayHasKey('current', $kpis['total_revenue']);
        $this->assertArrayHasKey('previous', $kpis['total_revenue']);
        $this->assertArrayHasKey('growth_rate', $kpis['total_revenue']);

        // Test AOV structure
        $this->assertArrayHasKey('current', $kpis['average_order_value']);
        $this->assertArrayHasKey('previous', $kpis['average_order_value']);
        $this->assertArrayHasKey('growth_rate', $kpis['average_order_value']);

        // Test CLV structure
        $this->assertArrayHasKey('estimated_clv', $kpis['customer_lifetime_value']);
        $this->assertArrayHasKey('avg_lifespan_months', $kpis['customer_lifetime_value']);
        $this->assertArrayHasKey('avg_monthly_orders', $kpis['customer_lifetime_value']);
    }

    /** @test */
    public function it_calculates_profit_margin_analysis_correctly()
    {
        $this->createTestData();

        $component = Livewire::test(FinancialAnalytics::class);
        $profitMargins = $component->instance()->getProfitMarginAnalysis();

        $this->assertIsArray($profitMargins);
        
        foreach ($profitMargins as $margin) {
            $this->assertArrayHasKey('date', $margin);
            $this->assertArrayHasKey('gross_revenue', $margin);
            $this->assertArrayHasKey('operational_costs', $margin);
            $this->assertArrayHasKey('net_profit', $margin);
            $this->assertArrayHasKey('profit_margin', $margin);
            $this->assertArrayHasKey('total_revenue', $margin);
            $this->assertArrayHasKey('pass_through_costs', $margin);
            $this->assertArrayHasKey('order_count', $margin);
        }
    }

    /** @test */
    public function it_calculates_customer_lifetime_value_data_correctly()
    {
        $this->createTestData();

        $component = Livewire::test(FinancialAnalytics::class);
        $clvData = $component->instance()->getCustomerLifetimeValueData();

        $this->assertIsArray($clvData);
        
        foreach ($clvData as $customer) {
            $this->assertArrayHasKey('customer_id', $customer);
            $this->assertArrayHasKey('total_spent', $customer);
            $this->assertArrayHasKey('total_orders', $customer);
            $this->assertArrayHasKey('avg_order_value', $customer);
            $this->assertArrayHasKey('days_as_customer', $customer);
            $this->assertArrayHasKey('months_as_customer', $customer);
            $this->assertArrayHasKey('avg_monthly_spend', $customer);
            $this->assertArrayHasKey('estimated_clv', $customer);
            $this->assertArrayHasKey('order_frequency', $customer);
        }
    }

    /** @test */
    public function it_calculates_growth_rate_analysis_correctly()
    {
        $this->createTestData();

        $component = Livewire::test(FinancialAnalytics::class);
        $growthAnalysis = $component->instance()->getGrowthRateAnalysis();

        $this->assertIsArray($growthAnalysis);
        $this->assertArrayHasKey('current_period', $growthAnalysis);
        $this->assertArrayHasKey('previous_period', $growthAnalysis);
        $this->assertArrayHasKey('growth_rates', $growthAnalysis);
        $this->assertArrayHasKey('period_info', $growthAnalysis);

        // Test growth rates structure
        $this->assertArrayHasKey('revenue_growth', $growthAnalysis['growth_rates']);
        $this->assertArrayHasKey('order_growth', $growthAnalysis['growth_rates']);
        $this->assertArrayHasKey('customer_growth', $growthAnalysis['growth_rates']);
        $this->assertArrayHasKey('aov_growth', $growthAnalysis['growth_rates']);
    }

    /** @test */
    public function it_handles_filter_updates_correctly()
    {
        $this->createTestData();

        $component = Livewire::test(FinancialAnalytics::class)
            ->call('updateFilters', [
                'date_range' => '7',
                'custom_start' => '',
                'custom_end' => ''
            ]);

        $this->assertEquals('7', $component->get('dateRange'));
        $this->assertEquals('', $component->get('customStartDate'));
        $this->assertEquals('', $component->get('customEndDate'));
    }

    /** @test */
    public function it_handles_custom_date_range_correctly()
    {
        $this->createTestData();

        $startDate = Carbon::now()->subDays(14)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $component = Livewire::test(FinancialAnalytics::class)
            ->call('updateFilters', [
                'date_range' => 'custom',
                'custom_start' => $startDate,
                'custom_end' => $endDate
            ]);

        $this->assertEquals('custom', $component->get('dateRange'));
        $this->assertEquals($startDate, $component->get('customStartDate'));
        $this->assertEquals($endDate, $component->get('customEndDate'));
    }

    /** @test */
    public function it_calculates_growth_rate_correctly()
    {
        $component = new FinancialAnalytics();
        
        // Test positive growth
        $growthRate = $component->calculateGrowthRate(120, 100);
        $this->assertEquals(20.0, $growthRate);
        
        // Test negative growth
        $growthRate = $component->calculateGrowthRate(80, 100);
        $this->assertEquals(-20.0, $growthRate);
        
        // Test zero previous value
        $growthRate = $component->calculateGrowthRate(100, 0);
        $this->assertEquals(100, $growthRate);
        
        // Test zero current value
        $growthRate = $component->calculateGrowthRate(0, 100);
        $this->assertEquals(-100.0, $growthRate);
    }

    /** @test */
    public function it_refreshes_data_correctly()
    {
        $this->createTestData();

        Livewire::test(FinancialAnalytics::class)
            ->call('refreshData')
            ->assertEmitted('dataRefreshed');
    }

    protected function createTestData()
    {
        // Create test users
        $users = User::factory()->count(5)->create([
            'role_id' => 3, // customer role
            'created_at' => Carbon::now()->subDays(30)
        ]);

        // Create test manifests
        $seaManifest = Manifest::create([
            'name' => 'SEA001',
            'type' => 'sea',
            'is_open' => true,
            'shipment_date' => Carbon::now()->subDays(20),
            'created_at' => Carbon::now()->subDays(20)
        ]);

        $airManifest = Manifest::create([
            'name' => 'AIR001',
            'type' => 'air',
            'is_open' => true,
            'shipment_date' => Carbon::now()->subDays(15),
            'created_at' => Carbon::now()->subDays(15)
        ]);

        // Create test packages with varying revenue amounts
        foreach ($users as $index => $user) {
            // Create packages for current period
            Package::factory()->count(2)->create([
                'user_id' => $user->id,
                'manifest_id' => $seaManifest->id,
                'freight_price' => 100 + ($index * 50),
                'customs_duty' => 20 + ($index * 10),
                'storage_fee' => 15 + ($index * 5),
                'delivery_fee' => 25 + ($index * 5),
                'status' => 'delivered',
                'created_at' => Carbon::now()->subDays(rand(1, 15))
            ]);

            // Create packages for previous period (for comparison)
            Package::factory()->count(1)->create([
                'user_id' => $user->id,
                'manifest_id' => $airManifest->id,
                'freight_price' => 80 + ($index * 30),
                'customs_duty' => 15 + ($index * 8),
                'storage_fee' => 10 + ($index * 3),
                'delivery_fee' => 20 + ($index * 3),
                'status' => 'delivered',
                'created_at' => Carbon::now()->subDays(rand(45, 60))
            ]);
        }
    }
}