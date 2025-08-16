<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Models\User;
use App\Models\Manifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageModelEnhancedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->manifest = Manifest::factory()->create();
    }

    /** @test */
    public function it_returns_weight_in_pounds()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 25.5
        ]);
        
        $this->assertEquals(25.5, $package->getWeightInLbs());
    }

    /** @test */
    public function it_returns_zero_weight_when_weight_is_zero()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 0
        ]);
        
        $this->assertEquals(0.0, $package->getWeightInLbs());
    }

    /** @test */
    public function it_converts_weight_to_kilograms_correctly()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 10.0 // 10 lbs should be ~4.54 kg
        ]);
        
        $this->assertEquals(4.54, $package->getWeightInKg());
    }

    /** @test */
    public function it_rounds_weight_in_kg_to_two_decimal_places()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 7.3 // Should be 3.311... kg, rounded to 3.31
        ]);
        
        $this->assertEquals(3.31, $package->getWeightInKg());
    }

    /** @test */
    public function it_returns_zero_kg_when_weight_is_zero()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 0
        ]);
        
        $this->assertEquals(0.0, $package->getWeightInKg());
    }

    /** @test */
    public function it_returns_volume_from_cubic_feet_field()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => 5.25
        ]);
        
        $this->assertEquals(5.25, $package->getVolumeInCubicFeet());
    }

    /** @test */
    public function it_calculates_volume_from_dimensions_when_cubic_feet_is_null()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'length_inches' => 12,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);
        
        // 12 x 12 x 12 = 1728 cubic inches = 1 cubic foot
        $this->assertEquals(1.0, $package->getVolumeInCubicFeet());
    }

    /** @test */
    public function it_calculates_volume_from_dimensions_when_cubic_feet_is_zero()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'length_inches' => 24,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => 0
        ]);
        
        // 24 x 12 x 12 = 3456 cubic inches = 2 cubic feet
        $this->assertEquals(2.0, $package->getVolumeInCubicFeet());
    }

    /** @test */
    public function it_prefers_cubic_feet_over_calculated_dimensions()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'length_inches' => 12,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => 3.5 // This should be used instead of calculated 1.0
        ]);
        
        $this->assertEquals(3.5, $package->getVolumeInCubicFeet());
    }

    /** @test */
    public function it_returns_zero_volume_when_no_volume_data_available()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => null,
            'length_inches' => null,
            'width_inches' => null,
            'height_inches' => null
        ]);
        
        $this->assertEquals(0.0, $package->getVolumeInCubicFeet());
    }

    /** @test */
    public function it_detects_weight_data_presence_correctly()
    {
        $packageWithWeight = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 10.5
        ]);
        
        $packageWithZeroWeight = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 0
        ]);
        
        $this->assertTrue($packageWithWeight->hasWeightData());
        $this->assertFalse($packageWithZeroWeight->hasWeightData());
    }

    /** @test */
    public function it_detects_volume_data_from_cubic_feet()
    {
        $packageWithCubicFeet = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => 2.5
        ]);
        
        $packageWithZeroCubicFeet = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => 0,
            'length_inches' => null,
            'width_inches' => null,
            'height_inches' => null
        ]);
        
        $packageWithNullCubicFeet = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => null,
            'length_inches' => null,
            'width_inches' => null,
            'height_inches' => null
        ]);
        
        $this->assertTrue($packageWithCubicFeet->hasVolumeData());
        $this->assertFalse($packageWithZeroCubicFeet->hasVolumeData());
        $this->assertFalse($packageWithNullCubicFeet->hasVolumeData());
    }

    /** @test */
    public function it_detects_volume_data_from_complete_dimensions()
    {
        $packageWithCompleteDimensions = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => null,
            'length_inches' => 12,
            'width_inches' => 10,
            'height_inches' => 8
        ]);
        
        $this->assertTrue($packageWithCompleteDimensions->hasVolumeData());
    }

    /** @test */
    public function it_detects_missing_volume_data_from_incomplete_dimensions()
    {
        $packageMissingLength = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => null,
            'length_inches' => null,
            'width_inches' => 10,
            'height_inches' => 8
        ]);
        
        $packageMissingWidth = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => null,
            'length_inches' => 12,
            'width_inches' => null,
            'height_inches' => 8
        ]);
        
        $packageMissingHeight = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => null,
            'length_inches' => 12,
            'width_inches' => 10,
            'height_inches' => null
        ]);
        
        $packageWithZeroDimensions = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => null,
            'length_inches' => 0,
            'width_inches' => 10,
            'height_inches' => 8
        ]);
        
        $this->assertFalse($packageMissingLength->hasVolumeData());
        $this->assertFalse($packageMissingWidth->hasVolumeData());
        $this->assertFalse($packageMissingHeight->hasVolumeData());
        $this->assertFalse($packageWithZeroDimensions->hasVolumeData());
    }

    /** @test */
    public function it_prioritizes_cubic_feet_over_dimensions_for_volume_data_detection()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'cubic_feet' => 5.0,
            'length_inches' => null, // Missing dimensions
            'width_inches' => null,
            'height_inches' => null
        ]);
        
        $this->assertTrue($package->hasVolumeData());
        $this->assertEquals(5.0, $package->getVolumeInCubicFeet());
    }

    /** @test */
    public function it_handles_edge_case_weight_conversions()
    {
        // Test very small weight
        $smallPackage = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 0.1
        ]);
        
        $this->assertEquals(0.05, $smallPackage->getWeightInKg());
        
        // Test large weight
        $largePackage = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 1000.0
        ]);
        
        $this->assertEquals(453.59, $largePackage->getWeightInKg());
    }

    /** @test */
    public function it_handles_edge_case_volume_calculations()
    {
        // Test very small dimensions
        $smallPackage = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'length_inches' => 1,
            'width_inches' => 1,
            'height_inches' => 1,
            'cubic_feet' => null
        ]);
        
        $this->assertEquals(0.001, $smallPackage->getVolumeInCubicFeet());
        
        // Test large dimensions
        $largePackage = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'length_inches' => 36,
            'width_inches' => 24,
            'height_inches' => 24,
            'cubic_feet' => null
        ]);
        
        // 36 x 24 x 24 = 20736 cubic inches = 12 cubic feet
        $this->assertEquals(12.0, $largePackage->getVolumeInCubicFeet());
    }

    /** @test */
    public function it_validates_data_consistency_between_methods()
    {
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'manifest_id' => $this->manifest->id,
            'weight' => 22.05, // Approximately 10 kg
            'cubic_feet' => 2.5,
            'length_inches' => 12,
            'width_inches' => 12,
            'height_inches' => 12
        ]);
        
        // Weight methods should be consistent
        $this->assertEquals(22.05, $package->getWeightInLbs());
        $this->assertEquals(10.0, $package->getWeightInKg());
        $this->assertTrue($package->hasWeightData());
        
        // Volume methods should prioritize cubic_feet
        $this->assertEquals(2.5, $package->getVolumeInCubicFeet());
        $this->assertTrue($package->hasVolumeData());
    }
}