<?php

namespace Tests\Unit;

use App\Enums\PackageStatus;
use App\Models\ConsolidatedPackage;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageConsolidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_has_consolidated_package_relationship()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);
        
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true
        ]);

        $this->assertInstanceOf(ConsolidatedPackage::class, $package->consolidatedPackage);
        $this->assertEquals($consolidatedPackage->id, $package->consolidatedPackage->id);
    }

    /** @test */
    public function it_can_check_if_package_is_consolidated()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);
        
        // Consolidated package
        $consolidatedPkg = Package::factory()->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true
        ]);

        // Individual package
        $individualPkg = Package::factory()->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => null,
            'is_consolidated' => false
        ]);

        $this->assertTrue($consolidatedPkg->isConsolidated());
        $this->assertFalse($individualPkg->isConsolidated());
    }

    /** @test */
    public function it_can_check_if_package_can_be_consolidated()
    {
        // Package in allowed status
        $allowedPackage = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false,
            'consolidated_package_id' => null
        ]);

        // Package in disallowed status
        $disallowedPackage = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::DELIVERED,
            'is_consolidated' => false,
            'consolidated_package_id' => null
        ]);

        // Already consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);
        
        $alreadyConsolidated = Package::factory()->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true
        ]);

        $this->assertTrue($allowedPackage->canBeConsolidated());
        $this->assertFalse($disallowedPackage->canBeConsolidated());
        $this->assertFalse($alreadyConsolidated->canBeConsolidated());
    }

    /** @test */
    public function it_can_get_consolidated_group()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);
        
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true
        ]);

        $group = $package->getConsolidatedGroup();
        
        $this->assertInstanceOf(ConsolidatedPackage::class, $group);
        $this->assertEquals($consolidatedPackage->id, $group->id);
    }

    /** @test */
    public function it_can_scope_consolidated_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);
        
        // Create consolidated packages
        Package::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true
        ]);

        // Create individual packages
        Package::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => null,
            'is_consolidated' => false
        ]);

        $consolidatedPackages = Package::consolidated()->get();
        
        $this->assertCount(2, $consolidatedPackages);
        $consolidatedPackages->each(function ($package) {
            $this->assertTrue($package->is_consolidated);
            $this->assertNotNull($package->consolidated_package_id);
        });
    }

    /** @test */
    public function it_can_scope_individual_packages()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);
        
        // Create consolidated packages
        Package::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true
        ]);

        // Create individual packages
        Package::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => null,
            'is_consolidated' => false
        ]);

        $individualPackages = Package::individual()->get();
        
        $this->assertCount(3, $individualPackages);
        $individualPackages->each(function ($package) {
            $this->assertFalse($package->is_consolidated);
            $this->assertNull($package->consolidated_package_id);
        });
    }

    /** @test */
    public function it_can_scope_packages_available_for_consolidation()
    {
        // Create packages in allowed statuses
        $allowedStatuses = [
            PackageStatus::PENDING,
            PackageStatus::PROCESSING,
            PackageStatus::READY,
            PackageStatus::SHIPPED,
            PackageStatus::CUSTOMS
        ];

        foreach ($allowedStatuses as $status) {
            Package::factory()->create([
                'user_id' => $this->user->id,
                'status' => $status,
                'is_consolidated' => false,
                'consolidated_package_id' => null
            ]);
        }

        // Create packages in disallowed statuses
        Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::DELIVERED,
            'is_consolidated' => false,
            'consolidated_package_id' => null
        ]);

        // Create already consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id
        ]);
        
        Package::factory()->create([
            'user_id' => $this->user->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true
        ]);

        $availablePackages = Package::availableForConsolidation()->get();
        
        $this->assertCount(5, $availablePackages); // Only the allowed status packages
        $availablePackages->each(function ($package) {
            $this->assertFalse($package->is_consolidated);
            $this->assertNull($package->consolidated_package_id);
            $this->assertTrue($package->canBeConsolidated());
        });
    }

    /** @test */
    public function it_handles_consolidation_status_check_with_different_statuses()
    {
        $testCases = [
            [PackageStatus::PENDING, true],
            [PackageStatus::PROCESSING, true],
            [PackageStatus::READY, true],
            [PackageStatus::SHIPPED, true],
            [PackageStatus::CUSTOMS, true],
            [PackageStatus::DELIVERED, false],
            [PackageStatus::DELAYED, false],
        ];

        foreach ($testCases as [$status, $expected]) {
            $package = Package::factory()->create([
                'user_id' => $this->user->id,
                'status' => $status,
                'is_consolidated' => false,
                'consolidated_package_id' => null
            ]);

            $statusValue = is_string($status) ? $status : $status->value;
            $this->assertEquals(
                $expected, 
                $package->canBeConsolidated(),
                "Package with status {$statusValue} should " . ($expected ? 'be able to' : 'not be able to') . " be consolidated"
            );
        }
    }
}