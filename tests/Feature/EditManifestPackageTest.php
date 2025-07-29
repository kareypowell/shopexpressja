<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Office;
use App\Models\Shipper;
use App\Models\Rate;
use App\Http\Livewire\Manifests\Packages\EditManifestPackage;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EditManifestPackageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();
        
        // Create sea rates for testing
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 0,
            'max_cubic_feet' => 10,
            'price' => 50.00,
            'processing_fee' => 10.00,
            'weight' => null
        ]);
    }

    /** @test */
    public function it_calculates_cubic_feet_correctly()
    {
        // Create a sea manifest
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        // Create a sea package
        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Test the component directly
        $component = new EditManifestPackage();
        $component->manifest_id = $manifest->id;
        $component->package_id = $package->id;
        $component->isSeaManifest = true;
        
        // Set dimensions
        $component->length_inches = 24;
        $component->width_inches = 12;
        $component->height_inches = 12;
        
        // Calculate cubic feet
        $component->calculateCubicFeet();
        
        // Cubic feet should be calculated: (24 * 12 * 12) / 1728 = 2.0
        $this->assertEquals(2.0, $component->cubic_feet);
    }

    /** @test */
    public function it_can_add_and_remove_items()
    {
        $component = new EditManifestPackage();
        $component->isSeaManifest = true;
        $component->items = [
            ['id' => null, 'description' => '', 'quantity' => 1, 'weight_per_item' => '']
        ];

        // Initially should have one item
        $this->assertCount(1, $component->items);

        // Add an item
        $component->addItem();
        $this->assertCount(2, $component->items);

        // Remove an item
        $component->removeItem(1);
        $this->assertCount(1, $component->items);
    }

    /** @test */
    public function it_determines_sea_manifest_correctly()
    {
        // Test sea manifest
        $seaManifest = Manifest::factory()->create(['type' => 'sea']);
        $component = new EditManifestPackage();
        $component->isSeaManifest = true;
        
        $this->assertTrue($component->isSeaManifest());
        
        // Test air manifest
        $component->isSeaManifest = false;
        $this->assertFalse($component->isSeaManifest());
    }

    /** @test */
    public function it_calculates_cubic_feet_with_zero_dimensions()
    {
        $component = new EditManifestPackage();
        $component->isSeaManifest = true;
        
        // Test with zero dimensions
        $component->length_inches = 0;
        $component->width_inches = 12;
        $component->height_inches = 12;
        
        $component->calculateCubicFeet();
        
        $this->assertEquals(0, $component->cubic_feet);
    }

    /** @test */
    public function it_updates_cubic_feet_when_dimensions_change()
    {
        $component = new EditManifestPackage();
        $component->isSeaManifest = true;
        
        // Set initial dimensions
        $component->length_inches = 12;
        $component->width_inches = 12;
        $component->height_inches = 12;
        
        // Test updatedLengthInches
        $component->updatedLengthInches();
        $this->assertEquals(1.0, $component->cubic_feet);
        
        // Change width and test updatedWidthInches
        $component->width_inches = 24;
        $component->updatedWidthInches();
        $this->assertEquals(2.0, $component->cubic_feet);
        
        // Change height and test updatedHeightInches
        $component->height_inches = 24;
        $component->updatedHeightInches();
        $this->assertEquals(4.0, $component->cubic_feet);
    }
}