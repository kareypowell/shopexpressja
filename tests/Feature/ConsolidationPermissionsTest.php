<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Services\PackageConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;

class ConsolidationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdminUser;
    protected $adminUser;
    protected $customerUser;
    protected $otherCustomerUser;
    protected $consolidationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles using the seeder structure
        $superAdminRole = Role::create(['id' => 1, 'name' => 'superadmin', 'description' => 'Super Administrator']);
        $adminRole = Role::create(['id' => 2, 'name' => 'admin', 'description' => 'Administrator']);
        $customerRole = Role::create(['id' => 3, 'name' => 'customer', 'description' => 'Customer']);

        // Create users
        $this->superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
        $this->otherCustomerUser = User::factory()->create(['role_id' => $customerRole->id]);

        $this->consolidationService = app(PackageConsolidationService::class);
    }

    /** @test */
    public function superadmin_can_consolidate_packages()
    {
        // Create packages for a customer
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        $result = $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->superAdminUser
        );

        if (!$result['success']) {
            $this->fail('Consolidation failed: ' . $result['message']);
        }

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(ConsolidatedPackage::class, $result['consolidated_package']);
    }

    /** @test */
    public function admin_can_consolidate_packages()
    {
        // Create packages for a customer
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        $result = $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->adminUser
        );

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(ConsolidatedPackage::class, $result['consolidated_package']);
    }

    /** @test */
    public function customer_cannot_consolidate_packages()
    {
        // Create packages for the customer
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to consolidate packages.');

        $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->customerUser
        );
    }

    /** @test */
    public function admin_cannot_consolidate_packages_they_dont_have_access_to()
    {
        // Create packages for different customers
        $package1 = Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $this->otherCustomerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = [$package1->id, $package2->id];

        $result = $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->adminUser
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('same customer', $result['message']);
    }

    /** @test */
    public function admin_can_unconsolidate_packages()
    {
        // Create a consolidated package
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        // Associate packages with consolidated package
        foreach ($packages as $package) {
            $package->update([
                'consolidated_package_id' => $consolidatedPackage->id,
                'is_consolidated' => true
            ]);
        }

        $result = $this->consolidationService->unconsolidatePackages(
            $consolidatedPackage,
            $this->adminUser
        );

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function customer_cannot_unconsolidate_packages()
    {
        // Create a consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to unconsolidate this package.');

        $this->consolidationService->unconsolidatePackages(
            $consolidatedPackage,
            $this->customerUser
        );
    }

    /** @test */
    public function customer_cannot_unconsolidate_other_customers_packages()
    {
        // Create a consolidated package for another customer
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->otherCustomerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to unconsolidate this package.');

        $this->consolidationService->unconsolidatePackages(
            $consolidatedPackage,
            $this->customerUser
        );
    }

    /** @test */
    public function superadmin_can_update_consolidated_package_status()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id,
            'status' => 'processing'
        ]);

        $result = $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            'ready',
            $this->superAdminUser
        );

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function admin_can_update_consolidated_package_status()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id,
            'status' => 'processing'
        ]);

        $result = $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            'ready',
            $this->adminUser
        );

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function customer_cannot_update_consolidated_package_status()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id,
            'status' => 'processing'
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to update this consolidated package.');

        $this->consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            'ready',
            $this->customerUser
        );
    }

    /** @test */
    public function admin_can_view_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        $history = $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            $this->adminUser
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $history);
    }

    /** @test */
    public function customer_can_view_their_own_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        $history = $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            $this->customerUser
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $history);
    }

    /** @test */
    public function customer_cannot_view_other_customers_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->otherCustomerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to view consolidation history for this package.');

        $this->consolidationService->getConsolidationHistory(
            $consolidatedPackage,
            $this->customerUser
        );
    }

    /** @test */
    public function admin_can_get_available_packages_for_any_customer()
    {
        Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packages = $this->consolidationService->getAvailablePackagesForCustomer(
            $this->customerUser->id,
            $this->adminUser
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $packages);
    }

    /** @test */
    public function customer_can_get_their_own_available_packages()
    {
        Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packages = $this->consolidationService->getAvailablePackagesForCustomer(
            $this->customerUser->id,
            $this->customerUser
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $packages);
    }

    /** @test */
    public function customer_cannot_get_other_customers_available_packages()
    {
        Package::factory()->count(3)->create([
            'user_id' => $this->otherCustomerUser->id,
            'status' => 'processing'
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to view packages for this customer.');

        $this->consolidationService->getAvailablePackagesForCustomer(
            $this->otherCustomerUser->id,
            $this->customerUser
        );
    }

    /** @test */
    public function admin_can_export_audit_trail()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        $auditTrail = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            $this->adminUser
        );

        $this->assertIsArray($auditTrail);
        $this->assertArrayHasKey('consolidated_package', $auditTrail);
        $this->assertArrayHasKey('audit_trail', $auditTrail);
    }

    /** @test */
    public function customer_can_export_their_own_audit_trail()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        $auditTrail = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            $this->customerUser
        );

        $this->assertIsArray($auditTrail);
        $this->assertArrayHasKey('consolidated_package', $auditTrail);
        $this->assertArrayHasKey('audit_trail', $auditTrail);
    }

    /** @test */
    public function customer_cannot_export_other_customers_audit_trail()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->otherCustomerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to export audit trail for this package.');

        $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            $this->customerUser
        );
    }

    /** @test */
    public function unauthenticated_user_cannot_access_consolidation_features()
    {
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        // Create a role with no permissions and a user with that role
        $noPermissionRole = Role::create(['id' => 99, 'name' => 'no_permission', 'description' => 'No Permission Role']);
        $unauthenticatedUser = User::factory()->create(['role_id' => $noPermissionRole->id]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You do not have permission to consolidate packages.');

        $this->consolidationService->consolidatePackages(
            $packageIds,
            $unauthenticatedUser
        );
    }

    /** @test */
    public function consolidation_service_validates_user_permissions_for_each_package()
    {
        // Create packages for different customers
        $customerPackage = Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);
        
        $otherCustomerPackage = Package::factory()->create([
            'user_id' => $this->otherCustomerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = [$customerPackage->id, $otherCustomerPackage->id];

        // Even admin should not be able to consolidate packages from different customers
        $result = $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->adminUser
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('same customer', $result['message']);
    }

    /** @test */
    public function consolidation_history_summary_respects_permissions()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        // Admin can access history summary
        $summary = $this->consolidationService->getConsolidationHistorySummary(
            $consolidatedPackage,
            $this->adminUser
        );
        $this->assertIsArray($summary);

        // Customer can access their own history summary
        $summary = $this->consolidationService->getConsolidationHistorySummary(
            $consolidatedPackage,
            $this->customerUser
        );
        $this->assertIsArray($summary);

        // Other customer cannot access history summary
        $this->expectException(AuthorizationException::class);
        $this->consolidationService->getConsolidationHistorySummary(
            $consolidatedPackage,
            $this->otherCustomerUser
        );
    }
}