<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BalanceCalculationScenariosTest extends TestCase
{
    use RefreshDatabase;

    private $distributionService;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->distributionService = app(PackageDistributionService::class);
        $this->admin = User::factory()->create(['role_id' => 1]);
    }

    /** @test */
    public function scenario_1_customer_pays_exact_amount_with_cash()
    {
        // Customer with 875 balance, package costs 100, pays 100 cash
        $customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 875.00,
            'credit_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        $result = $this->distributionService->distributePackages(
            [$package->id],
            100.00, // Exact amount
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Account balance should remain unchanged
        $this->assertEquals(875.00, $customer->account_balance);
        $this->assertEquals(0.00, $customer->credit_balance);
    }

    /** @test */
    public function scenario_2_customer_overpays_with_cash()
    {
        // Customer with 875 balance, package costs 100, pays 150 cash
        $customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 875.00,
            'credit_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        $result = $this->distributionService->distributePackages(
            [$package->id],
            150.00, // Overpayment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Account balance should remain unchanged, overpayment goes to credit
        $this->assertEquals(875.00, $customer->account_balance);
        $this->assertEquals(50.00, $customer->credit_balance);
    }

    /** @test */
    public function scenario_3_customer_underpays_with_cash()
    {
        // Customer with 875 balance, package costs 100, pays 50 cash
        $customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 875.00,
            'credit_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        $result = $this->distributionService->distributePackages(
            [$package->id],
            50.00, // Underpayment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Account balance should be reduced by unpaid amount
        $this->assertEquals(825.00, $customer->account_balance); // 875 - 50 unpaid
        $this->assertEquals(0.00, $customer->credit_balance);
    }

    /** @test */
    public function scenario_4_customer_uses_account_balance()
    {
        // Customer with 875 balance, package costs 100, uses account balance
        $customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 875.00,
            'credit_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            ['account' => true] // Use account balance only
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Account balance should be reduced by package cost
        $this->assertEquals(775.00, $customer->account_balance); // 875 - 100
        $this->assertEquals(0.00, $customer->credit_balance);
    }

    /** @test */
    public function scenario_5_customer_uses_partial_account_balance_and_cash()
    {
        // Customer with 50 balance, package costs 100, pays 60 cash, uses account balance
        $customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 50.00,
            'credit_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        $result = $this->distributionService->distributePackages(
            [$package->id],
            60.00, // Cash payment
            $this->admin,
            ['account' => true] // Use account balance
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Account balance should be reduced by only what's needed (40)
        // Customer pays 60 cash + 40 account balance = 100 total (exact amount)
        // Remaining account balance: 50 - 40 = 10
        $this->assertEquals(10.00, $customer->account_balance);
        $this->assertEquals(0.00, $customer->credit_balance);
    }

    /** @test */
    public function scenario_6_simba_powell_original_issue()
    {
        // Simba Powell: 875 balance, 7942 package cost, pays 8000 cash
        $customer = User::factory()->create([
            'first_name' => 'Simba',
            'last_name' => 'Powell',
            'role_id' => 3,
            'account_balance' => 875.00,
            'credit_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 5000.00,
            'customs_duty' => 1500.00,
            'storage_fee' => 200.00,
            'delivery_fee' => 1242.00, // Total: 7942
        ]);

        $result = $this->distributionService->distributePackages(
            [$package->id],
            8000.00, // Cash payment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Account balance should remain 875 (unchanged)
        // Credit balance should be 58 (8000 - 7942 overpayment)
        $this->assertEquals(875.00, $customer->account_balance, 'Account balance should remain unchanged');
        $this->assertEquals(58.00, $customer->credit_balance, 'Credit should be the overpayment amount');

        // Verify transaction history - should have complete audit trail
        $transactions = $customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(3, $transactions);

        $chargeTransaction = $transactions->where('type', 'charge')->first();
        $this->assertEquals(7942.00, $chargeTransaction->amount);
        $this->assertEquals(875.00, $chargeTransaction->balance_before);
        $this->assertEquals(-7067.00, $chargeTransaction->balance_after);

        $paymentTransaction = $transactions->where('type', 'payment')->first();
        $this->assertEquals(7942.00, $paymentTransaction->amount);
        $this->assertEquals(-7067.00, $paymentTransaction->balance_before);
        $this->assertEquals(875.00, $paymentTransaction->balance_after);

        $creditTransaction = $transactions->where('type', 'credit')->first();
        $this->assertEquals(58.00, $creditTransaction->amount);
    }
}