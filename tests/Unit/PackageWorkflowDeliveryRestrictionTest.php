<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Manifests\PackageWorkflow;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use App\Models\Manifest;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class PackageWorkflowDeliveryRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $manifest;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and users
        $customerRole = Role::where('name', 'customer')->first();
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        
        $adminRole = Role::where('name', 'admin')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create manifest and package
        $this->manifest = Manifest::factory()->create();
        $this->package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'status' => PackageStatus::READY,
        ]);
    }

    /** @test */
    public function status_options_exclude_delivered()
    {
        $component = new PackageWorkflow();
        $statusOptions = $component->getStatusOptions();
        
        $this->assertArrayNotHasKey(PackageStatus::DELIVERED, $statusOptions);
        $this->assertArrayHasKey(PackageStatus::READY, $statusOptions);
        $this->assertArrayHasKey(PackageStatus::PROCESSING, $statusOptions);
    }

    /** @test */
    public function single_package_status_update_blocks_delivered()
    {
        $this->actingAs($this->admin);
        
        $originalStatus = $this->package->status;
        
        Livewire::test(PackageWorkflow::class, ['manifest' => $this->manifest->id])
            ->call('updateSinglePackageStatus', $this->package->id, PackageStatus::DELIVERED);
        
        // Verify package status hasn't changed (this is the key test)
        $this->package->refresh();
        $this->assertEquals($originalStatus, $this->package->status);
        $this->assertNotEquals(PackageStatus::DELIVERED, $this->package->status);
    }

    /** @test */
    public function bulk_status_update_blocks_delivered()
    {
        $this->actingAs($this->admin);
        
        $originalStatus = $this->package->status;
        
        Livewire::test(PackageWorkflow::class, ['manifest' => $this->manifest->id])
            ->set('confirmingStatus', PackageStatus::DELIVERED)
            ->set('confirmingPackages', [$this->package])
            ->call('executeBulkStatusUpdate');
        
        // Verify package status hasn't changed (this is the key test)
        $this->package->refresh();
        $this->assertEquals($originalStatus, $this->package->status);
        $this->assertNotEquals(PackageStatus::DELIVERED, $this->package->status);
    }

    /** @test */
    public function get_next_logical_status_excludes_delivered()
    {
        $component = new PackageWorkflow();
        
        // Test that READY status doesn't return DELIVERED as next status
        $nextStatus = $component->getNextLogicalStatus(PackageStatus::READY);
        
        $this->assertNull($nextStatus); // Should be null since DELIVERED is excluded
    }

    /** @test */
    public function normal_status_updates_still_work()
    {
        $this->actingAs($this->admin);
        
        // Change package to PROCESSING first
        $this->package->update(['status' => PackageStatus::PROCESSING]);
        
        $component = Livewire::test(PackageWorkflow::class, ['manifest' => $this->manifest->id])
            ->call('updateSinglePackageStatus', $this->package->id, PackageStatus::SHIPPED);
        
        $component->assertHasNoErrors();
        
        // Verify package status has changed
        $this->package->refresh();
        $this->assertEquals(PackageStatus::SHIPPED, $this->package->status);
    }
}