<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PackageStatusService;
use App\Services\PackageDistributionService;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageDeliveryRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected $packageStatusService;
    protected $user;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->packageStatusService = app(PackageStatusService::class);
        
        // Create a customer role and user
        $customerRole = Role::where('name', 'customer')->first();
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create an admin user for status updates
        $adminRole = Role::where('name', 'admin')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create a package in READY status
        $this->package = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::READY,
            'freight_price' => 50.00,
        ]);
    }

    /** @test */
    public function it_prevents_manual_update_to_delivered_status()
    {
        $result = $this->packageStatusService->updateStatus(
            $this->package,
            PackageStatus::DELIVERED(),
            $this->admin,
            'Attempting manual delivery update'
        );

        $this->assertFalse($result);
        
        // Verify package status hasn't changed
        $this->package->refresh();
        $this->assertEquals(PackageStatus::READY, $this->package->status);
    }

    /** @test */
    public function it_allows_delivered_status_through_distribution_process()
    {
        $result = $this->packageStatusService->markAsDeliveredThroughDistribution(
            $this->package,
            $this->admin,
            'Package delivered through distribution process'
        );

        $this->assertTrue($result);
        
        // Verify package status has changed to delivered
        $this->package->refresh();
        $this->assertEquals(PackageStatus::DELIVERED, $this->package->status);
    }

    /** @test */
    public function it_allows_other_status_updates_normally()
    {
        // Change package to PROCESSING first
        $this->package->update(['status' => PackageStatus::PROCESSING]);
        
        $result = $this->packageStatusService->updateStatus(
            $this->package,
            PackageStatus::SHIPPED(),
            $this->admin,
            'Normal status update'
        );

        $this->assertTrue($result);
        
        // Verify package status has changed
        $this->package->refresh();
        $this->assertEquals(PackageStatus::SHIPPED, $this->package->status);
    }

    /** @test */
    public function manual_update_cases_exclude_delivered_status()
    {
        $manualCases = PackageStatus::manualUpdateCases();
        $manualValues = collect($manualCases)->pluck('value')->toArray();
        
        $this->assertNotContains(PackageStatus::DELIVERED, $manualValues);
        $this->assertContains(PackageStatus::READY, $manualValues);
        $this->assertContains(PackageStatus::PROCESSING, $manualValues);
    }

    /** @test */
    public function all_cases_still_include_delivered_status()
    {
        $allCases = PackageStatus::cases();
        $allValues = collect($allCases)->pluck('value')->toArray();
        
        $this->assertContains(PackageStatus::DELIVERED, $allValues);
    }
}