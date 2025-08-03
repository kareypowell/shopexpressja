<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\DashboardMetrics;
use App\Services\DashboardAnalyticsService;
use Livewire\Livewire;
use Mockery;

class DashboardMetricsTest extends TestCase
{
    public function test_component_renders_successfully()
    {
        $mockService = Mockery::mock(DashboardAnalyticsService::class);
        $mockService->shouldReceive('getCustomerMetrics')->andReturn([
            'total' => 100,
            'active' => 80,
            'new_this_period' => 10,
            'growth_percentage' => 5.5,
            'inactive' => 20,
        ]);
        $mockService->shouldReceive('getShipmentMetrics')->andReturn([
            'total' => 50,
            'in_transit' => 10,
            'delivered' => 30,
            'delayed' => 5,
            'pending' => 5,
            'processing_time_avg' => 3.2,
            'status_distribution' => [],
        ]);
        $mockService->shouldReceive('getFinancialMetrics')->andReturn([
            'current_period' => 5000.00,
            'previous_period' => 4500.00,
            'growth_percentage' => 11.1,
            'average_order_value' => 100.00,
            'total_orders' => 50,
        ]);

        $this->app->instance(DashboardAnalyticsService::class, $mockService);

        Livewire::test(DashboardMetrics::class)
            ->assertViewIs('livewire.dashboard-metrics')
            ->assertSee('Dashboard Overview')
            ->assertSee('100') // Total customers
            ->assertSee('$5,000.00'); // Revenue
    }

    public function test_component_handles_loading_state()
    {
        $mockService = Mockery::mock(DashboardAnalyticsService::class);
        $mockService->shouldReceive('getCustomerMetrics')->andReturn([
            'total' => 0,
            'active' => 0,
            'new_this_period' => 0,
            'growth_percentage' => 0,
            'inactive' => 0,
        ]);
        $mockService->shouldReceive('getShipmentMetrics')->andReturn([
            'total' => 0,
            'in_transit' => 0,
            'delivered' => 0,
            'delayed' => 0,
            'pending' => 0,
            'processing_time_avg' => 0,
            'status_distribution' => [],
        ]);
        $mockService->shouldReceive('getFinancialMetrics')->andReturn([
            'current_period' => 0,
            'previous_period' => 0,
            'growth_percentage' => 0,
            'average_order_value' => 0,
            'total_orders' => 0,
        ]);

        $this->app->instance(DashboardAnalyticsService::class, $mockService);

        $component = Livewire::test(DashboardMetrics::class);
        
        // Initially loading should be false after mount
        $this->assertFalse($component->get('isLoading'));
    }

    public function test_format_percentage_method()
    {
        $mockService = Mockery::mock(DashboardAnalyticsService::class);
        $mockService->shouldReceive('getCustomerMetrics')->andReturn([
            'total' => 0, 'active' => 0, 'new_this_period' => 0, 'growth_percentage' => 0, 'inactive' => 0,
        ]);
        $mockService->shouldReceive('getShipmentMetrics')->andReturn([
            'total' => 0, 'in_transit' => 0, 'delivered' => 0, 'delayed' => 0, 'pending' => 0, 'processing_time_avg' => 0, 'status_distribution' => [],
        ]);
        $mockService->shouldReceive('getFinancialMetrics')->andReturn([
            'current_period' => 0, 'previous_period' => 0, 'growth_percentage' => 0, 'average_order_value' => 0, 'total_orders' => 0,
        ]);

        $this->app->instance(DashboardAnalyticsService::class, $mockService);

        $component = Livewire::test(DashboardMetrics::class);
        
        $this->assertEquals('+5.5%', $component->instance()->getFormattedPercentage(5.5));
        $this->assertEquals('-3.2%', $component->instance()->getFormattedPercentage(-3.2));
        $this->assertEquals('+0.0%', $component->instance()->getFormattedPercentage(0));
    }

    public function test_trend_direction_method()
    {
        $mockService = Mockery::mock(DashboardAnalyticsService::class);
        $mockService->shouldReceive('getCustomerMetrics')->andReturn([
            'total' => 0, 'active' => 0, 'new_this_period' => 0, 'growth_percentage' => 0, 'inactive' => 0,
        ]);
        $mockService->shouldReceive('getShipmentMetrics')->andReturn([
            'total' => 0, 'in_transit' => 0, 'delivered' => 0, 'delayed' => 0, 'pending' => 0, 'processing_time_avg' => 0, 'status_distribution' => [],
        ]);
        $mockService->shouldReceive('getFinancialMetrics')->andReturn([
            'current_period' => 0, 'previous_period' => 0, 'growth_percentage' => 0, 'average_order_value' => 0, 'total_orders' => 0,
        ]);

        $this->app->instance(DashboardAnalyticsService::class, $mockService);

        $component = Livewire::test(DashboardMetrics::class);
        
        $this->assertEquals('up', $component->instance()->getTrendDirection(5.5));
        $this->assertEquals('down', $component->instance()->getTrendDirection(-3.2));
        $this->assertEquals('neutral', $component->instance()->getTrendDirection(0));
    }

    public function test_format_currency_method()
    {
        $mockService = Mockery::mock(DashboardAnalyticsService::class);
        $mockService->shouldReceive('getCustomerMetrics')->andReturn([
            'total' => 0, 'active' => 0, 'new_this_period' => 0, 'growth_percentage' => 0, 'inactive' => 0,
        ]);
        $mockService->shouldReceive('getShipmentMetrics')->andReturn([
            'total' => 0, 'in_transit' => 0, 'delivered' => 0, 'delayed' => 0, 'pending' => 0, 'processing_time_avg' => 0, 'status_distribution' => [],
        ]);
        $mockService->shouldReceive('getFinancialMetrics')->andReturn([
            'current_period' => 0, 'previous_period' => 0, 'growth_percentage' => 0, 'average_order_value' => 0, 'total_orders' => 0,
        ]);

        $this->app->instance(DashboardAnalyticsService::class, $mockService);

        $component = Livewire::test(DashboardMetrics::class);
        
        $this->assertEquals('$1,234.56', $component->instance()->formatCurrency(1234.56));
        $this->assertEquals('$0.00', $component->instance()->formatCurrency(0));
    }

    public function test_format_number_method()
    {
        $mockService = Mockery::mock(DashboardAnalyticsService::class);
        $mockService->shouldReceive('getCustomerMetrics')->andReturn([
            'total' => 0, 'active' => 0, 'new_this_period' => 0, 'growth_percentage' => 0, 'inactive' => 0,
        ]);
        $mockService->shouldReceive('getShipmentMetrics')->andReturn([
            'total' => 0, 'in_transit' => 0, 'delivered' => 0, 'delayed' => 0, 'pending' => 0, 'processing_time_avg' => 0, 'status_distribution' => [],
        ]);
        $mockService->shouldReceive('getFinancialMetrics')->andReturn([
            'current_period' => 0, 'previous_period' => 0, 'growth_percentage' => 0, 'average_order_value' => 0, 'total_orders' => 0,
        ]);

        $this->app->instance(DashboardAnalyticsService::class, $mockService);

        $component = Livewire::test(DashboardMetrics::class);
        
        $this->assertEquals('1.2M', $component->instance()->formatNumber(1200000));
        $this->assertEquals('1.5K', $component->instance()->formatNumber(1500));
        $this->assertEquals('999', $component->instance()->formatNumber(999));
    }
}
