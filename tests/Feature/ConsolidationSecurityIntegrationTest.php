<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Services\PackageConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class ConsolidationSecurityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerUser;
    protected $otherCustomerUser;
    protected $consolidationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles using the seeder structure
        $adminRole = Role::create(['id' => 2, 'name' => 'admin', 'description' => 'Administrator']);
        $customerRole = Role::create(['id' => 3, 'name' => 'customer', 'description' => 'Customer']);

        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
        $this->otherCustomerUser = User::factory()->create(['role_id' => $customerRole->id]);

        $this->consolidationService = app(PackageConsolidationService::class);
    }

    /** @test */
    public function complete_consolidation_workflow_respects_permissions()
    {
        // Step 1: Create packages for a customer
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        // Step 2: Admin consolidates packages
        $this->actingAs($this->adminUser);
        
        $result = $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->adminUser,
            ['notes' => 'Test consolidation']
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Step 3: Verify consolidation history was created with proper permissions
        $history = $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            $this->adminUser
        );
        $this->assertGreaterThan(0, $history->count());

        // Step 4: Customer can view their own consolidation history
        $this->actingAs($this->customerUser);
        $customerHistory = $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            $this->customerUser
        );
        $this->assertEquals($history->count(), $customerHistory->count());

        // Step 5: Other customer cannot view the history
        $this->actingAs($this->otherCustomerUser);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            $this->otherCustomerUser
        );
    }

    /** @test */
    public function consolidation_audit_trail_maintains_security()
    {
        // Create consolidated package
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $this->actingAs($this->adminUser);
        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Admin can export audit trail
        $auditTrail = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            $this->adminUser
        );
        $this->assertIsArray($auditTrail);
        $this->assertArrayHasKey('consolidated_package', $auditTrail);

        // Customer can export their own audit trail
        $this->actingAs($this->customerUser);
        $customerAuditTrail = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            $this->customerUser
        );
        $this->assertIsArray($customerAuditTrail);

        // Other customer cannot export audit trail
        $this->actingAs($this->otherCustomerUser);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            $this->otherCustomerUser
        );
    }

    /** @test */
    public function status_updates_respect_permissions()
    {
        // Create consolidated package
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $this->actingAs($this->adminUser);
        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Admin can update status
        $statusResult = $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            'ready',
            $this->adminUser
        );
        $this->assertTrue($statusResult['success']);

        // Customer cannot update status
        $this->actingAs($this->customerUser);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            'shipped',
            $this->customerUser
        );
    }

    /** @test */
    public function unconsolidation_respects_permissions()
    {
        // Create consolidated package
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $this->actingAs($this->adminUser);
        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Customer cannot unconsolidate
        $this->actingAs($this->customerUser);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->consolidationService->unconsolidatePackages(
            $consolidatedPackage,
            $this->customerUser
        );

        // Admin can unconsolidate
        $this->actingAs($this->adminUser);
        $unconsolidateResult = $this->consolidationService->unconsolidatePackages(
            $consolidatedPackage,
            $this->adminUser
        );
        $this->assertTrue($unconsolidateResult['success']);
    }

    /** @test */
    public function customer_data_isolation_is_enforced()
    {
        // Create packages for different customers
        $customer1Packages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $customer2Packages = Package::factory()->count(2)->create([
            'user_id' => $this->otherCustomerUser->id,
            'status' => 'processing'
        ]);

        $this->actingAs($this->adminUser);

        // Create consolidated packages for each customer
        $result1 = $this->consolidationService->consolidatePackages(
            $customer1Packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $result2 = $this->consolidationService->consolidatePackages(
            $customer2Packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage1 = $result1['consolidated_package'];
        $consolidatedPackage2 = $result2['consolidated_package'];

        // Customer 1 can only access their own data
        $this->actingAs($this->customerUser);
        
        // Can access own consolidated package
        $this->assertTrue(Gate::allows('view', $consolidatedPackage1));
        $this->assertTrue(Gate::allows('viewHistory', $consolidatedPackage1));
        $this->assertTrue(Gate::allows('exportAuditTrail', $consolidatedPackage1));

        // Cannot access other customer's consolidated package
        $this->assertFalse(Gate::allows('view', $consolidatedPackage2));
        $this->assertFalse(Gate::allows('viewHistory', $consolidatedPackage2));
        $this->assertFalse(Gate::allows('exportAuditTrail', $consolidatedPackage2));

        // Customer 2 can only access their own data
        $this->actingAs($this->otherCustomerUser);
        
        // Can access own consolidated package
        $this->assertTrue(Gate::allows('view', $consolidatedPackage2));
        $this->assertTrue(Gate::allows('viewHistory', $consolidatedPackage2));
        $this->assertTrue(Gate::allows('exportAuditTrail', $consolidatedPackage2));

        // Cannot access other customer's consolidated package
        $this->assertFalse(Gate::allows('view', $consolidatedPackage1));
        $this->assertFalse(Gate::allows('viewHistory', $consolidatedPackage1));
        $this->assertFalse(Gate::allows('exportAuditTrail', $consolidatedPackage1));
    }

    /** @test */
    public function available_packages_respect_customer_isolation()
    {
        // Create packages for different customers
        Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        Package::factory()->count(2)->create([
            'user_id' => $this->otherCustomerUser->id,
            'status' => 'processing'
        ]);

        // Admin can access packages for any customer
        $this->actingAs($this->adminUser);
        $customer1Packages = $this->consolidationService->getAvailablePackagesForCustomer(
            $this->customerUser->id,
            $this->adminUser
        );
        $this->assertEquals(3, $customer1Packages->count());

        $customer2Packages = $this->consolidationService->getAvailablePackagesForCustomer(
            $this->otherCustomerUser->id,
            $this->adminUser
        );
        $this->assertEquals(2, $customer2Packages->count());

        // Customer can only access their own packages
        $this->actingAs($this->customerUser);
        $ownPackages = $this->consolidationService->getAvailablePackagesForCustomer(
            $this->customerUser->id,
            $this->customerUser
        );
        $this->assertEquals(3, $ownPackages->count());

        // Customer cannot access other customer's packages
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->consolidationService->getAvailablePackagesForCustomer(
            $this->otherCustomerUser->id,
            $this->customerUser
        );
    }

    /** @test */
    public function consolidated_packages_respect_customer_isolation()
    {
        // Create consolidated packages for different customers
        $customer1Packages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $customer2Packages = Package::factory()->count(2)->create([
            'user_id' => $this->otherCustomerUser->id,
            'status' => 'processing'
        ]);

        $this->actingAs($this->adminUser);

        $result1 = $this->consolidationService->consolidatePackages(
            $customer1Packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $result2 = $this->consolidationService->consolidatePackages(
            $customer2Packages->pluck('id')->toArray(),
            $this->adminUser
        );

        // Admin can access consolidated packages for any customer
        $customer1Consolidated = $this->consolidationService->getActiveConsolidatedPackagesForCustomer(
            $this->customerUser->id,
            $this->adminUser
        );
        $this->assertEquals(1, $customer1Consolidated->count());

        $customer2Consolidated = $this->consolidationService->getActiveConsolidatedPackagesForCustomer(
            $this->otherCustomerUser->id,
            $this->adminUser
        );
        $this->assertEquals(1, $customer2Consolidated->count());

        // Customer can only access their own consolidated packages
        $this->actingAs($this->customerUser);
        $ownConsolidated = $this->consolidationService->getActiveConsolidatedPackagesForCustomer(
            $this->customerUser->id,
            $this->customerUser
        );
        $this->assertEquals(1, $ownConsolidated->count());

        // Customer cannot access other customer's consolidated packages
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->consolidationService->getActiveConsolidatedPackagesForCustomer(
            $this->otherCustomerUser->id,
            $this->customerUser
        );
    }

    /** @test */
    public function permission_checks_are_consistent_across_all_methods()
    {
        // Create test data
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $this->actingAs($this->adminUser);
        $result = $this->consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Test all methods with customer user (should have limited access)
        $this->actingAs($this->customerUser);

        // Methods that should work for customers (viewing their own data)
        $history = $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            $this->customerUser
        );
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $history);

        $summary = $this->consolidationService->getConsolidationHistorySummary(
            $consolidatedPackage,
            $this->customerUser
        );
        $this->assertIsArray($summary);

        $auditTrail = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            $this->customerUser
        );
        $this->assertIsArray($auditTrail);

        // Methods that should NOT work for customers
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            'ready',
            $this->customerUser
        );
    }
}