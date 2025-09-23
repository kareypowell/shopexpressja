<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class CompleteReceiptIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private $distributionService;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->distributionService = app(PackageDistributionService::class);
        
        $adminRole = Role::where('name', 'superadmin')->first();
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        // Mock storage
        Storage::fake('public');
    }

    /** @test */
    public function it_generates_complete_receipt_for_simba_powell_scenario()
    {
        // Create Simba Powell with the exact scenario from the original issue
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'Simba',
            'last_name' => 'Powell',
            'email' => 'simba.powell@example.com',
            'account_balance' => 875.00,
            'credit_balance' => 0.00,
        ]);

        // Note: Not creating profile to keep test simple - receipt will show "N/A" for account number

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'tracking_number' => 'SHS123456789',
            'description' => 'Electronics Package',
            'weight' => 5.5,
            'freight_price' => 5000.00,
            'clearance_fee' => 1500.00,
            'storage_fee' => 200.00,
            'delivery_fee' => 1242.00, // Total: 7942
        ]);

        // Customer pays 8000 cash (58 overpayment)
        $result = $this->distributionService->distributePackages(
            [$package->id],
            8000.00,
            $this->admin,
            [] // No balance application
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Verify the distribution was created correctly
        $this->assertEquals(7942.00, $distribution->total_amount);
        $this->assertEquals(8000.00, $distribution->amount_collected);
        $this->assertEquals(0.00, $distribution->credit_applied);
        $this->assertEquals(0.00, $distribution->account_balance_applied);
        $this->assertEquals('paid', $distribution->payment_status);

        // Verify customer balances
        $customer->refresh();
        $this->assertEquals(875.00, $customer->account_balance, 'Account balance should remain unchanged');
        $this->assertEquals(58.00, $customer->credit_balance, 'Credit balance should be the overpayment');

        // Verify PDF receipt was generated
        $this->assertNotEmpty($distribution->receipt_path);
        Storage::disk('public')->assertExists($distribution->receipt_path);

        // Verify transaction history shows complete audit trail
        $transactions = $customer->transactions()->orderBy('created_at')->get();
        $this->assertCount(3, $transactions, 'Should have charge, payment, and credit transactions');

        $chargeTransaction = $transactions->where('type', 'charge')->first();
        $this->assertEquals(7942.00, $chargeTransaction->amount);
        $this->assertStringStartsWith('Package distribution charge', $chargeTransaction->description);

        $paymentTransaction = $transactions->where('type', 'payment')->first();
        $this->assertEquals(7942.00, $paymentTransaction->amount);
        $this->assertStringStartsWith('Payment received for package distribution', $paymentTransaction->description);

        $creditTransaction = $transactions->where('type', 'credit')->first();
        $this->assertEquals(58.00, $creditTransaction->amount);
        $this->assertStringStartsWith('Overpayment credit', $creditTransaction->description);
    }

    /** @test */
    public function it_generates_receipt_with_granular_balance_application()
    {
        // Customer with both account and credit balance
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'Balance',
            'last_name' => 'Customer',
            'email' => 'balance@example.com',
            'account_balance' => 300.00,
            'credit_balance' => 150.00,
        ]);

        // Note: Not creating profile to keep test simple

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'tracking_number' => 'BAL123',
            'description' => 'Balance Test Package',
            'freight_price' => 200.00,
            'clearance_fee' => 100.00,
            'storage_fee' => 50.00,
            'delivery_fee' => 50.00, // Total: 400
        ]);

        // Customer pays 50 cash and uses both balances
        $result = $this->distributionService->distributePackages(
            [$package->id],
            50.00, // Cash payment
            $this->admin,
            ['credit' => true, 'account' => true] // Use both balances
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Verify the distribution details
        $this->assertEquals(400.00, $distribution->total_amount);
        $this->assertEquals(50.00, $distribution->amount_collected);
        $this->assertEquals(150.00, $distribution->credit_applied); // All credit used
        $this->assertEquals(200.00, $distribution->account_balance_applied); // Remaining amount
        $this->assertEquals('paid', $distribution->payment_status);

        // Verify customer balances after distribution
        $customer->refresh();
        $this->assertEquals(100.00, $customer->account_balance); // 300 - 200 used
        $this->assertEquals(0.00, $customer->credit_balance); // All credit used

        // Verify PDF receipt was generated
        $this->assertNotEmpty($distribution->receipt_path);
        Storage::disk('public')->assertExists($distribution->receipt_path);
    }

    /** @test */
    public function it_generates_receipt_with_write_off_discount()
    {
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'Discount',
            'last_name' => 'Customer',
            'account_balance' => 0.00,
            'credit_balance' => 0.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'tracking_number' => 'DISC123',
            'freight_price' => 100.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Apply a discount and partial payment
        $result = $this->distributionService->distributePackages(
            [$package->id],
            60.00, // Partial payment
            $this->admin,
            [], // No balance application
            [
                'writeOff' => 25.00, // $25 discount
                'writeOffReason' => 'Customer loyalty discount',
                'notes' => 'Valued customer discount applied'
            ]
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Verify the distribution details
        $this->assertEquals(100.00, $distribution->total_amount); // Original amount
        $this->assertEquals(60.00, $distribution->amount_collected);
        $this->assertEquals(25.00, $distribution->write_off_amount);
        $this->assertEquals('partial', $distribution->payment_status); // 60 cash vs 75 net total (100 - 25 write-off)

        // Verify PDF receipt was generated with discount information
        $this->assertNotEmpty($distribution->receipt_path);
        Storage::disk('public')->assertExists($distribution->receipt_path);
    }

    /** @test */
    public function it_handles_customer_without_profile_in_receipt()
    {
        // Customer without profile
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'No',
            'last_name' => 'Profile',
            'email' => 'noprofile@example.com',
            'account_balance' => 100.00,
        ]);
        // Intentionally not creating a profile

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'tracking_number' => 'NOPROF123',
            'freight_price' => 75.00,
            'clearance_fee' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        $result = $this->distributionService->distributePackages(
            [$package->id],
            75.00,
            $this->admin,
            []
        );

        $this->assertTrue($result['success']);
        $distribution = $result['distribution'];

        // Should still generate PDF successfully even without profile
        $this->assertNotEmpty($distribution->receipt_path);
        Storage::disk('public')->assertExists($distribution->receipt_path);

        // Verify customer name is still included correctly
        $receiptGenerator = app(\App\Services\ReceiptGeneratorService::class);
        $receiptData = $receiptGenerator->formatReceiptData($distribution);
        
        $this->assertEquals('No Profile', $receiptData['customer']['name']);
        $this->assertEquals('N/A', $receiptData['customer']['account_number']);
    }
}