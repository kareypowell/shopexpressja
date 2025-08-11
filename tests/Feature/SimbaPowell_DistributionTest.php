<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\PackageDistribution;
use App\Models\CustomerTransaction;
use App\Services\PackageDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimbaPowell_DistributionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $simba;
    protected $distributionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create(['role_id' => 1]);
        
        // Create Simba Powell as test customer
        $this->simba = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Simba',
            'last_name' => 'Powell',
            'email' => 'simba.powell@example.com',
            'account_balance' => 500.00,
            'credit_balance' => 0.00
        ]);
        
        $this->distributionService = app(PackageDistributionService::class);
    }

    /** @test */
    public function simba_powell_can_receive_single_package_distribution()
    {
        // Create a package for Simba
        $package = Package::factory()->create([
            'user_id' => $this->simba->id,
            'tracking_number' => 'SP001',
            'status' => 'ready',
            'freight_price' => 45.00,
            'customs_duty' => 15.50,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        $totalCost = 75.50;
        $initialBalance = $this->simba->account_balance;
        
        // Distribute package with no cash payment (charge to account)
        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            false, // Don't apply credit balance
            ['notes' => 'Distribution test for Simba Powell']
        );
        
        // Verify distribution was successful
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify distribution details
        $this->assertEquals($totalCost, $distribution->total_amount);
        $this->assertEquals(0.00, $distribution->amount_collected);
        $this->assertEquals('unpaid', $distribution->payment_status);
        $this->assertEquals($this->simba->id, $distribution->customer_id);
        $this->assertEquals($this->admin->id, $distribution->distributed_by);
        
        // Verify customer balance was charged
        $this->simba->refresh();
        $this->assertEquals($initialBalance - $totalCost, $this->simba->account_balance);
        
        // Verify package status updated
        $package->refresh();
        $this->assertEquals('delivered', $package->status);
        
        // Verify transaction was created
        $transaction = CustomerTransaction::where('user_id', $this->simba->id)
            ->where('reference_type', 'package_distribution')
            ->where('reference_id', $distribution->id)
            ->where('type', 'charge')
            ->first();
            
        $this->assertNotNull($transaction);
        $this->assertEquals($totalCost, $transaction->amount);
    }

    /** @test */
    public function simba_powell_can_pay_cash_for_distribution()
    {
        // Create a package for Simba
        $package = Package::factory()->create([
            'user_id' => $this->simba->id,
            'tracking_number' => 'SP002',
            'status' => 'ready',
            'freight_price' => 30.00,
            'customs_duty' => 12.00,
            'storage_fee' => 8.00,
            'delivery_fee' => 5.00
        ]);
        
        $totalCost = 55.00;
        $cashPayment = 55.00;
        $initialBalance = $this->simba->account_balance;
        
        // Distribute package with exact cash payment
        $result = $this->distributionService->distributePackages(
            [$package->id],
            $cashPayment,
            $this->admin,
            false,
            ['notes' => 'Cash payment test for Simba Powell']
        );
        
        // Verify distribution was successful
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify payment details
        $this->assertEquals($totalCost, $distribution->total_amount);
        $this->assertEquals($cashPayment, $distribution->amount_collected);
        $this->assertEquals('paid', $distribution->payment_status);
        
        // Verify customer balance unchanged (paid with cash)
        $this->simba->refresh();
        $this->assertEquals($initialBalance, $this->simba->account_balance);
        
        // Verify payment transaction was created
        $paymentTransaction = CustomerTransaction::where('user_id', $this->simba->id)
            ->where('reference_type', 'package_distribution')
            ->where('reference_id', $distribution->id)
            ->where('type', 'payment')
            ->first();
            
        $this->assertNotNull($paymentTransaction);
        $this->assertEquals($cashPayment, $paymentTransaction->amount);
    }

    /** @test */
    public function simba_powell_can_use_credit_balance_for_distribution()
    {
        // Set Simba's credit balance
        $this->simba->credit_balance = 30.00;
        $this->simba->save();
        
        // Create a package for Simba
        $package = Package::factory()->create([
            'user_id' => $this->simba->id,
            'tracking_number' => 'SP003',
            'status' => 'ready',
            'freight_price' => 25.00,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 5.00
        ]);
        
        $totalCost = 45.00;
        $cashPayment = 0.00;
        $expectedCreditUsed = 30.00; // All available credit is used
        $expectedAccountCharge = $totalCost - $expectedCreditUsed; // 15.00
        $initialBalance = $this->simba->account_balance;
        
        // Distribute package with credit balance application
        $result = $this->distributionService->distributePackages(
            [$package->id],
            $cashPayment,
            $this->admin,
            true, // Apply credit balance
            ['notes' => 'Credit balance test for Simba Powell']
        );
        
        // Verify distribution was successful
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify credit was applied
        $this->assertEquals($totalCost, $distribution->total_amount);
        $this->assertEquals($cashPayment, $distribution->amount_collected);
        $this->assertEquals($expectedCreditUsed, $distribution->credit_applied);
        $this->assertEquals('paid', $distribution->payment_status); // Paid via credit + account balance
        
        // Verify customer balances
        $this->simba->refresh();
        $this->assertEquals(0.00, $this->simba->credit_balance); // All credit used
        $this->assertEquals($initialBalance - $expectedAccountCharge, $this->simba->account_balance); // Account charged for remainder
    }

    /** @test */
    public function simba_powell_can_receive_multiple_packages_distribution()
    {
        // Create multiple packages for Simba
        $package1 = Package::factory()->create([
            'user_id' => $this->simba->id,
            'tracking_number' => 'SP004A',
            'status' => 'ready',
            'freight_price' => 20.00,
            'customs_duty' => 8.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 2.00
        ]);
        
        $package2 = Package::factory()->create([
            'user_id' => $this->simba->id,
            'tracking_number' => 'SP004B',
            'status' => 'ready',
            'freight_price' => 35.00,
            'customs_duty' => 12.00,
            'storage_fee' => 8.00,
            'delivery_fee' => 5.00
        ]);
        
        $package1Total = 35.00;
        $package2Total = 60.00;
        $totalCost = $package1Total + $package2Total; // 95.00
        $initialBalance = $this->simba->account_balance;
        
        // Distribute both packages together
        $result = $this->distributionService->distributePackages(
            [$package1->id, $package2->id],
            0.00, // No cash payment
            $this->admin,
            false,
            ['notes' => 'Multiple packages test for Simba Powell']
        );
        
        // Verify distribution was successful
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify total calculation
        $this->assertEquals($totalCost, $distribution->total_amount);
        $this->assertEquals(2, $distribution->items()->count());
        
        // Verify customer balance was charged
        $this->simba->refresh();
        $this->assertEquals($initialBalance - $totalCost, $this->simba->account_balance);
        
        // Verify both packages were marked as delivered
        $package1->refresh();
        $package2->refresh();
        $this->assertEquals('delivered', $package1->status);
        $this->assertEquals('delivered', $package2->status);
    }

    /** @test */
    public function simba_powell_can_receive_distribution_with_write_off()
    {
        // Create a package for Simba
        $package = Package::factory()->create([
            'user_id' => $this->simba->id,
            'tracking_number' => 'SP005',
            'status' => 'ready',
            'freight_price' => 50.00,
            'customs_duty' => 20.00,
            'storage_fee' => 15.00,
            'delivery_fee' => 10.00
        ]);
        
        $originalTotal = 95.00;
        $writeOffAmount = 20.00;
        $expectedChargeAmount = $originalTotal - $writeOffAmount; // 75.00
        $initialBalance = $this->simba->account_balance;
        
        // Distribute package with write-off
        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            false,
            [
                'writeOff' => $writeOffAmount,
                'writeOffReason' => 'Loyalty discount for Simba Powell',
                'notes' => 'Write-off test for Simba Powell'
            ]
        );
        
        // Verify distribution was successful
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify write-off was applied
        $this->assertEquals($originalTotal, $distribution->total_amount);
        $this->assertEquals($writeOffAmount, $distribution->write_off_amount);
        
        // Verify customer balance was charged the reduced amount
        $this->simba->refresh();
        $this->assertEquals($initialBalance - $expectedChargeAmount, $this->simba->account_balance);
        
        // Verify write-off transaction was created
        $writeOffTransaction = CustomerTransaction::where('user_id', $this->simba->id)
            ->where('reference_type', 'package_distribution')
            ->where('reference_id', $distribution->id)
            ->where('type', 'write_off')
            ->first();
            
        $this->assertNotNull($writeOffTransaction);
        $this->assertEquals($writeOffAmount, $writeOffTransaction->amount);
    }

    /** @test */
    public function simba_powell_distribution_maintains_proper_audit_trail()
    {
        // Create a package for Simba
        $package = Package::factory()->create([
            'user_id' => $this->simba->id,
            'tracking_number' => 'SP006',
            'status' => 'ready',
            'freight_price' => 40.00,
            'customs_duty' => 15.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        // Distribute package
        $result = $this->distributionService->distributePackages(
            [$package->id],
            30.00, // Partial cash payment
            $this->admin,
            false,
            ['notes' => 'Audit trail test for Simba Powell']
        );
        
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify audit fields
        $this->assertNotNull($distribution->receipt_number);
        $this->assertEquals($this->simba->id, $distribution->customer_id);
        $this->assertEquals($this->admin->id, $distribution->distributed_by);
        $this->assertNotNull($distribution->distributed_at);
        $this->assertNotNull($distribution->created_at);
        
        // Verify all transactions have proper audit trail
        $transactions = CustomerTransaction::where('user_id', $this->simba->id)
            ->where('reference_type', 'package_distribution')
            ->where('reference_id', $distribution->id)
            ->get();
            
        $this->assertGreaterThan(0, $transactions->count());
        
        foreach ($transactions as $transaction) {
            $this->assertEquals($this->admin->id, $transaction->created_by);
            $this->assertNotNull($transaction->created_at);
            $this->assertStringContainsString($distribution->receipt_number, $transaction->description);
        }
    }
}