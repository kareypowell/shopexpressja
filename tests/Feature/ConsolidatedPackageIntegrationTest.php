<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Enums\PackageStatus;
use App\Services\PackageConsolidationService;
use App\Services\PackageDistributionService;
use App\Services\PackageNotificationService;
use Database\Seeders\ConsolidatedPackageTestDataSeeder;
use Illuminate\Support\Facades\Notification;

class ConsolidatedPackageIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PackageConsolidationService $consolidationService;
    private PackageDistributionService $distributionService;
    private PackageNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->consolidationService = app(PackageConsolidationService::class);
        $this->distributionService = app(PackageDistributionService::class);
        $this->notificationService = app(PackageNotificationService::class);
        
        // Seed test data
        $this->seed([
            \Database\Seeders\RolesTableSeeder::class,
            \Database\Seeders\OfficesTableSeeder::class,
            \Database\Seeders\ShippersTableSeeder::class,
            \Database\Seeders\ManifestsTableSeeder::class,
            ConsolidatedPackageTestDataSeeder::class,
        ]);
    }

    /** @test */
    public function it_can_consolidate_packages_using_seeded_data()
    {
        // Get customer with packages ready for consolidation
        $customer = User::where('email', 'like', 'consolidation.ready.%@test.com')->first();
        $this->assertNotNull($customer);

        // Get packages ready for consolidation
        $packages = Package::where('user_id', $customer->id)
            ->where('status', PackageStatus::READY)
            ->where('is_consolidated', false)
            ->get();

        $this->assertGreaterThanOrEqual(3, $packages->count());

        // Get admin user
        $admin = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->first();

        // Consolidate packages
        $packageIds = $packages->pluck('id')->toArray();
        $consolidatedPackage = $this->consolidationService->consolidatePackages(
            $packageIds,
            $admin,
            ['notes' => 'Integration test consolidation']
        );

        // Verify consolidation
        $this->assertInstanceOf(ConsolidatedPackage::class, $consolidatedPackage);
        $this->assertEquals($customer->id, $consolidatedPackage->customer_id);
        $this->assertEquals($admin->id, $consolidatedPackage->created_by);
        $this->assertTrue($consolidatedPackage->is_active);

        // Verify packages are updated
        $packages->each(function ($package) use ($consolidatedPackage) {
            $package->refresh();
            $this->assertTrue($package->is_consolidated);
            $this->assertEquals($consolidatedPackage->id, $package->consolidated_package_id);
        });

        // Verify totals are calculated correctly
        $expectedWeight = $packages->sum('weight');
        $expectedQuantity = $packages->count();
        $expectedFreight = $packages->sum('freight_price');
        $expectedCustoms = $packages->sum('customs_duty');
        $expectedStorage = $packages->sum('storage_fee');
        $expectedDelivery = $packages->sum('delivery_fee');

        $this->assertEquals($expectedWeight, $consolidatedPackage->total_weight);
        $this->assertEquals($expectedQuantity, $consolidatedPackage->total_quantity);
        $this->assertEquals($expectedFreight, $consolidatedPackage->total_freight_price);
        $this->assertEquals($expectedCustoms, $consolidatedPackage->total_customs_duty);
        $this->assertEquals($expectedStorage, $consolidatedPackage->total_storage_fee);
        $this->assertEquals($expectedDelivery, $consolidatedPackage->total_delivery_fee);

        // Verify consolidation history is created
        $history = ConsolidationHistory::where('consolidated_package_id', $consolidatedPackage->id)
            ->where('action', 'consolidated')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals($admin->id, $history->performed_by);
        $this->assertEquals($packages->count(), $history->details['package_count']);
    }

    /** @test */
    public function it_can_distribute_consolidated_packages_using_seeded_data()
    {
        // Get customer with existing consolidated packages
        $customer = User::where('email', 'like', 'has.consolidated.%@test.com')->first();
        $this->assertNotNull($customer);

        // Get a consolidated package ready for distribution
        $consolidatedPackage = ConsolidatedPackage::where('customer_id', $customer->id)
            ->where('status', PackageStatus::READY)
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($consolidatedPackage);

        // Get admin user
        $admin = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->first();

        // Calculate total cost
        $totalCost = $consolidatedPackage->total_freight_price + 
                    $consolidatedPackage->total_customs_duty + 
                    $consolidatedPackage->total_storage_fee + 
                    $consolidatedPackage->total_delivery_fee;

        // Distribute consolidated package
        $distribution = $this->distributionService->distributeConsolidatedPackages(
            $consolidatedPackage,
            $totalCost + 10, // Overpayment
            $admin,
            [],
            ['notes' => 'Integration test distribution']
        );

        // Verify distribution
        $this->assertNotNull($distribution);
        $this->assertEquals($customer->id, $distribution->customer_id);
        $this->assertEquals($admin->id, $distribution->distributed_by);
        $this->assertEquals($totalCost, $distribution->total_amount);
        $this->assertEquals($totalCost + 10, $distribution->amount_collected);

        // Verify consolidated package status is updated
        $consolidatedPackage->refresh();
        $this->assertEquals(PackageStatus::DELIVERED, $consolidatedPackage->status);

        // Verify individual packages are updated
        $consolidatedPackage->packages->each(function ($package) {
            $this->assertEquals(PackageStatus::DELIVERED, $package->status);
        });

        // Verify distribution items are created
        $this->assertEquals($consolidatedPackage->packages->count(), $distribution->items->count());

        // Verify customer balance is updated with overpayment
        $customer->refresh();
        $this->assertEquals(10.00, $customer->credit_balance);
    }

    /** @test */
    public function it_can_unconsolidate_packages_using_seeded_data()
    {
        // Get customer with consolidation ready for unconsolidation
        $customer = User::where('email', 'like', 'unconsolidation.test.%@test.com')->first();
        $this->assertNotNull($customer);

        // Get consolidated package
        $consolidatedPackage = ConsolidatedPackage::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($consolidatedPackage);

        // Get packages in the consolidation
        $packages = $consolidatedPackage->packages;
        $this->assertGreaterThan(1, $packages->count());

        // Get admin user
        $admin = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->first();

        // Store original package data for verification
        $originalPackageData = $packages->map(function ($package) {
            return [
                'id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'weight' => $package->weight,
                'status' => $package->status,
                'freight_price' => $package->freight_price,
                'customs_duty' => $package->customs_duty,
                'storage_fee' => $package->storage_fee,
                'delivery_fee' => $package->delivery_fee,
            ];
        })->toArray();

        // Unconsolidate packages
        $unconsolidatedPackages = $this->consolidationService->unconsolidatePackages(
            $consolidatedPackage,
            $admin
        );

        // Verify unconsolidation
        $this->assertCount($packages->count(), $unconsolidatedPackages);

        // Verify consolidated package is marked inactive
        $consolidatedPackage->refresh();
        $this->assertFalse($consolidatedPackage->is_active);
        $this->assertNotNull($consolidatedPackage->unconsolidated_at);

        // Verify packages are restored to individual status
        foreach ($originalPackageData as $originalData) {
            $package = Package::find($originalData['id']);
            $this->assertFalse($package->is_consolidated);
            $this->assertNull($package->consolidated_package_id);
            $this->assertNull($package->consolidated_at);
            
            // Verify original data is preserved
            $this->assertEquals($originalData['tracking_number'], $package->tracking_number);
            $this->assertEquals($originalData['weight'], $package->weight);
            $this->assertEquals($originalData['freight_price'], $package->freight_price);
            $this->assertEquals($originalData['customs_duty'], $package->customs_duty);
            $this->assertEquals($originalData['storage_fee'], $package->storage_fee);
            $this->assertEquals($originalData['delivery_fee'], $package->delivery_fee);
        }

        // Verify unconsolidation history is created
        $history = ConsolidationHistory::where('consolidated_package_id', $consolidatedPackage->id)
            ->where('action', 'unconsolidated')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals($admin->id, $history->performed_by);
        $this->assertEquals($packages->count(), $history->details['package_count']);
    }

    /** @test */
    public function it_can_search_consolidated_packages_using_seeded_data()
    {
        // Get customer with mixed packages
        $customer = User::where('email', 'like', 'mixed.packages.%@test.com')->first();
        $this->assertNotNull($customer);

        // Get a consolidated package
        $consolidatedPackage = ConsolidatedPackage::where('customer_id', $customer->id)->first();
        $this->assertNotNull($consolidatedPackage);

        // Get one of the individual packages in the consolidation
        $individualPackage = $consolidatedPackage->packages->first();
        $this->assertNotNull($individualPackage);

        // Search for the individual package tracking number
        $searchResults = Package::where('user_id', $customer->id)
            ->where(function ($query) use ($individualPackage) {
                $query->where('tracking_number', 'like', '%' . $individualPackage->tracking_number . '%')
                      ->orWhereHas('consolidatedPackage', function ($subQuery) use ($individualPackage) {
                          $subQuery->whereHas('packages', function ($packageQuery) use ($individualPackage) {
                              $packageQuery->where('tracking_number', 'like', '%' . $individualPackage->tracking_number . '%');
                          });
                      });
            })
            ->get();

        // Verify search finds the package
        $this->assertTrue($searchResults->contains('id', $individualPackage->id));

        // Search by consolidated tracking number
        $consolidatedSearchResults = ConsolidatedPackage::where('customer_id', $customer->id)
            ->where('consolidated_tracking_number', 'like', '%' . $consolidatedPackage->consolidated_tracking_number . '%')
            ->get();

        $this->assertTrue($consolidatedSearchResults->contains('id', $consolidatedPackage->id));
    }

    /** @test */
    public function it_maintains_data_integrity_across_consolidation_operations()
    {
        // Get high volume customer
        $customer = User::where('email', 'like', 'highvolume.consolidation.%@test.com')->first();
        $this->assertNotNull($customer);

        // Get all consolidated packages for this customer
        $consolidatedPackages = ConsolidatedPackage::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->get();

        $this->assertGreaterThan(0, $consolidatedPackages->count());

        // Verify data integrity for each consolidation
        foreach ($consolidatedPackages as $consolidatedPackage) {
            $packages = $consolidatedPackage->packages;
            
            // Verify all packages belong to the same customer
            $packages->each(function ($package) use ($customer) {
                $this->assertEquals($customer->id, $package->user_id);
                $this->assertTrue($package->is_consolidated);
                $this->assertNotNull($package->consolidated_at);
            });

            // Verify calculated totals match individual package sums
            $calculatedWeight = $packages->sum('weight');
            $calculatedQuantity = $packages->count();
            $calculatedFreight = $packages->sum('freight_price');
            $calculatedCustoms = $packages->sum('customs_duty');
            $calculatedStorage = $packages->sum('storage_fee');
            $calculatedDelivery = $packages->sum('delivery_fee');

            $this->assertEquals($calculatedWeight, $consolidatedPackage->total_weight);
            $this->assertEquals($calculatedQuantity, $consolidatedPackage->total_quantity);
            $this->assertEquals($calculatedFreight, $consolidatedPackage->total_freight_price);
            $this->assertEquals($calculatedCustoms, $consolidatedPackage->total_customs_duty);
            $this->assertEquals($calculatedStorage, $consolidatedPackage->total_storage_fee);
            $this->assertEquals($calculatedDelivery, $consolidatedPackage->total_delivery_fee);

            // Verify consolidation history exists
            $history = ConsolidationHistory::where('consolidated_package_id', $consolidatedPackage->id)
                ->where('action', 'consolidated')
                ->first();

            $this->assertNotNull($history);
            $this->assertEquals($packages->count(), $history->details['package_count']);
        }
    }

    /** @test */
    public function it_handles_different_consolidation_statuses_correctly()
    {
        // Get status test customer
        $customer = User::where('email', 'like', 'status.test.%@test.com')->first();
        $this->assertNotNull($customer);

        // Get consolidated packages in different statuses
        $consolidatedPackages = ConsolidatedPackage::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->get();

        $this->assertGreaterThan(0, $consolidatedPackages->count());

        // Test each status scenario
        $statusesToTest = [
            PackageStatus::PROCESSING,
            PackageStatus::SHIPPED,
            PackageStatus::CUSTOMS,
            PackageStatus::READY,
        ];

        foreach ($statusesToTest as $status) {
            $consolidatedPackage = $consolidatedPackages->where('status', $status)->first();
            
            if ($consolidatedPackage) {
                // Verify all individual packages have the same status
                $consolidatedPackage->packages->each(function ($package) use ($status) {
                    $this->assertEquals($status, $package->status);
                });

                // Verify status-specific business rules
                if ($status === PackageStatus::READY) {
                    // Ready packages should have all fees calculated
                    $this->assertGreaterThan(0, $consolidatedPackage->total_customs_duty);
                    $this->assertGreaterThan(0, $consolidatedPackage->total_storage_fee);
                    $this->assertGreaterThan(0, $consolidatedPackage->total_delivery_fee);
                } else {
                    // Non-ready packages may have zero fees
                    $consolidatedPackage->packages->each(function ($package) {
                        if ($package->status !== PackageStatus::READY) {
                            $this->assertEquals(0, $package->customs_duty);
                            $this->assertEquals(0, $package->storage_fee);
                            $this->assertEquals(0, $package->delivery_fee);
                        }
                    });
                }
            }
        }
    }

    /** @test */
    public function it_can_handle_historical_consolidation_data()
    {
        // Get historical customer
        $customer = User::where('email', 'like', 'historical.consolidation.%@test.com')->first();
        $this->assertNotNull($customer);

        // Get historical consolidated package
        $historicalConsolidation = ConsolidatedPackage::where('customer_id', $customer->id)
            ->where('status', PackageStatus::DELIVERED)
            ->first();

        $this->assertNotNull($historicalConsolidation);

        // Verify historical data integrity
        $this->assertEquals(PackageStatus::DELIVERED, $historicalConsolidation->status);
        $this->assertTrue($historicalConsolidation->is_active);

        // Verify individual packages are also delivered
        $historicalConsolidation->packages->each(function ($package) {
            $this->assertEquals(PackageStatus::DELIVERED, $package->status);
            $this->assertTrue($package->is_consolidated);
        });

        // Verify consolidation history exists
        $consolidationHistory = ConsolidationHistory::where('consolidated_package_id', $historicalConsolidation->id)
            ->orderBy('performed_at')
            ->get();

        $this->assertGreaterThan(0, $consolidationHistory->count());

        // Verify consolidation action exists
        $consolidationAction = $consolidationHistory->where('action', 'consolidated')->first();
        $this->assertNotNull($consolidationAction);

        // Verify status change action exists
        $statusChangeAction = $consolidationHistory->where('action', 'status_changed')->first();
        $this->assertNotNull($statusChangeAction);
        $this->assertEquals(PackageStatus::DELIVERED, $statusChangeAction->details['new_status']);

        // Verify distribution record exists
        $distribution = \App\Models\PackageDistribution::where('customer_id', $customer->id)->first();
        $this->assertNotNull($distribution);
        $this->assertEquals('paid', $distribution->payment_status);
        $this->assertTrue($distribution->email_sent);
    }

    /** @test */
    public function it_maintains_audit_trail_for_all_consolidation_operations()
    {
        // Get all consolidation history records
        $allHistory = ConsolidationHistory::all();
        $this->assertGreaterThan(0, $allHistory->count());

        // Verify each history record has required data
        $allHistory->each(function ($history) {
            $this->assertNotNull($history->consolidated_package_id);
            $this->assertNotNull($history->action);
            $this->assertNotNull($history->performed_by);
            $this->assertNotNull($history->performed_at);
            $this->assertIsArray($history->details);

            // Verify action-specific details
            switch ($history->action) {
                case 'consolidated':
                    $this->assertArrayHasKey('package_count', $history->details);
                    $this->assertArrayHasKey('total_weight', $history->details);
                    $this->assertArrayHasKey('total_cost', $history->details);
                    $this->assertArrayHasKey('package_ids', $history->details);
                    break;

                case 'unconsolidated':
                    $this->assertArrayHasKey('package_count', $history->details);
                    $this->assertArrayHasKey('package_ids', $history->details);
                    break;

                case 'status_changed':
                    $this->assertArrayHasKey('old_status', $history->details);
                    $this->assertArrayHasKey('new_status', $history->details);
                    $this->assertArrayHasKey('package_count', $history->details);
                    break;
            }

            // Verify consolidated package exists
            $consolidatedPackage = ConsolidatedPackage::find($history->consolidated_package_id);
            $this->assertNotNull($consolidatedPackage);

            // Verify user exists
            $user = User::find($history->performed_by);
            $this->assertNotNull($user);
        });
    }
}