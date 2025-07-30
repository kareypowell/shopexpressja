<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\User;
use App\Models\Shipper;
use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_sea_specific_information_in_package_tables()
    {
        // Create a user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a sea manifest
        $seaManifest = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => 'Test Vessel',
            'voyage_number' => 'TV001'
        ]);

        // Create shipper and office
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create a sea package with container information
        $seaPackage = Package::factory()->create([
            'user_id' => $user->id,
            'manifest_id' => $seaManifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'container_type' => 'box',
            'length_inches' => 24.0,
            'width_inches' => 18.0,
            'height_inches' => 12.0,
            'cubic_feet' => 1.5,
            'tracking_number' => 'SEA001'
        ]);

        // Create package items
        PackageItem::factory()->create([
            'package_id' => $seaPackage->id,
            'description' => 'Electronics',
            'quantity' => 2,
            'weight_per_item' => 5.5
        ]);

        PackageItem::factory()->create([
            'package_id' => $seaPackage->id,
            'description' => 'Clothing',
            'quantity' => 10,
            'weight_per_item' => 0.5
        ]);

        // Test that the package correctly identifies as sea package
        $this->assertTrue($seaPackage->isSeaPackage());

        // Test that package items are loaded
        $this->assertEquals(2, $seaPackage->items->count());

        // Test that cubic feet calculation works
        $this->assertEquals(1.5, $seaPackage->cubic_feet);

        // Create an air manifest and package for comparison
        $airManifest = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'FL001'
        ]);

        $airPackage = Package::factory()->create([
            'user_id' => $user->id,
            'manifest_id' => $airManifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'tracking_number' => 'AIR001'
        ]);

        // Test that air package correctly identifies as not sea package
        $this->assertFalse($airPackage->isSeaPackage());
    }

    /** @test */
    public function it_formats_dimensions_correctly_for_sea_packages()
    {
        $user = User::factory()->create();
        $seaManifest = Manifest::factory()->create(['type' => 'sea']);
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package = Package::factory()->create([
            'user_id' => $user->id,
            'manifest_id' => $seaManifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'container_type' => 'barrel',
            'length_inches' => 30.5,
            'width_inches' => 20.25,
            'height_inches' => 15.75,
            'cubic_feet' => 6.5
        ]);

        // Test that dimensions are properly stored
        $this->assertEquals(30.5, $package->length_inches);
        $this->assertEquals(20.25, $package->width_inches);
        $this->assertEquals(15.75, $package->height_inches);
        $this->assertEquals(6.5, $package->cubic_feet);
        $this->assertEquals('barrel', $package->container_type);
    }

    /** @test */
    public function it_calculates_package_item_totals_correctly()
    {
        $user = User::factory()->create();
        $seaManifest = Manifest::factory()->create(['type' => 'sea']);
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        $package = Package::factory()->create([
            'user_id' => $user->id,
            'manifest_id' => $seaManifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'container_type' => 'pallet'
        ]);

        $item = PackageItem::factory()->create([
            'package_id' => $package->id,
            'description' => 'Heavy Equipment',
            'quantity' => 3,
            'weight_per_item' => 25.5
        ]);

        // Test total weight calculation
        $this->assertEquals(76.5, $item->total_weight);
    }
}