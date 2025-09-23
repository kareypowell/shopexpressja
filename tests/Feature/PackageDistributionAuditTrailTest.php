<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageDistributionAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private $distributionService;
    private $admin;
    private $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->distributionService = app(PackageDistributionService::class);
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->customer = User::factory()->create([
            'role_id' => 3,
            'account_balance' => 500.00,
            'credit_balance' => 0.00,
        ]);
    }

    /** @test */
    public function it_creates_complete_audit_trail_for_cash_payments()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Customer pays exact amount in cash
        $result = $this->distributionService->distributePackages(
            [$package->id],
            100.00, // Exact cash payment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $this->customer->refresh();

        // Account balance should remain unchanged
        $this->assertEquals(500.00, $this->customer->account_balance);

        // Should have complete audit trail
        $transactions = $this->customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(2, $transactions);

        // 1. Charge for package distribution
        $chargeTransaction = $transactions->where('type', 'charge')->first();
        $this->assertNotNull($chargeTransaction, 'Should have a charge transaction');
        $this->assertEquals(100.00, $chargeTransaction->amount);
        $this->assertStringStartsWith('Package distribution charge', $chargeTransaction->description);
        $this->assertEquals(500.00, $chargeTransaction->balance_before);
        $this->assertEquals(400.00, $chargeTransaction->balance_after);

        // 2. Payment to cover the charge
        $paymentTransaction = $transactions->where('type', 'payment')->first();
        $this->assertNotNull($paymentTransaction, 'Should have a payment transaction');
        $this->assertEquals(100.00, $paymentTransaction->amount);
        $this->assertStringStartsWith('Payment received for package distribution', $paymentTransaction->description);
        $this->assertEquals(400.00, $paymentTransaction->balance_before);
        $this->assertEquals(500.00, $paymentTransaction->balance_after);
    }

    /** @test */
    public function it_creates_audit_trail_for_overpayment_scenario()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Customer overpays
        $result = $this->distributionService->distributePackages(
            [$package->id],
            150.00, // Overpayment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $this->customer->refresh();

        // Account balance should remain unchanged, overpayment goes to credit
        $this->assertEquals(500.00, $this->customer->account_balance);
        $this->assertEquals(50.00, $this->customer->credit_balance);

        // Should have complete audit trail
        $transactions = $this->customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(3, $transactions);

        // 1. Charge for package distribution
        $chargeTransaction = $transactions->where('type', 'charge')->first();
        $this->assertEquals(100.00, $chargeTransaction->amount);

        // 2. Payment to cover the charge (only the service portion)
        $paymentTransaction = $transactions->where('type', 'payment')->first();
        $this->assertEquals(100.00, $paymentTransaction->amount);

        // 3. Credit for overpayment
        $creditTransaction = $transactions->where('type', 'credit')->first();
        $this->assertEquals(50.00, $creditTransaction->amount);
    }

    /** @test */
    public function it_creates_audit_trail_for_underpayment_scenario()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Customer underpays
        $result = $this->distributionService->distributePackages(
            [$package->id],
            60.00, // Underpayment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $this->customer->refresh();

        // Account balance calculation: 500 (initial) - 100 (charge) + 60 (payment) = 460
        $this->assertEquals(460.00, $this->customer->account_balance);

        // Should have complete audit trail
        $transactions = $this->customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(2, $transactions);

        // 1. Charge for package distribution
        $chargeTransaction = $transactions->where('type', 'charge')->first();
        $this->assertEquals(100.00, $chargeTransaction->amount);
        $this->assertEquals(500.00, $chargeTransaction->balance_before);
        $this->assertEquals(400.00, $chargeTransaction->balance_after);

        // 2. Payment (partial)
        $paymentTransaction = $transactions->where('type', 'payment')->first();
        $this->assertEquals(60.00, $paymentTransaction->amount);
        $this->assertEquals(400.00, $paymentTransaction->balance_before);
        $this->assertEquals(460.00, $paymentTransaction->balance_after);

        // The customer still owes 40 (100 - 60), but this is reflected in the account balance
        // The final balance of 460 is correct: initial 500 - 100 charge + 60 payment = 460
    }

    /** @test */
    public function it_shows_package_distribution_charges_in_recent_transactions()
    {
        $package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 75.00,
            'clearance_fee' => 25.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Distribute package
        $result = $this->distributionService->distributePackages(
            [$package->id],
            100.00, // Exact payment
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);

        // Get recent transactions
        $recentTransactions = $this->customer->getRecentTransactions(10);
        
        // Should include the package distribution charge
        $chargeTransaction = $recentTransactions->where('type', 'charge')->first();
        $this->assertNotNull($chargeTransaction, 'Recent transactions should include package distribution charge');
        $this->assertStringStartsWith('Package distribution charge', $chargeTransaction->description);
        $this->assertEquals(100.00, $chargeTransaction->amount);

        // Should also include the payment
        $paymentTransaction = $recentTransactions->where('type', 'payment')->first();
        $this->assertNotNull($paymentTransaction, 'Recent transactions should include payment');
        $this->assertStringStartsWith('Payment received for package distribution', $paymentTransaction->description);
        $this->assertEquals(100.00, $paymentTransaction->amount);
    }
}