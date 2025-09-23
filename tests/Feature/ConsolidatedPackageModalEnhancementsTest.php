<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Http\Livewire\Customers\CustomerPackagesWithModal;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidatedPackageModalEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consolidated_by_shows_admin_user_name()
    {
        // Create admin user
        $adminRole = Role::where('name', 'superadmin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'first_name' => 'John',
            'last_name' => 'Admin',
            'full_name' => 'John Admin'
        ]);
        
        // Create customer
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'consolidated_tracking_number' => 'CONS-ADMIN-TEST'
        ]);
        
        // Act as customer and test modal
        $this->actingAs($customer);
        
        $modalComponent = new CustomerPackagesWithModal();
        $modalComponent->mount($customer);
        $modalComponent->showConsolidatedPackageDetails($consolidatedPackage->id);
        
        $this->assertTrue($modalComponent->showModal);
        $this->assertTrue($modalComponent->isConsolidatedPackage);
        $this->assertNotNull($modalComponent->selectedConsolidatedPackage);
        $this->assertNotNull($modalComponent->selectedConsolidatedPackage->createdBy);
        $this->assertEquals('John Admin', $modalComponent->selectedConsolidatedPackage->createdBy->full_name);
    }
    
    public function test_shipping_information_matches_individual_package_format()
    {
        // Create customer
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create manifest, shipper, and office
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $shipper = Shipper::factory()->create(['name' => 'Test Shipper']);
        $office = Office::factory()->create(['name' => 'Test Office']);
        
        // Create individual package
        $package = Package::factory()->create([
            'user_id' => $customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'container_type' => 'air',
            'weight' => 25.5,
            'cubic_feet' => 2.5,
            'estimated_value' => 100.00
        ]);
        
        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'total_weight' => 25.5
        ]);
        
        // Associate package with consolidated package
        $package->update(['consolidated_package_id' => $consolidatedPackage->id]);
        
        // Act as customer and test modal
        $this->actingAs($customer);
        
        $modalComponent = new CustomerPackagesWithModal();
        $modalComponent->mount($customer);
        $modalComponent->showConsolidatedPackageDetails($consolidatedPackage->id);
        
        $selectedPackage = $modalComponent->selectedConsolidatedPackage;
        $firstPackage = $selectedPackage->packages->first();
        
        // Verify shipping information format matches individual package format
        $this->assertEquals(25.5, $selectedPackage->total_weight);
        $this->assertEquals(100.00, $selectedPackage->packages->sum('estimated_value'));
        $this->assertEquals('Test Shipper', $firstPackage->shipper->name);
        $this->assertEquals('Test Office', $firstPackage->office->name);
        $this->assertEquals('air', $firstPackage->container_type);
        $this->assertEquals(2.5, $selectedPackage->packages->sum('cubic_feet'));
    }
    
    public function test_individual_package_breakdown_includes_freight_cost()
    {
        // Create customer
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create(['role_id' => $customerRole->id]);
        
        // Create manifest, shipper, and office
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();
        
        // Create individual packages with freight costs
        $package1 = Package::factory()->create([
            'user_id' => $customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'freight_price' => 150.00,
            'clearance_fee' => 50.00,
            'tracking_number' => 'PKG001'
        ]);
        
        $package2 = Package::factory()->create([
            'user_id' => $customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'freight_price' => 200.00,
            'clearance_fee' => 75.00,
            'tracking_number' => 'PKG002'
        ]);
        
        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'ready' // Ensure customer can see costs
        ]);
        
        // Associate packages with consolidated package
        $package1->update(['consolidated_package_id' => $consolidatedPackage->id]);
        $package2->update(['consolidated_package_id' => $consolidatedPackage->id]);
        
        // Act as customer and test modal
        $this->actingAs($customer);
        
        $modalComponent = new CustomerPackagesWithModal();
        $modalComponent->mount($customer);
        $modalComponent->showConsolidatedPackageDetails($consolidatedPackage->id);
        
        $selectedPackage = $modalComponent->selectedConsolidatedPackage;
        
        // Verify individual packages have freight costs
        $package1Fresh = $selectedPackage->packages->where('tracking_number', 'PKG001')->first();
        $package2Fresh = $selectedPackage->packages->where('tracking_number', 'PKG002')->first();
        
        $this->assertEquals(150.00, $package1Fresh->freight_price);
        $this->assertEquals(200.00, $package2Fresh->freight_price);
        $this->assertGreaterThan(0, $package1Fresh->total_cost);
        $this->assertGreaterThan(0, $package2Fresh->total_cost);
    }
}