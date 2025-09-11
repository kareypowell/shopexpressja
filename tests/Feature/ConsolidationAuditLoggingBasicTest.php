<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Models\Role;
use App\Services\PackageConsolidationService;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidationAuditLoggingBasicTest extends TestCase
{
    use RefreshDatabase;

    protected $consolidationService;
    protected $admin;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles manually instead of seeding
        Role::create(['id' => 1, 'name' => 'Admin', 'description' => 'Administrator']);
        Role::create(['id' => 2, 'name' => 'Customer', 'description' => 'Customer']);
        
        $this->consolidationService = app(PackageConsolidationService::class);
        $adminRole = Role::where('name', 'superadmin')->first();
        $customerRole = Role::where('name', 'customer')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function it_logs_consolidation_action_when_packages_are_consolidated()
    {
        // Create packages for consolidation
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::PROCESSING,
            'weight' => 5.0,
            'freight_price' => 25.00,
            'customs_duty' => 0,
            'storage_fee' => 0,
            'delivery_fee' => 0,
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        // Consolidate packages
        $result = $this->consolidationService->consolidatePackages(
            $packageIds,
            $this->admin,
            ['notes' => 'Test consolidation']
        );

        $this->assertTrue($result['success']);
        $consolidatedPackage = $result['consolidated_package'];

        // Assert history record was created
        $this->assertDatabaseHas('consolidation_history', [
            'consolidated_package_id' => $consolidatedPackage->id,
            'action' => 'consolidated',
            'performed_by' => $this->admin->id,
        ]);

        // Get the history record and check details
        $historyRecord = ConsolidationHistory::where('consolidated_package_id', $consolidatedPackage->id)
            ->where('action', 'consolidated')
            ->first();

        $this->assertNotNull($historyRecord);
        $this->assertEquals($packageIds, $historyRecord->details['package_ids']);
        $this->assertEquals(2, $historyRecord->details['package_count']);
        $this->assertEquals(10.0, $historyRecord->details['total_weight']);
        $this->assertEquals(50.0, $historyRecord->details['total_cost']);
    }

    /** @test */
    public function it_can_retrieve_consolidation_history()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create();

        // Create multiple history records
        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(5),
        ]);

        ConsolidationHistory::factory()->statusChanged()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(3),
        ]);

        ConsolidationHistory::factory()->unconsolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_at' => now()->subDays(1),
        ]);

        // Get history
        $history = $this->consolidationService->getConsolidationHistory($consolidatedPackage);

        $this->assertCount(3, $history);
        
        // Should be ordered by performed_at desc
        $this->assertEquals('unconsolidated', $history->first()->action);
        $this->assertEquals('consolidated', $history->last()->action);
    }

    /** @test */
    public function it_can_export_consolidation_audit_trail_as_array()
    {
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        ConsolidationHistory::factory()->consolidated()->create([
            'consolidated_package_id' => $consolidatedPackage->id,
            'performed_by' => $this->admin->id,
        ]);

        $exportData = $this->consolidationService->exportConsolidationAuditTrail(
            $consolidatedPackage,
            'array'
        );

        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('export_generated_at', $exportData);
        $this->assertArrayHasKey('consolidated_package', $exportData);
        $this->assertArrayHasKey('audit_trail', $exportData);

        $packageInfo = $exportData['consolidated_package'];
        $this->assertEquals($consolidatedPackage->id, $packageInfo['consolidated_package_id']);
        $this->assertEquals($this->customer->name, $packageInfo['customer_name']);

        $this->assertCount(1, $exportData['audit_trail']);
        $auditRecord = $exportData['audit_trail'][0];
        $this->assertEquals('consolidated', $auditRecord['action']);
        $this->assertEquals($this->admin->name, $auditRecord['performed_by_name']);
    }
}