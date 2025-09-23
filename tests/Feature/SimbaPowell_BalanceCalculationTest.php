<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\CustomerTransaction;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimbaPowell_BalanceCalculationTest extends TestCase
{
    use RefreshDatabase;

    private $customer;
    private $admin;
    private $distributionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->distributionService = app(PackageDistributionService::class);
        
        // Create admin user
        $this->admin = User::factory()->create(['role_id' => 1]);
        
        // Create Simba Powell with initial balance of 875
        $this->customer = User::factory()->create([
            'first_name' => 'Simba',
            'last_name' => 'Powell',
            'role_id' => 3,
            'account_balance' => 875.00,
            'credit_balance' => 0.00,
        ]);
    }

    /** @test */
    public function it_correctly_handles_simba_powell_balance_calculation()
    {
        // Create a package with total cost of 7,942
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 5000.00,
            'clearance_fee' => 1500.00,
            'storage_fee' => 200.00,
            'delivery_fee' => 1242.00, // Total: 7,942
        ]);

        // Verify initial state
        $this->assertEquals(875.00, $this->customer->account_balance);
        $this->assertEquals(0.00, $this->customer->credit_balance);

        // Customer pays 8,000 for a package costing 7,942
        $result = $this->distributionService->distributePackages(
            [$package->id],
            8000.00, // Amount collected
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);

        // Refresh customer to get updated balances
        $this->customer->refresh();

        // Expected behavior after fix:
        // 1. Customer had 875 balance initially
        // 2. Package cost 7,942
        // 3. Customer paid 8,000 cash
        // 4. Overpayment of 58 (8,000 - 7,942) should go to credit
        // 5. Account balance should remain 875 (unchanged since no account balance was used)

        $this->assertEquals(875.00, $this->customer->account_balance, 'Account balance should remain unchanged at 875');
        $this->assertEquals(58.00, $this->customer->credit_balance, 'Credit balance should be 58 (overpayment)');

        // Check transactions - should have 3 transactions now for complete audit trail
        $transactions = $this->customer->transactions()->orderBy('created_at')->get();
        
        // Should have:
        // 1. Charge for package distribution (7,942)
        // 2. Payment to cover the charge (7,942) 
        // 3. Credit for overpayment (58)
        
        $this->assertCount(3, $transactions);
        
        // Verify transaction details
        $chargeTransaction = $transactions->where('type', 'charge')->first();
        $this->assertEquals(7942.00, $chargeTransaction->amount, 'Charge should be for the package cost');
        $this->assertEquals(875.00, $chargeTransaction->balance_before, 'Balance before charge should be initial balance');
        $this->assertEquals(-7067.00, $chargeTransaction->balance_after, 'Balance after charge should be negative');
        
        $paymentTransaction = $transactions->where('type', 'payment')->first();
        $this->assertEquals(7942.00, $paymentTransaction->amount, 'Payment should cover the charge exactly');
        $this->assertEquals(-7067.00, $paymentTransaction->balance_before, 'Balance before payment should be negative');
        $this->assertEquals(875.00, $paymentTransaction->balance_after, 'Balance after payment should return to original');
        
        $creditTransaction = $transactions->where('type', 'credit')->first();
        $this->assertEquals(58.00, $creditTransaction->amount, 'Credit should be the overpayment amount');
    }

    /** @test */
    public function it_correctly_handles_balance_when_not_using_account_balance()
    {
        // Create a package with total cost of 7,942
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 5000.00,
            'clearance_fee' => 1500.00,
            'storage_fee' => 200.00,
            'delivery_fee' => 1242.00, // Total: 7,942
        ]);

        // Verify initial state
        $this->assertEquals(875.00, $this->customer->account_balance);

        // Customer pays exact amount (7,942) - no overpayment
        $result = $this->distributionService->distributePackages(
            [$package->id],
            7942.00, // Exact amount
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $this->customer->refresh();

        // When customer pays exact amount and doesn't use account balance:
        // Account balance should remain unchanged at 875
        $this->assertEquals(875.00, $this->customer->account_balance);
        $this->assertEquals(0.00, $this->customer->credit_balance);
    }
}