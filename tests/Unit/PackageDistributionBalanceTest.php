<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PackageDistributionService;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageDistributionBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected $distributionService;
    protected $user;
    protected $customer;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->distributionService = app(PackageDistributionService::class);
        
        // Create test user (admin)
        $adminRole = Role::where('name', 'admin')->first();
        $this->user = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create test customer with initial balances
        $customerRole = Role::where('name', 'customer')->first();
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 100.00,
            'credit_balance' => 25.00,
        ]);
        
        // Create test package ready for distribution
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();
        
        $this->package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => PackageStatus::READY,
            'freight_price' => 30.00,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 5.00,
            // Total cost: $50.00
        ]);
    }

    /** @test */
    public function it_properly_charges_customer_account_and_records_payment()
    {
        $initialAccountBalance = $this->customer->account_balance; // $100.00
        $initialCreditBalance = $this->customer->credit_balance;   // $25.00
        $packageCost = 50.00;
        $amountCollected = 50.00; // Exact payment
        
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            $amountCollected,
            $this->user,
            false // Don't apply credit
        );
        
        $this->assertTrue($result['success']);
        
        // Check customer balances after distribution
        $this->customer->refresh();
        
        // Account balance should be: initial - package_cost + payment = 100 - 50 + 50 = 100
        $this->assertEquals(100.00, $this->customer->account_balance);
        
        // Credit balance should remain unchanged
        $this->assertEquals($initialCreditBalance, $this->customer->credit_balance);
        
        // Check transactions were created
        $transactions = $this->customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(2, $transactions);
        
        // First transaction: charge for package
        $chargeTransaction = $transactions->first();
        $this->assertEquals('charge', $chargeTransaction->type);
        $this->assertEquals(50.00, $chargeTransaction->amount);
        
        // Second transaction: payment received
        $paymentTransaction = $transactions->last();
        $this->assertEquals('payment', $paymentTransaction->type);
        $this->assertEquals(50.00, $paymentTransaction->amount);
    }

    /** @test */
    public function it_applies_credit_balance_when_requested()
    {
        $packageCost = 50.00;
        $amountCollected = 30.00; // Underpayment, will use credit to cover
        
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            $amountCollected,
            $this->user,
            true // Apply credit balance
        );
        
        $this->assertTrue($result['success']);
        

        
        // Check customer balances after distribution
        $this->customer->refresh();
        

        // Actual transaction flow:
        // 1. Credit applied: 25 (credit balance: 25 -> 0)
        // 2. Net charge: 50 - 25 = 25 (account balance: 100 -> 75)
        // 3. Payment received: 30 (account balance: 75 -> 105)
        // 4. Overpayment: 30 - 25 = 5 converted to credit (credit balance: 0 -> 5)
        // 5. Account balance reduced by overpayment: 105 -> 100
        $this->assertEquals(100.00, $this->customer->account_balance);
        
        // Credit balance: 0 + 5 overpayment = 5
        $this->assertEquals(5.00, $this->customer->credit_balance);
        
        // Check distribution record
        $distribution = $result['distribution'];
        $this->assertEquals(25.00, $distribution->credit_applied);
        $this->assertEquals('paid', $distribution->payment_status);
    }

    /** @test */
    public function it_handles_overpayment_correctly_with_proper_balance_updates()
    {
        $packageCost = 50.00;
        $amountCollected = 75.00; // $25 overpayment
        
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            $amountCollected,
            $this->user,
            false
        );
        
        $this->assertTrue($result['success']);
        
        // Check customer balances after distribution
        $this->customer->refresh();
        
        // Transaction flow:
        // 1. Charge: 50 (account balance: 100 - 50 = 50)
        // 2. Payment: 75 (account balance: 50 + 75 = 125)
        // 3. Overpayment: 25 converted to credit (credit balance: 25 + 25 = 50)
        // 4. Account balance reduced by overpayment: 125 - 25 = 100
        $this->assertEquals(100.00, $this->customer->account_balance);
        
        // Credit balance: 25 + 25 (overpayment) = 50
        $this->assertEquals(50.00, $this->customer->credit_balance);
        
        // Check transactions
        $transactions = $this->customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(4, $transactions);
        
        // Should have: charge, payment, overpayment credit, overpayment transfer
        $transactionTypes = $transactions->pluck('type')->toArray();
        $this->assertContains('charge', $transactionTypes);
        $this->assertContains('payment', $transactionTypes);
        $this->assertContains('credit', $transactionTypes);
        // The overpayment transfer creates another charge transaction
    }

    /** @test */
    public function it_handles_customer_with_negative_balance()
    {
        // Set customer to have negative balance (owes money)
        $this->customer->update(['account_balance' => -30.00]);
        
        $packageCost = 50.00;
        $amountCollected = 80.00; // Enough to cover debt + package + overpayment
        
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            $amountCollected,
            $this->user,
            false
        );
        
        $this->assertTrue($result['success']);
        
        // Check customer balances after distribution
        $this->customer->refresh();
        
        // Transaction flow:
        // 1. Charge: 50 (account balance: -30 - 50 = -80)
        // 2. Payment: 80 (account balance: -80 + 80 = 0)
        // 3. Overpayment: 80 - 50 = 30 converted to credit (credit balance: 25 + 30 = 55)
        // 4. Account balance reduced by overpayment: 0 - 30 = -30
        $this->assertEquals(-30.00, $this->customer->account_balance);
        
        // Credit balance: 25 + 30 (overpayment) = 55
        $this->assertEquals(55.00, $this->customer->credit_balance);
    }

    /** @test */
    public function it_handles_partial_payment_correctly()
    {
        $packageCost = 50.00;
        $amountCollected = 30.00; // Partial payment
        
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            $amountCollected,
            $this->user,
            false
        );
        
        $this->assertTrue($result['success']);
        
        // Check customer balances after distribution
        $this->customer->refresh();
        
        // Account balance: 100 - 50 + 30 = 80
        $this->assertEquals(80.00, $this->customer->account_balance);
        
        // Credit balance unchanged
        $this->assertEquals(25.00, $this->customer->credit_balance);
        
        // Check distribution shows partial payment
        $distribution = $result['distribution'];
        $this->assertEquals('partial', $distribution->payment_status);
        $this->assertEquals(20.00, $distribution->outstanding_balance);
    }
}