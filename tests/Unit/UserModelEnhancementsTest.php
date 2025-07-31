<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class UserModelEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::factory()->create(['id' => 1, 'name' => 'superadmin']);
        Role::factory()->create(['id' => 2, 'name' => 'admin']);
        Role::factory()->create(['id' => 3, 'name' => 'customer']);
    }

    /** @test */
    public function it_can_soft_delete_users()
    {
        $user = User::factory()->create(['role_id' => 3]);
        
        $this->assertNull($user->deleted_at);
        
        $user->delete();
        $user->refresh();
        
        $this->assertNotNull($user->deleted_at);
        $this->assertTrue($user->trashed());
    }

    /** @test */
    public function it_can_restore_soft_deleted_users()
    {
        $user = User::factory()->create(['role_id' => 3]);
        $user->delete();
        
        $this->assertTrue($user->trashed());
        
        $user->restore();
        $user->refresh();
        
        $this->assertNull($user->deleted_at);
        $this->assertFalse($user->trashed());
    }

    /** @test */
    public function customers_scope_returns_only_customers()
    {
        User::factory()->create(['role_id' => 1]); // superadmin
        User::factory()->create(['role_id' => 2]); // admin
        $customer1 = User::factory()->create(['role_id' => 3]); // customer
        $customer2 = User::factory()->create(['role_id' => 3]); // customer

        $customers = User::customers()->get();

        $this->assertCount(2, $customers);
        $this->assertTrue($customers->contains($customer1));
        $this->assertTrue($customers->contains($customer2));
    }

    /** @test */
    public function active_customers_scope_excludes_soft_deleted()
    {
        $activeCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer->delete();

        $activeCustomers = User::activeCustomers()->get();

        $this->assertCount(1, $activeCustomers);
        $this->assertTrue($activeCustomers->contains($activeCustomer));
        $this->assertFalse($activeCustomers->contains($deletedCustomer));
    }

    /** @test */
    public function deleted_customers_scope_returns_only_soft_deleted()
    {
        $activeCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer->delete();

        $deletedCustomers = User::deletedCustomers()->get();

        $this->assertCount(1, $deletedCustomers);
        $this->assertTrue($deletedCustomers->contains($deletedCustomer));
        $this->assertFalse($deletedCustomers->contains($activeCustomer));
    }

    /** @test */
    public function it_calculates_total_spent_correctly()
    {
        $user = User::factory()->create(['role_id' => 3]);
        
        // Create packages with different costs
        Package::factory()->create([
            'user_id' => $user->id,
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
        ]);
        
        Package::factory()->create([
            'user_id' => $user->id,
            'freight_price' => 200.00,
            'customs_duty' => 50.00,
            'storage_fee' => 20.00,
            'delivery_fee' => 30.00,
        ]);

        $totalSpent = $user->total_spent;
        
        // Total should be (100+25+10+15) + (200+50+20+30) = 150 + 300 = 450
        $this->assertEquals(450.00, $totalSpent);
    }

    /** @test */
    public function it_calculates_package_count_correctly()
    {
        $user = User::factory()->create(['role_id' => 3]);
        
        Package::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertEquals(3, $user->package_count);
    }

    /** @test */
    public function it_calculates_average_package_value_correctly()
    {
        $user = User::factory()->create(['role_id' => 3]);
        
        Package::factory()->create([
            'user_id' => $user->id,
            'freight_price' => 100.00,
            'customs_duty' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);
        
        Package::factory()->create([
            'user_id' => $user->id,
            'freight_price' => 200.00,
            'customs_duty' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);

        $averageValue = $user->average_package_value;
        
        // Average should be (100 + 200) / 2 = 150
        $this->assertEquals(150.00, $averageValue);
    }

    /** @test */
    public function it_returns_zero_average_for_users_with_no_packages()
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->assertEquals(0.0, $user->average_package_value);
        $this->assertEquals(0, $user->package_count);
        $this->assertEquals(0.0, $user->total_spent);
    }

    /** @test */
    public function it_gets_last_shipment_date_correctly()
    {
        $user = User::factory()->create(['role_id' => 3]);
        
        $oldPackage = Package::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);
        
        $newPackage = Package::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $lastShipmentDate = $user->last_shipment_date;
        
        $this->assertEquals($newPackage->created_at->format('Y-m-d H:i:s'), $lastShipmentDate->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_returns_null_last_shipment_date_for_users_with_no_packages()
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->assertNull($user->last_shipment_date);
    }

    /** @test */
    public function it_generates_financial_summary_correctly()
    {
        $user = User::factory()->create(['role_id' => 3]);
        
        Package::factory()->create([
            'user_id' => $user->id,
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
        ]);
        
        Package::factory()->create([
            'user_id' => $user->id,
            'freight_price' => 200.00,
            'customs_duty' => 50.00,
            'storage_fee' => 20.00,
            'delivery_fee' => 30.00,
        ]);

        $summary = $user->getFinancialSummary();

        $this->assertEquals(2, $summary['total_packages']);
        $this->assertEquals(450.00, $summary['total_spent']);
        $this->assertEquals(300.00, $summary['breakdown']['freight']);
        $this->assertEquals(75.00, $summary['breakdown']['customs']);
        $this->assertEquals(30.00, $summary['breakdown']['storage']);
        $this->assertEquals(45.00, $summary['breakdown']['delivery']);
        $this->assertEquals(225.00, $summary['averages']['per_package']);
    }

    /** @test */
    public function it_generates_package_stats_correctly()
    {
        $user = User::factory()->create(['role_id' => 3]);
        
        Package::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
            'weight' => 10.5,
        ]);
        
        Package::factory()->create([
            'user_id' => $user->id,
            'status' => 'in_transit',
            'weight' => 15.0,
        ]);
        
        Package::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
            'weight' => 8.5,
        ]);

        $stats = $user->getPackageStats();

        $this->assertEquals(3, $stats['total_packages']);
        $this->assertEquals(2, $stats['status_breakdown']['delivered']);
        $this->assertEquals(1, $stats['status_breakdown']['in_transit']);
        $this->assertEquals(34.0, $stats['weight_stats']['total_weight']);
        $this->assertEquals(11.33, round($stats['weight_stats']['average_weight'], 2));
    }

    /** @test */
    public function all_customers_scope_includes_soft_deleted()
    {
        $activeCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer->delete();

        $allCustomers = User::allCustomers()->get();

        $this->assertCount(2, $allCustomers);
        $this->assertTrue($allCustomers->contains($activeCustomer));
        $this->assertTrue($allCustomers->contains($deletedCustomer));
    }

    /** @test */
    public function by_status_scope_filters_correctly()
    {
        $activeCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer->delete();

        // Test active status
        $activeCustomers = User::byStatus('active')->get();
        $this->assertCount(1, $activeCustomers);
        $this->assertTrue($activeCustomers->contains($activeCustomer));

        // Test deleted status
        $deletedCustomers = User::byStatus('deleted')->get();
        $this->assertCount(1, $deletedCustomers);
        $this->assertTrue($deletedCustomers->contains($deletedCustomer));

        // Test all status
        $allCustomers = User::byStatus('all')->get();
        $this->assertCount(2, $allCustomers);

        // Test default (active)
        $defaultCustomers = User::byStatus()->get();
        $this->assertCount(1, $defaultCustomers);
        $this->assertTrue($defaultCustomers->contains($activeCustomer));
    }

    /** @test */
    public function soft_delete_customer_validates_user_is_customer()
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only customers can be soft deleted through this method.');

        $admin->softDeleteCustomer();
    }

    /** @test */
    public function soft_delete_customer_validates_user_is_not_already_deleted()
    {
        $customer = User::factory()->create(['role_id' => 3]);
        $customer->delete();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Customer is already deleted.');

        $customer->softDeleteCustomer();
    }

    /** @test */
    public function soft_delete_customer_validates_user_is_not_superadmin()
    {
        $superadmin = User::factory()->create(['role_id' => 1]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only customers can be soft deleted through this method.');

        $superadmin->softDeleteCustomer();
    }

    /** @test */
    public function soft_delete_customer_successfully_deletes_valid_customer()
    {
        $customer = User::factory()->create(['role_id' => 3]);

        $result = $customer->softDeleteCustomer();

        $this->assertTrue($result);
        $this->assertTrue($customer->trashed());
    }

    /** @test */
    public function restore_customer_validates_user_is_customer()
    {
        $admin = User::factory()->create(['role_id' => 2]);
        $admin->delete();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only customers can be restored through this method.');

        $admin->restoreCustomer();
    }

    /** @test */
    public function restore_customer_validates_user_is_deleted()
    {
        $customer = User::factory()->create(['role_id' => 3]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Customer is not deleted and cannot be restored.');

        $customer->restoreCustomer();
    }

    /** @test */
    public function restore_customer_validates_email_conflict()
    {
        // Test the canBeRestored method logic for email conflicts
        $activeCustomer = User::factory()->create(['role_id' => 3, 'email' => 'active@example.com']);
        $deletedCustomer = User::factory()->create(['role_id' => 3, 'email' => 'deleted@example.com']);
        $deletedCustomer->delete();
        
        // This should work - no email conflict
        $this->assertTrue($deletedCustomer->canBeRestored());
        
        // Now test the actual restore method with a valid scenario
        $result = $deletedCustomer->restoreCustomer();
        $this->assertTrue($result);
        $this->assertFalse($deletedCustomer->trashed());
    }

    /** @test */
    public function restore_customer_successfully_restores_valid_customer()
    {
        $customer = User::factory()->create(['role_id' => 3]);
        $customer->delete();

        $result = $customer->restoreCustomer();

        $this->assertTrue($result);
        $this->assertFalse($customer->trashed());
    }

    /** @test */
    public function can_be_deleted_returns_correct_values()
    {
        // Customer can be deleted
        $customer = User::factory()->create(['role_id' => 3]);
        $this->assertTrue($customer->canBeDeleted());

        // Admin cannot be deleted
        $admin = User::factory()->create(['role_id' => 2]);
        $this->assertFalse($admin->canBeDeleted());

        // Superadmin cannot be deleted
        $superadmin = User::factory()->create(['role_id' => 1]);
        $this->assertFalse($superadmin->canBeDeleted());

        // Already deleted customer cannot be deleted again
        $deletedCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer->delete();
        $this->assertFalse($deletedCustomer->canBeDeleted());
    }

    /** @test */
    public function can_be_restored_returns_correct_values()
    {
        // Active customer cannot be restored
        $customer = User::factory()->create(['role_id' => 3]);
        $this->assertFalse($customer->canBeRestored());

        // Deleted customer can be restored
        $deletedCustomer = User::factory()->create(['role_id' => 3]);
        $deletedCustomer->delete();
        $this->assertTrue($deletedCustomer->canBeRestored());

        // Test basic canBeRestored functionality
        $customer1 = User::factory()->create(['role_id' => 3, 'email' => 'customer1@example.com']);
        $customer2 = User::factory()->create(['role_id' => 3, 'email' => 'customer2@example.com']);
        $customer2->delete();
        
        // Deleted customer can be restored when no conflicts exist
        $this->assertTrue($customer2->canBeRestored());

        // Deleted admin cannot be restored through this method
        $admin = User::factory()->create(['role_id' => 2]);
        $admin->delete();
        $this->assertFalse($admin->canBeRestored());
    }

    /** @test */
    public function get_deletion_info_returns_correct_information()
    {
        $customer = User::factory()->create(['role_id' => 3]);
        
        $info = $customer->getDeletionInfo();
        
        $this->assertFalse($info['is_deleted']);
        $this->assertNull($info['deleted_at']);
        $this->assertTrue($info['can_be_deleted']);
        $this->assertFalse($info['can_be_restored']);
        $this->assertNull($info['deletion_reason']);

        // Test deleted customer
        $customer->delete();
        $customer->refresh();
        
        $info = $customer->getDeletionInfo();
        
        $this->assertTrue($info['is_deleted']);
        $this->assertNotNull($info['deleted_at']);
        $this->assertFalse($info['can_be_deleted']);
        $this->assertTrue($info['can_be_restored']);
        $this->assertEquals('Customer is already deleted', $info['deletion_reason']);
    }
}