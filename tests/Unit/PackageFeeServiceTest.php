<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PackageFeeService;
use App\Models\Package;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Role;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageFeeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $feeService;
    protected $user;
    protected $customer;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->feeService = new PackageFeeService();
        
        // Create test user (admin)
        $adminRole = Role::where('name', 'admin')->first();
        $this->user = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create test customer
        $customerRole = Role::where('name', 'customer')->first();
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'account_balance' => 100.00,
            'credit_balance' => 50.00,
        ]);
        
        // Create test package
        $manifest = Manifest::factory()->create();
        $this->package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'status' => PackageStatus::CUSTOMS,
            'freight_price' => 25.00,
            'clearance_fee' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);
    }

    /** @test */
    public function it_validates_fees_correctly()
    {
        $validFees = [
            'clearance_fee' => 10.50,
            'storage_fee' => 5.00,
            'delivery_fee' => 2.50,
        ];
        
        $errors = $this->feeService->validateFees($validFees);
        $this->assertEmpty($errors);
        
        $invalidFees = [
            'clearance_fee' => -5,
            'storage_fee' => 'invalid',
            // missing delivery_fee
        ];
        
        $errors = $this->feeService->validateFees($invalidFees);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('clearance_fee', $errors);
        $this->assertArrayHasKey('storage_fee', $errors);
        $this->assertArrayHasKey('delivery_fee', $errors);
    }

    /** @test */
    public function it_calculates_balance_impact_correctly()
    {
        $fees = [
            'clearance_fee' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 3.00,
        ];
        
        $impact = $this->feeService->calculateBalanceImpact($this->package, $fees, false);
        
        $this->assertEquals(25.00, $impact['current_total_cost']); // Only freight price initially
        $this->assertEquals(43.00, $impact['new_total_cost']); // 25 + 10 + 5 + 3
        $this->assertEquals(18.00, $impact['cost_difference']); // 43 - 25
        $this->assertEquals(0, $impact['credit_to_apply']);
        $this->assertEquals(43.00, $impact['net_charge']);
    }

    /** @test */
    public function it_calculates_balance_impact_with_credit_application()
    {
        $fees = [
            'clearance_fee' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 3.00,
        ];
        
        $impact = $this->feeService->calculateBalanceImpact($this->package, $fees, true);
        
        $this->assertEquals(43.00, $impact['new_total_cost']);
        $this->assertEquals(43.00, $impact['credit_to_apply']); // Customer has 50 credit, package costs 43
        $this->assertEquals(0, $impact['net_charge']);
        $this->assertEquals(100.00, $impact['customer_balance_after']); // No charge to account balance
        $this->assertEquals(7.00, $impact['customer_credit_after']); // 50 - 43
    }

    /** @test */
    public function it_generates_fee_update_preview()
    {
        $fees = [
            'clearance_fee' => 15.00,
            'storage_fee' => 8.00,
            'delivery_fee' => 4.00,
        ];
        
        $preview = $this->feeService->getFeeUpdatePreview($this->package, $fees, true);
        
        $this->assertTrue($preview['valid']);
        $this->assertEquals('Ready for Pickup', $preview['package']['new_status']);
        $this->assertEquals(52.00, $preview['cost_summary']['new_total_cost']); // 25 + 15 + 8 + 4
        $this->assertEquals(50.00, $preview['cost_summary']['credit_to_apply']); // Customer's full credit
        $this->assertEquals(2.00, $preview['cost_summary']['net_charge']); // 52 - 50
    }

    /** @test */
    public function it_updates_package_fees_and_creates_status_history()
    {
        $fees = [
            'clearance_fee' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 3.00,
        ];
        
        $originalStatus = $this->package->status;
        
        $result = $this->feeService->updatePackageFeesAndSetReady(
            $this->package,
            $fees,
            $this->user,
            false
        );
        
        $this->assertTrue($result['success']);
        
        // Check package was updated
        $this->package->refresh();
        $this->assertEquals(PackageStatus::READY, $this->package->status);
        $this->assertEquals(10.00, $this->package->clearance_fee);
        $this->assertEquals(5.00, $this->package->storage_fee);
        $this->assertEquals(3.00, $this->package->delivery_fee);
        
        // Check status history was created
        $statusHistory = $this->package->statusHistory()->latest()->first();
        $this->assertNotNull($statusHistory);
        $this->assertEquals($originalStatus->value, $statusHistory->old_status);
        $this->assertEquals(PackageStatus::READY, $statusHistory->new_status);
        $this->assertEquals($this->user->id, $statusHistory->changed_by);
        $this->assertStringContainsString('Package fees updated', $statusHistory->notes);
    }
}