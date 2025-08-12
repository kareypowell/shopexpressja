<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Services\PackageDistributionService;
use App\Services\DashboardAnalyticsService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardRevenueCalculationTest extends TestCase
{
    use RefreshDatabase;

    private $distributionService;
    private $dashboardService;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->distributionService = app(PackageDistributionService::class);
        $this->dashboardService = app(DashboardAnalyticsService::class);
        $this->admin = User::factory()->create(['role_id' => 1]);
    }

    /** @test */
    public function it_calculates_revenue_correctly_for_exact_payment()
    {
        // Create customer and package
        $customer = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Revenue',
            'last_name' => 'Test',
            'account_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 50.00,
            'storage_fee' => 25.00,
            'delivery_fee' => 25.00, // Total: 200
        ]);

        // Customer pays exact amount
        $result = $this->distributionService->distributePackages(
            [$package->id],
            200.00, // Exact payment
            $this->admin,
            []
        );

        $this->assertTrue($result['success']);

        // Check dashboard metrics
        $metrics = $this->dashboardService->getFinancialMetrics(['date_range' => 1]);

        // Revenue should be the service charge amount, not charge + payment
        $this->assertEquals(200.00, $metrics['current_period'], 'Revenue should equal the service charge only');
        $this->assertEquals(200.00, $metrics['average_order_value'], 'Average order value should equal the service charge');
        $this->assertEquals(1, $metrics['total_orders'], 'Should count one order');

        // Verify transactions were created correctly
        $transactions = $customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(2, $transactions, 'Should have charge and payment transactions');

        $chargeTransaction = $transactions->where('type', 'charge')->first();
        $paymentTransaction = $transactions->where('type', 'payment')->first();

        $this->assertEquals(200.00, $chargeTransaction->amount, 'Charge should be for service amount');
        $this->assertEquals(200.00, $paymentTransaction->amount, 'Payment should cover the charge');
    }

    /** @test */
    public function it_calculates_revenue_correctly_for_overpayment()
    {
        // Simba Powell scenario
        $customer = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Simba',
            'last_name' => 'Powell',
            'account_balance' => 875.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 4000.00,
            'customs_duty' => 1500.00,
            'storage_fee' => 500.00,
            'delivery_fee' => 335.00, // Total: 6335
        ]);

        // Customer pays more than needed
        $result = $this->distributionService->distributePackages(
            [$package->id],
            7000.00, // Overpayment
            $this->admin,
            []
        );

        $this->assertTrue($result['success']);

        // Check dashboard metrics
        $metrics = $this->dashboardService->getFinancialMetrics(['date_range' => 1]);

        // Revenue should be only the service charge, not including overpayment
        $this->assertEquals(6335.00, $metrics['current_period'], 'Revenue should equal the service charge only, not including overpayment');
        $this->assertEquals(6335.00, $metrics['average_order_value'], 'Average order value should equal the service charge');
        $this->assertEquals(1, $metrics['total_orders'], 'Should count one order');

        // Verify customer balances
        $customer->refresh();
        $this->assertEquals(875.00, $customer->account_balance, 'Account balance should remain unchanged');
        $this->assertEquals(665.00, $customer->credit_balance, 'Credit balance should be the overpayment');

        // Verify transactions
        $transactions = $customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(3, $transactions, 'Should have charge, payment, and credit transactions');

        $chargeTransaction = $transactions->where('type', 'charge')->first();
        $paymentTransaction = $transactions->where('type', 'payment')->first();
        $creditTransaction = $transactions->where('type', 'credit')->first();

        $this->assertEquals(6335.00, $chargeTransaction->amount, 'Charge should be for service amount');
        $this->assertEquals(6335.00, $paymentTransaction->amount, 'Payment should cover the charge exactly');
        $this->assertEquals(665.00, $creditTransaction->amount, 'Credit should be the overpayment amount');
    }

    /** @test */
    public function it_calculates_revenue_correctly_for_multiple_customers()
    {
        // Create multiple customers with different scenarios
        $customer1 = User::factory()->create(['role_id' => 3]);
        $customer2 = User::factory()->create(['role_id' => 3]);

        $package1 = Package::factory()->create([
            'user_id' => $customer1->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        $package2 = Package::factory()->create([
            'user_id' => $customer2->id,
            'status' => PackageStatus::READY,
            'freight_price' => 150.00,
            'customs_duty' => 50.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Distribute packages
        $this->distributionService->distributePackages([$package1->id], 100.00, $this->admin, []);
        $this->distributionService->distributePackages([$package2->id], 250.00, $this->admin, []); // Overpayment

        // Check dashboard metrics
        $metrics = $this->dashboardService->getFinancialMetrics(['date_range' => 1]);

        // Revenue should be sum of service charges only
        $expectedRevenue = 100.00 + 200.00; // Service charges only
        $this->assertEquals($expectedRevenue, $metrics['current_period'], 'Revenue should be sum of service charges only');
        $this->assertEquals($expectedRevenue / 2, $metrics['average_order_value'], 'Average should be correct');
        $this->assertEquals(2, $metrics['total_orders'], 'Should count two orders');
    }

    /** @test */
    public function it_excludes_admin_transactions_from_revenue()
    {
        // Create admin user transaction (should be excluded)
        $adminUser = User::factory()->create([
            'role_id' => 1,
            'account_balance' => 1000.00
        ]);
        $adminUser->recordCharge(500.00, 'Admin test charge', $this->admin->id);

        // Create customer transaction (should be included)
        $customer = User::factory()->create(['role_id' => 3]);
        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        $this->distributionService->distributePackages([$package->id], 100.00, $this->admin, []);

        // Check dashboard metrics
        $metrics = $this->dashboardService->getFinancialMetrics(['date_range' => 1]);

        // Should only include customer revenue, not admin transactions
        $this->assertEquals(100.00, $metrics['current_period'], 'Should only include customer transactions');
        $this->assertEquals(1, $metrics['total_orders'], 'Should only count customer orders');
    }
}