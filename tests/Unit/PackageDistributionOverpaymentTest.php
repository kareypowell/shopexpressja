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

class PackageDistributionOverpaymentTest extends TestCase
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
            'account_balance' => 0.00,
            'credit_balance' => 0.00,
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
            'freight_price' => 25.00,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 3.00,
            // Total cost: $43.00
        ]);
    }

    /** @test */
    public function it_handles_exact_payment_correctly()
    {
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            43.00, // Exact amount
            $this->user,
            false
        );
        
        $this->assertTrue($result['success']);
        
        // Check customer balances remain zero (no overpayment)
        $this->customer->refresh();
        $this->assertEquals(0.00, $this->customer->account_balance);
        $this->assertEquals(0.00, $this->customer->credit_balance);
        
        // Check distribution record
        $distribution = $result['distribution'];
        $this->assertEquals(43.00, $distribution->total_amount);
        $this->assertEquals(43.00, $distribution->amount_collected);
        $this->assertEquals(0.00, $distribution->credit_applied);
        $this->assertEquals('paid', $distribution->payment_status);
    }

    /** @test */
    public function it_handles_overpayment_correctly()
    {
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            50.00, // $7.00 overpayment
            $this->user,
            false
        );
        
        $this->assertTrue($result['success']);
        
        // Check customer received credit for overpayment
        $this->customer->refresh();
        $this->assertEquals(0.00, $this->customer->account_balance); // Account balance unchanged
        $this->assertEquals(7.00, $this->customer->credit_balance); // Credit balance increased by overpayment
        
        // Check distribution record
        $distribution = $result['distribution'];
        $this->assertEquals(43.00, $distribution->total_amount);
        $this->assertEquals(50.00, $distribution->amount_collected);
        $this->assertEquals(0.00, $distribution->credit_applied);
        $this->assertEquals('paid', $distribution->payment_status);
        
        // Check transaction was created
        $transaction = $this->customer->transactions()->latest()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('credit', $transaction->type);
        $this->assertEquals(7.00, $transaction->amount);
        $this->assertStringContainsString('Overpayment credit', $transaction->description);
    }

    /** @test */
    public function it_handles_underpayment_correctly()
    {
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            30.00, // $13.00 underpayment
            $this->user,
            false
        );
        
        $this->assertTrue($result['success']);
        
        // Check customer balances remain zero (no credit for underpayment)
        $this->customer->refresh();
        $this->assertEquals(0.00, $this->customer->account_balance);
        $this->assertEquals(0.00, $this->customer->credit_balance);
        
        // Check distribution record shows partial payment
        $distribution = $result['distribution'];
        $this->assertEquals(43.00, $distribution->total_amount);
        $this->assertEquals(30.00, $distribution->amount_collected);
        $this->assertEquals(0.00, $distribution->credit_applied);
        $this->assertEquals('partial', $distribution->payment_status);
        $this->assertEquals(13.00, $distribution->outstanding_balance);
    }

    /** @test */
    public function it_handles_overpayment_with_existing_credit()
    {
        // Give customer existing credit
        $this->customer->update(['credit_balance' => 5.00]);
        
        $result = $this->distributionService->distributePackages(
            [$this->package->id],
            50.00, // $7.00 overpayment
            $this->user,
            false
        );
        
        $this->assertTrue($result['success']);
        
        // Check customer credit increased by overpayment amount
        $this->customer->refresh();
        $this->assertEquals(0.00, $this->customer->account_balance);
        $this->assertEquals(12.00, $this->customer->credit_balance); // 5.00 + 7.00
    }
}