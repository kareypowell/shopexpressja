<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GranularBalanceControlTest extends TestCase
{
    use RefreshDatabase;

    private $distributionService;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->distributionService = app(PackageDistributionService::class);
        $adminRole = Role::where('name', 'superadmin')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /** @test */
    public function it_can_use_credit_balance_only()
    {
        // Customer with both account and credit balance
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 500.00,
            'credit_balance' => 200.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 150.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Use credit balance only
        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => true] // Use credit balance only
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Credit balance should be reduced by 150
        // Account balance should remain unchanged
        $this->assertEquals(500.00, $customer->account_balance, 'Account balance should remain unchanged');
        $this->assertEquals(50.00, $customer->credit_balance, 'Credit balance should be reduced by 150');
    }

    /** @test */
    public function it_can_use_account_balance_only()
    {
        // Customer with both account and credit balance
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 500.00,
            'credit_balance' => 200.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 150.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Use account balance only
        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            ['account' => true] // Use account balance only
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Account balance should be reduced by 150
        // Credit balance should remain unchanged
        $this->assertEquals(350.00, $customer->account_balance, 'Account balance should be reduced by 150');
        $this->assertEquals(200.00, $customer->credit_balance, 'Credit balance should remain unchanged');
    }

    /** @test */
    public function it_can_use_both_credit_and_account_balance()
    {
        // Customer with limited credit balance
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 500.00,
            'credit_balance' => 50.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 150.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Use both credit and account balance
        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => true, 'account' => true] // Use both balances
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Credit balance should be fully used (50)
        // Account balance should be reduced by remaining amount (100)
        $this->assertEquals(400.00, $customer->account_balance, 'Account balance should be reduced by 100');
        $this->assertEquals(0.00, $customer->credit_balance, 'Credit balance should be fully used');
    }

    /** @test */
    public function it_prioritizes_credit_balance_over_account_balance()
    {
        // Customer with sufficient credit balance
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 500.00,
            'credit_balance' => 200.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 150.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Use both balances, but credit should be used first
        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => true, 'account' => true] // Use both balances
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Only credit balance should be used since it's sufficient
        // Account balance should remain unchanged
        $this->assertEquals(500.00, $customer->account_balance, 'Account balance should remain unchanged');
        $this->assertEquals(50.00, $customer->credit_balance, 'Credit balance should be reduced by 150');
    }

    /** @test */
    public function it_handles_insufficient_credit_balance_gracefully()
    {
        // Customer with insufficient credit balance
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 500.00,
            'credit_balance' => 50.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 150.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Try to use credit balance only (insufficient)
        $result = $this->distributionService->distributePackages(
            [$package->id],
            0.00, // No cash payment
            $this->admin,
            ['credit' => true] // Use credit balance only
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Credit balance should be fully used (50)
        // Account balance should be charged for remaining amount (100)
        $this->assertEquals(400.00, $customer->account_balance, 'Account balance should be charged for unpaid amount');
        $this->assertEquals(0.00, $customer->credit_balance, 'Credit balance should be fully used');
    }

    /** @test */
    public function it_combines_cash_payment_with_selected_balances()
    {
        // Customer with both balances
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 500.00,
            'credit_balance' => 100.00,
        ]);

        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 200.00,
            'customs_duty' => 0.00,
            'storage_fee' => 0.00,
            'delivery_fee' => 0.00,
        ]);

        // Pay 50 cash + use credit balance only
        $result = $this->distributionService->distributePackages(
            [$package->id],
            50.00, // Cash payment
            $this->admin,
            ['credit' => true] // Use credit balance only
        );

        $this->assertTrue($result['success']);
        $customer->refresh();

        // Credit balance should be reduced by 100 (all used)
        // Account balance should be charged for remaining 50 (200 - 50 cash - 100 credit)
        $this->assertEquals(450.00, $customer->account_balance, 'Account balance should be charged for unpaid amount');
        $this->assertEquals(0.00, $customer->credit_balance, 'Credit balance should be fully used');
    }
}