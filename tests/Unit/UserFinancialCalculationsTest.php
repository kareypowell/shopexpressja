<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class UserFinancialCalculationsTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create customer role
        $this->customerRole = Role::factory()->create([
            'name' => 'customer',
            'description' => 'Customer role'
        ]);
        
        // Create a test customer
        $this->customer = User::factory()->create([
            'role_id' => $this->customerRole->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com'
        ]);
    }

    public function test_get_total_spending_by_category_calculates_correctly()
    {
        // Create packages with known costs
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

        $categorySpending = $this->customer->getTotalSpendingByCategory();

        // Test totals
        $this->assertEquals(300.00, $categorySpending['freight']['total']);
        $this->assertEquals(75.00, $categorySpending['customs']['total']);
        $this->assertEquals(30.00, $categorySpending['storage']['total']);
        $this->assertEquals(45.00, $categorySpending['delivery']['total']);
        $this->assertEquals(450.00, $categorySpending['grand_total']);
        $this->assertEquals(2, $categorySpending['package_count']);

        // Test percentages
        $this->assertEquals(66.7, $categorySpending['freight']['percentage']);
        $this->assertEquals(16.7, $categorySpending['customs']['percentage']);
        $this->assertEquals(6.7, $categorySpending['storage']['percentage']);
        $this->assertEquals(10.0, $categorySpending['delivery']['percentage']);

        // Test averages per package
        $this->assertEquals(150.00, $categorySpending['freight']['average_per_package']);
        $this->assertEquals(37.50, $categorySpending['customs']['average_per_package']);
        $this->assertEquals(15.00, $categorySpending['storage']['average_per_package']);
        $this->assertEquals(22.50, $categorySpending['delivery']['average_per_package']);
    }

    public function test_get_average_package_value_calculations()
    {
        // Create packages with known values
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
            'weight' => 10.0,
            'estimated_value' => 500.00
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 200.00,
            'customs_duty' => 50.00,
            'storage_fee' => 20.00,
            'delivery_fee' => 30.00,
            'weight' => 20.0,
            'estimated_value' => 1000.00
        ]);

        $averages = $this->customer->getAveragePackageValueCalculations();

        $this->assertEquals(225.00, $averages['total_cost']); // (150 + 300) / 2
        $this->assertEquals(150.00, $averages['by_category']['freight']); // (100 + 200) / 2
        $this->assertEquals(37.50, $averages['by_category']['customs']); // (25 + 50) / 2
        $this->assertEquals(15.00, $averages['by_category']['storage']); // (10 + 20) / 2
        $this->assertEquals(22.50, $averages['by_category']['delivery']); // (15 + 30) / 2
        $this->assertEquals(15.0, $averages['average_weight']); // (10 + 20) / 2
        $this->assertEquals(750.00, $averages['average_estimated_value']); // (500 + 1000) / 2
        $this->assertEquals(15.0, $averages['cost_per_weight']); // 225 / 15
        $this->assertEquals(2, $averages['package_count']);
    }

    public function test_get_financial_trend_analysis()
    {
        // Create packages over different months with dramatically increasing costs
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 50.00,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 10.00,
            'created_at' => Carbon::now()->subMonths(3)
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 100.00,
            'customs_duty' => 20.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
            'created_at' => Carbon::now()->subMonths(2)
        ]);

        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 300.00,
            'customs_duty' => 60.00,
            'storage_fee' => 30.00,
            'delivery_fee' => 40.00,
            'created_at' => Carbon::now()->subMonth()
        ]);

        $trends = $this->customer->getFinancialTrendAnalysis(6);

        $this->assertIsArray($trends);
        $this->assertEquals(6, $trends['period_months']);
        $this->assertArrayHasKey('monthly_trends', $trends);
        $this->assertArrayHasKey('summary', $trends);
        $this->assertArrayHasKey('trend_analysis', $trends);

        // Test summary (all packages within the 6-month period)
        $this->assertEquals(650.00, $trends['summary']['total_spent']); // 75 + 145 + 430 = 650
        $this->assertEquals(3, $trends['summary']['total_packages']);
        
        // Test trend analysis structure
        $this->assertArrayHasKey('direction', $trends['trend_analysis']);
        $this->assertArrayHasKey('percentage_change', $trends['trend_analysis']);
        $this->assertArrayHasKey('description', $trends['trend_analysis']);
        
        // Should show increasing trend since costs go up dramatically
        $this->assertEquals('increasing', $trends['trend_analysis']['direction']);
        $this->assertGreaterThan(10, $trends['trend_analysis']['percentage_change']);
    }

    public function test_enhanced_financial_summary_includes_new_fields()
    {
        // Create packages with varying costs
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

        $summary = $this->customer->getFinancialSummary();

        // Test new fields are present
        $this->assertArrayHasKey('cost_percentages', $summary);
        $this->assertArrayHasKey('cost_range', $summary);

        // Test cost percentages
        $this->assertEquals(66.7, $summary['cost_percentages']['freight']);
        $this->assertEquals(16.7, $summary['cost_percentages']['customs']);
        $this->assertEquals(6.7, $summary['cost_percentages']['storage']);
        $this->assertEquals(10.0, $summary['cost_percentages']['delivery']);

        // Test cost range
        $this->assertEquals(300.00, $summary['cost_range']['highest_package']); // 200+50+20+30
        $this->assertEquals(150.00, $summary['cost_range']['lowest_package']); // 100+25+10+15

        // Test existing fields still work
        $this->assertEquals(450.00, $summary['total_spent']);
        $this->assertEquals(225.00, $summary['averages']['per_package']);
    }

    public function test_financial_calculations_handle_zero_values()
    {
        // Create package with zero costs
        Package::factory()->create([
            'user_id' => $this->customer->id,
            'freight_price' => 0,
            'customs_duty' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
            'weight' => 0,
            'estimated_value' => 0
        ]);

        $categorySpending = $this->customer->getTotalSpendingByCategory();
        $averages = $this->customer->getAveragePackageValueCalculations();
        $summary = $this->customer->getFinancialSummary();

        // Test category spending with zeros
        $this->assertEquals(0, $categorySpending['grand_total']);
        $this->assertEquals(0, $categorySpending['freight']['percentage']);

        // Test averages with zeros
        $this->assertEquals(0, $averages['total_cost']);
        $this->assertEquals(0, $averages['cost_per_weight']);

        // Test summary with zeros
        $this->assertEquals(0, $summary['total_spent']);
        $this->assertEquals(0, $summary['cost_percentages']['freight']);
    }

    public function test_financial_calculations_handle_empty_data()
    {
        // Test with customer who has no packages
        $categorySpending = $this->customer->getTotalSpendingByCategory();
        $averages = $this->customer->getAveragePackageValueCalculations();
        $trends = $this->customer->getFinancialTrendAnalysis();
        $summary = $this->customer->getFinancialSummary();

        // Test category spending with no data
        $this->assertEquals(0, $categorySpending['grand_total']);
        $this->assertEquals(0, $categorySpending['package_count']);

        // Test averages with no data
        $this->assertEquals(0, $averages['total_cost']);
        $this->assertEquals(0, $averages['package_count']);

        // Test trends with no data
        $this->assertEquals(0, $trends['summary']['total_spent']);
        $this->assertEmpty($trends['monthly_trends']);
        $this->assertEquals('stable', $trends['trend_analysis']['direction']);

        // Test summary with no data
        $this->assertEquals(0, $summary['total_spent']);
        $this->assertEquals(0, $summary['total_packages']);
    }

    public function test_trend_description_messages()
    {
        // Test increasing trend
        $reflection = new \ReflectionClass($this->customer);
        $method = $reflection->getMethod('getTrendDescription');
        $method->setAccessible(true);

        $increasingDesc = $method->invokeArgs($this->customer, ['increasing', 25.5]);
        $this->assertStringContainsString('increased by 25.5%', $increasingDesc);

        $decreasingDesc = $method->invokeArgs($this->customer, ['decreasing', -15.2]);
        $this->assertStringContainsString('decreased by 15.2%', $decreasingDesc);

        $stableDesc = $method->invokeArgs($this->customer, ['stable', 2.1]);
        $this->assertStringContainsString('remained relatively stable', $stableDesc);
    }

    public function test_financial_trend_analysis_with_different_periods()
    {
        // Create packages over 6 months
        for ($i = 1; $i <= 6; $i++) {
            Package::factory()->create([
                'user_id' => $this->customer->id,
                'freight_price' => 100.00 * $i,
                'customs_duty' => 25.00,
                'storage_fee' => 10.00,
                'delivery_fee' => 15.00,
                'created_at' => Carbon::now()->subMonths($i)
            ]);
        }

        // Test 3-month analysis
        $trends3 = $this->customer->getFinancialTrendAnalysis(3);
        $this->assertEquals(3, $trends3['period_months']);
        $this->assertLessThanOrEqual(3, count($trends3['monthly_trends']));

        // Test 12-month analysis
        $trends12 = $this->customer->getFinancialTrendAnalysis(12);
        $this->assertEquals(12, $trends12['period_months']);
        $this->assertLessThanOrEqual(6, count($trends12['monthly_trends'])); // Only 6 packages created
    }
}