<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\PackageDistribution;
use App\Models\CustomerTransaction;
use App\Services\PackageDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\PackageDistribution as PackageDistributionComponent;

class PackageDistributionCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $package;
    protected $distributionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create(['role_id' => 1]);
        
        // Create Simba Powell as test customer
        $this->customer = User::factory()->create([
            'role_id' => 3,
            'first_name' => 'Simba',
            'last_name' => 'Powell',
            'email' => 'simba.powell@example.com'
        ]);
        
        // Create customer profile with account balance
        $this->customer->profile()->create([
            'account_number' => 'SIM001',
            'tax_number' => '123456789',
            'telephone_number' => '555-0123',
            'street_address' => '123 Test Street',
            'city_town' => 'Test City',
            'parish' => 'Test Parish',
            'country' => 'Test Country'
        ]);
        
        // Set initial account balance
        $this->customer->account_balance = 500.00;
        $this->customer->save();
        
        // Create a test package
        $this->package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'tracking_number' => 'TEST123456',
            'weight' => 2.5,
            'estimated_value' => 150.00,
            'status' => 'ready'
        ]);
        
        $this->distributionService = app(PackageDistributionService::class);
    }

    /** @test */
    public function it_calculates_distribution_costs_correctly()
    {
        // Set package fees for testing
        $this->package->update([
            'freight_price' => 45.00,
            'clearance_fee' => 15.50,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        $expectedTotalCost = 45.00 + 15.50 + 10.00 + 5.00; // 75.50
        
        // Test distribution with exact payment
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            $expectedTotalCost, // Exact payment
            $this->admin,
            ['credit' => false, 'account' => true], // Don't apply credit balance
            ['notes' => 'Test distribution for Simba Powell']
        );
        
        // Verify distribution was successful
        $this->assertTrue($result['success']);
        $this->assertEquals('Packages distributed successfully', $result['message']);
        
        $distribution = $result['distribution'];
        $this->assertEquals($expectedTotalCost, $distribution->total_amount);
        $this->assertEquals($expectedTotalCost, $distribution->amount_collected);
        $this->assertEquals('paid', $distribution->payment_status);
        
        // Verify package status updated
        $this->package->refresh();
        $this->assertEquals('delivered', $this->package->status);
    }

    /** @test */
    public function it_processes_customer_transaction_correctly()
    {
        $initialBalance = $this->customer->account_balance;
        $distributionCost = 75.50;
        
        // Set package fees
        $this->package->update([
            'freight_price' => 45.00,
            'clearance_fee' => 15.50,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        // Distribute with no cash payment (all charged to account)
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => false, 'account' => true], // Don't apply credit balance
            ['notes' => 'Test distribution with transaction']
        );
        
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify customer transaction was created for the charge
        $chargeTransaction = CustomerTransaction::where('user_id', $this->customer->id)
            ->where('reference_type', 'package_distribution')
            ->where('reference_id', $distribution->id)
            ->where('type', 'charge')
            ->first();
            
        $this->assertNotNull($chargeTransaction);
        $this->assertEquals($distributionCost, $chargeTransaction->amount);
        $this->assertEquals($initialBalance, $chargeTransaction->balance_before);
        $this->assertEquals($initialBalance - $distributionCost, $chargeTransaction->balance_after);
        
        // Verify customer balance updated
        $this->customer->refresh();
        $this->assertEquals($initialBalance - $distributionCost, $this->customer->account_balance);
    }

    /** @test */
    public function it_handles_insufficient_balance_correctly()
    {
        // Set customer balance lower than distribution cost
        $this->customer->account_balance = 25.00;
        $this->customer->save();
        
        $distributionCost = 75.50;
        
        // Set package fees
        $this->package->update([
            'freight_price' => 45.00,
            'clearance_fee' => 15.50,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        // Distribute with no cash payment (all charged to account)
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => false, 'account' => true], // Apply account balance
            ['notes' => 'Test insufficient balance']
        );
        
        // Distribution should still succeed (system allows negative balances)
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify distribution was created and marked as partial (since some account balance was applied)
        $this->assertNotNull($distribution);
        $this->assertEquals('partial', $distribution->payment_status);
        
        // Verify customer balance went negative
        $this->customer->refresh();
        $this->assertEquals(25.00 - $distributionCost, $this->customer->account_balance);
        $this->assertTrue($this->customer->account_balance < 0);
    }

    /** @test */
    public function it_handles_package_with_various_fees()
    {
        // Create package with different fee structure
        $this->package->update([
            'freight_price' => 60.00,
            'clearance_fee' => 25.00,
            'storage_fee' => 15.00,
            'delivery_fee' => 8.00
        ]);
        
        $expectedTotal = 60.00 + 25.00 + 15.00 + 8.00; // 108.00
        
        // Test distribution
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            $expectedTotal,
            $this->admin,
            ['credit' => false, 'account' => true],
            ['notes' => 'Test various fees for Simba Powell']
        );
        
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify total calculation
        $this->assertEquals($expectedTotal, $distribution->total_amount);
        $this->assertEquals('paid', $distribution->payment_status);
        
        // Verify distribution item was created with correct fees
        $distributionItem = $distribution->items()->first();
        $this->assertNotNull($distributionItem);
        $this->assertEquals(60.00, $distributionItem->freight_price);
        $this->assertEquals(25.00, $distributionItem->clearance_fee);
        $this->assertEquals(15.00, $distributionItem->storage_fee);
        $this->assertEquals(8.00, $distributionItem->delivery_fee);
    }

    /** @test */
    public function it_applies_credit_balance_correctly()
    {
        // Set customer credit balance
        $this->customer->credit_balance = 30.00;
        $this->customer->save();
        
        // Set package fees
        $this->package->update([
            'freight_price' => 45.00,
            'clearance_fee' => 15.50,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        $totalCost = 75.50;
        $cashPayment = 20.00;
        $expectedCreditUsed = 30.00; // All credit should be used
        $expectedAccountCharge = $totalCost - $expectedCreditUsed - $cashPayment; // 25.50
        
        // Distribute with credit balance application
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            $cashPayment,
            $this->admin,
            ['credit' => true, 'account' => true], // Apply credit balance
            ['notes' => 'Test credit balance application']
        );
        
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify credit was applied
        $this->assertEquals($expectedCreditUsed, $distribution->credit_applied);
        $this->assertEquals($cashPayment, $distribution->amount_collected);
        $this->assertEquals('paid', $distribution->payment_status);
        
        // Verify customer credit balance was reduced
        $this->customer->refresh();
        $this->assertEquals(0.00, $this->customer->credit_balance);
    }

    /** @test */
    public function it_handles_write_off_correctly()
    {
        // Set package fees
        $this->package->update([
            'freight_price' => 45.00,
            'clearance_fee' => 15.50,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        $originalTotal = 75.50;
        $writeOffAmount = 25.50;
        $expectedChargeAmount = $originalTotal - $writeOffAmount; // 50.00
        
        // Distribute with write-off
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => false, 'account' => true],
            [
                'writeOff' => $writeOffAmount,
                'writeOffReason' => 'Customer loyalty discount',
                'notes' => 'Test write-off for Simba Powell'
            ]
        );
        
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify write-off was applied
        $this->assertEquals($originalTotal, $distribution->total_amount); // Original total
        $this->assertEquals($writeOffAmount, $distribution->write_off_amount);
        $this->assertEquals('paid', $distribution->payment_status); // Account balance covers remaining amount after write-off
        
        // Verify write-off transaction was created
        $writeOffTransaction = CustomerTransaction::where('user_id', $this->customer->id)
            ->where('reference_type', 'package_distribution')
            ->where('reference_id', $distribution->id)
            ->where('type', 'write_off')
            ->first();
            
        $this->assertNotNull($writeOffTransaction);
        $this->assertEquals($writeOffAmount, $writeOffTransaction->amount);
    }

    /** @test */
    public function it_handles_percentage_write_off_correctly()
    {
        // Set package fees
        $this->package->update([
            'freight_price' => 80.00,
            'clearance_fee' => 20.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00
        ]);
        
        $originalTotal = 100.00;
        $writeOffPercentage = 25; // 25%
        $expectedWriteOffAmount = ($originalTotal * $writeOffPercentage) / 100; // 25.00
        $expectedChargeAmount = $originalTotal - $expectedWriteOffAmount; // 75.00
        
        // Distribute with percentage write-off
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => false, 'account' => true],
            [
                'writeOff' => $expectedWriteOffAmount, // Service expects calculated amount
                'writeOffReason' => 'Customer loyalty discount - 25%',
                'notes' => 'Test percentage write-off for Simba Powell'
            ]
        );
        
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify write-off was applied correctly
        $this->assertEquals($originalTotal, $distribution->total_amount); // Original total
        $this->assertEquals($expectedWriteOffAmount, $distribution->write_off_amount);
        $this->assertEquals('paid', $distribution->payment_status); // Account balance covers remaining amount after write-off
        
        // Verify write-off transaction was created with correct amount
        $writeOffTransaction = CustomerTransaction::where('user_id', $this->customer->id)
            ->where('reference_type', 'package_distribution')
            ->where('reference_id', $distribution->id)
            ->where('type', 'write_off')
            ->first();
            
        $this->assertNotNull($writeOffTransaction);
        $this->assertEquals($expectedWriteOffAmount, $writeOffTransaction->amount);
        $this->assertEquals(25.00, $writeOffTransaction->amount); // Verify exact amount
    }

    /** @test */
    public function it_maintains_audit_trail_correctly()
    {
        // Set package fees
        $this->package->update([
            'freight_price' => 45.00,
            'clearance_fee' => 15.50,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => false, 'account' => true],
            ['notes' => 'Audit trail test']
        );
        
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify distribution record has proper audit fields
        $this->assertNotNull($distribution->created_at);
        $this->assertNotNull($distribution->updated_at);
        $this->assertEquals($this->admin->id, $distribution->distributed_by);
        $this->assertNotNull($distribution->distributed_at);
        $this->assertNotNull($distribution->receipt_number);
        
        // Verify transaction has proper audit fields
        $transaction = CustomerTransaction::where('user_id', $this->customer->id)
            ->where('reference_type', 'package_distribution')
            ->where('reference_id', $distribution->id)
            ->first();
            
        $this->assertNotNull($transaction);
        $this->assertNotNull($transaction->created_at);
        $this->assertEquals($this->admin->id, $transaction->created_by);
    }

    /** @test */
    public function it_handles_multiple_packages_for_same_customer()
    {
        // Create additional packages for Simba Powell
        $package2 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'tracking_number' => 'TEST789',
            'weight' => 1.5,
            'estimated_value' => 75.00,
            'status' => 'ready',
            'freight_price' => 30.00,
            'clearance_fee' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 3.00
        ]);
        
        $package3 = Package::factory()->create([
            'user_id' => $this->customer->id,
            'tracking_number' => 'TEST999',
            'weight' => 3.0,
            'estimated_value' => 200.00,
            'status' => 'ready',
            'freight_price' => 60.00,
            'clearance_fee' => 20.00,
            'storage_fee' => 12.00,
            'delivery_fee' => 8.00
        ]);
        
        // Set fees for original package
        $this->package->update([
            'freight_price' => 45.00,
            'clearance_fee' => 15.50,
            'storage_fee' => 10.00,
            'delivery_fee' => 5.00
        ]);
        
        $initialBalance = $this->customer->account_balance;
        
        // Calculate expected total for all packages
        $package1Total = 45.00 + 15.50 + 10.00 + 5.00; // 75.50
        $package2Total = 30.00 + 10.00 + 5.00 + 3.00;   // 48.00
        $package3Total = 60.00 + 20.00 + 12.00 + 8.00;  // 100.00
        $expectedTotalCost = $package1Total + $package2Total + $package3Total; // 223.50
        
        // Distribute all packages together
        $result = $this->distributionService->distributePackages(
            [$this->package->id, $package2->id, $package3->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => false, 'account' => true],
            ['notes' => 'Multiple packages for Simba Powell']
        );
        
        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];
        
        // Verify total calculation
        $this->assertEquals($expectedTotalCost, $distribution->total_amount);
        
        // Verify all distribution items were created
        $this->assertEquals(3, $distribution->items()->count());
        
        // Verify final balance
        $this->customer->refresh();
        $expectedBalance = $initialBalance - $expectedTotalCost;
        $this->assertEquals($expectedBalance, $this->customer->account_balance);
        
        // Verify all packages were marked as delivered
        $this->package->refresh();
        $package2->refresh();
        $package3->refresh();
        
        $this->assertEquals('delivered', $this->package->status);
        $this->assertEquals('delivered', $package2->status);
        $this->assertEquals('delivered', $package3->status);
    }


}