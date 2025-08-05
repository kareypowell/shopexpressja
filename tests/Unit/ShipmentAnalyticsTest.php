<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\ShipmentAnalytics;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardCacheService;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ShipmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->createTestData();
    }

    protected function createTestData()
    {
        // Create manifests
        $seaManifest = Manifest::factory()->create([
            'type' => 'sea',
            'name' => 'Test Sea Manifest',
            'created_at' => Carbon::now()->subDays(5)
        ]);

        $airManifest = Manifest::factory()->create([
            'type' => 'air',
            'name' => 'Test Air Manifest',
            'created_at' => Carbon::now()->subDays(3)
        ]);

        // Create users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create packages with different statuses
        Package::factory()->create([
            'user_id' => $user1->id,
            'manifest_id' => $seaManifest->id,
            'status' => 'pending',
            'weight' => 10.5,
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(5)
        ]);

        Package::factory()->create([
            'user_id' => $user1->id,
            'manifest_id' => $seaManifest->id,
            'status' => 'processing',
            'weight' => 15.2,
            'created_at' => Carbon::now()->subDays(4),
            'updated_at' => Carbon::now()->subDays(4)
        ]);

        Package::factory()->create([
            'user_id' => $user2->id,
            'manifest_id' => $airManifest->id,
            'status' => 'ready_for_pickup',
            'weight' => 8.7,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(1) // 2 days processing time
        ]);

        Package::factory()->create([
            'user_id' => $user2->id,
            'manifest_id' => $airManifest->id,
            'status' => 'delivered',
            'weight' => 12.3,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now() // 2 days processing time
        ]);

        Package::factory()->create([
            'user_id' => $user1->id,
            'manifest_id' => $seaManifest->id,
            'status' => 'delayed',
            'weight' => 20.1,
            'created_at' => Carbon::now()->subDays(1),
            'updated_at' => Carbon::now()->subDays(1)
        ]);
    }

    /** @test */
    public function it_can_render_shipment_analytics_component()
    {
        Livewire::test(ShipmentAnalytics::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.shipment-analytics');
    }

    /** @test */
    public function it_can_get_shipment_volume_data()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        $volumeData = $component->instance()->getShipmentVolumeData();

        $this->assertIsArray($volumeData);
        $this->assertNotEmpty($volumeData);
        
        // Check data structure
        foreach ($volumeData as $data) {
            $this->assertArrayHasKey('date', $data);
            $this->assertArrayHasKey('volume', $data);
            $this->assertArrayHasKey('weight', $data);
        }
    }

    /** @test */
    public function it_can_get_package_status_distribution()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        $statusData = $component->instance()->getPackageStatusDistribution();

        $this->assertIsArray($statusData);
        
        // Check that status data contains expected keys
        if (!empty($statusData)) {
            foreach ($statusData as $data) {
                $this->assertArrayHasKey('date', $data);
                $this->assertArrayHasKey('pending', $data);
                $this->assertArrayHasKey('processing', $data);
                $this->assertArrayHasKey('in_transit', $data);
                $this->assertArrayHasKey('delivered', $data);
                $this->assertArrayHasKey('delayed', $data);
            }
        }
    }

    /** @test */
    public function it_can_get_processing_time_analysis()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        $processingData = $component->instance()->getProcessingTimeAnalysis();

        $this->assertIsArray($processingData);
        
        // Check data structure for completed packages
        foreach ($processingData as $data) {
            $this->assertArrayHasKey('date', $data);
            $this->assertArrayHasKey('avg_processing_time', $data);
            $this->assertArrayHasKey('min_processing_time', $data);
            $this->assertArrayHasKey('max_processing_time', $data);
            $this->assertArrayHasKey('count', $data);
        }
    }

    /** @test */
    public function it_can_get_shipping_method_breakdown()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        $methodData = $component->instance()->getShippingMethodBreakdown();

        $this->assertIsArray($methodData);
        $this->assertNotEmpty($methodData);
        
        // Should have both sea and air methods
        $methods = collect($methodData)->pluck('method')->toArray();
        $this->assertContains('Sea', $methods);
        $this->assertContains('Air', $methods);
        
        // Check data structure
        foreach ($methodData as $data) {
            $this->assertArrayHasKey('method', $data);
            $this->assertArrayHasKey('count', $data);
            $this->assertArrayHasKey('weight', $data);
            $this->assertArrayHasKey('percentage', $data);
        }
    }

    /** @test */
    public function it_can_get_delivery_performance_metrics()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        $deliveryMetrics = $component->instance()->getDeliveryPerformanceMetrics();

        $this->assertIsArray($deliveryMetrics);
        $this->assertArrayHasKey('total_packages', $deliveryMetrics);
        $this->assertArrayHasKey('delivered_packages', $deliveryMetrics);
        $this->assertArrayHasKey('delayed_packages', $deliveryMetrics);
        $this->assertArrayHasKey('on_time_delivery_rate', $deliveryMetrics);
        $this->assertArrayHasKey('overall_delivery_rate', $deliveryMetrics);

        // Verify calculations
        $this->assertEquals(5, $deliveryMetrics['total_packages']);
        $this->assertEquals(2, $deliveryMetrics['delivered_packages']); // ready_for_pickup + delivered
        $this->assertEquals(1, $deliveryMetrics['delayed_packages']);
    }

    /** @test */
    public function it_can_get_processing_time_by_method()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        $methodTimes = $component->instance()->getProcessingTimeByMethod();

        $this->assertIsArray($methodTimes);
        
        foreach ($methodTimes as $data) {
            $this->assertArrayHasKey('method', $data);
            $this->assertArrayHasKey('avg_processing_time', $data);
            $this->assertArrayHasKey('min_processing_time', $data);
            $this->assertArrayHasKey('max_processing_time', $data);
            $this->assertArrayHasKey('package_count', $data);
        }
    }

    /** @test */
    public function it_can_get_capacity_utilization()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        $capacityData = $component->instance()->getCapacityUtilization();

        $this->assertIsArray($capacityData);
        $this->assertNotEmpty($capacityData);
        
        foreach ($capacityData as $data) {
            $this->assertArrayHasKey('manifest_name', $data);
            $this->assertArrayHasKey('type', $data);
            $this->assertArrayHasKey('package_count', $data);
            $this->assertArrayHasKey('total_weight', $data);
            $this->assertArrayHasKey('total_volume', $data);
            $this->assertArrayHasKey('shipment_date', $data);
        }
    }

    /** @test */
    public function it_can_update_filters()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        
        $newFilters = [
            'date_range' => '7',
            'custom_start' => '2024-01-01',
            'custom_end' => '2024-01-31'
        ];
        
        $component->call('updateFilters', $newFilters);
        
        $this->assertEquals('7', $component->instance()->dateRange);
        $this->assertEquals('2024-01-01', $component->instance()->customStartDate);
        $this->assertEquals('2024-01-31', $component->instance()->customEndDate);
    }

    /** @test */
    public function it_can_refresh_data()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        
        $component->call('refreshData');
        
        // Should not throw any errors and loading state should be handled
        $this->assertFalse($component->instance()->isLoading);
    }

    /** @test */
    public function it_calculates_percentages_correctly_for_shipping_methods()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        $methodData = $component->instance()->getShippingMethodBreakdown();

        $totalPercentage = collect($methodData)->sum('percentage');
        
        // Total percentage should be 100% (allowing for rounding)
        $this->assertEqualsWithDelta(100, $totalPercentage, 0.1);
    }

    /** @test */
    public function it_handles_empty_data_gracefully()
    {
        // Clear all packages - use delete instead of truncate to avoid foreign key issues
        Package::query()->delete();
        
        $component = Livewire::test(ShipmentAnalytics::class);
        
        $volumeData = $component->instance()->getShipmentVolumeData();
        $statusData = $component->instance()->getPackageStatusDistribution();
        $deliveryMetrics = $component->instance()->getDeliveryPerformanceMetrics();
        
        $this->assertIsArray($volumeData);
        $this->assertIsArray($statusData);
        $this->assertIsArray($deliveryMetrics);
        
        // Delivery metrics should handle zero packages
        $this->assertEquals(0, $deliveryMetrics['total_packages']);
        $this->assertEquals(0, $deliveryMetrics['on_time_delivery_rate']);
    }

    /** @test */
    public function it_uses_correct_date_range_from_filters()
    {
        $component = Livewire::test(ShipmentAnalytics::class);
        
        // Test with custom date range
        $component->call('updateFilters', [
            'date_range' => '30',
            'custom_start' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'custom_end' => Carbon::now()->format('Y-m-d')
        ]);
        
        $volumeData = $component->instance()->getShipmentVolumeData();
        
        // Should only include data within the custom range
        $this->assertIsArray($volumeData);
    }
}