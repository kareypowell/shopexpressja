<?php

namespace Tests\Unit;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for packages
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_returns_correct_manifest_type_for_air_manifest()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        $this->assertEquals('air', $manifest->getType());
    }

    /** @test */
    public function it_returns_correct_manifest_type_for_sea_manifest()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        $this->assertEquals('sea', $manifest->getType());
    }

    /** @test */
    public function it_defaults_to_air_type_when_type_is_empty_string()
    {
        $manifest = Manifest::factory()->create(['type' => '']);
        
        $this->assertEquals('air', $manifest->getType());
    }

    /** @test */
    public function it_calculates_total_weight_correctly()
    {
        $manifest = Manifest::factory()->create();
        
        // Create packages with different weights
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 10.5
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 25.3
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 5.2
        ]);
        
        $this->assertEquals(41.0, $manifest->getTotalWeight());
    }

    /** @test */
    public function it_excludes_packages_with_zero_weight_from_total_calculation()
    {
        $manifest = Manifest::factory()->create();
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 10.5
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 0
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 5.2
        ]);
        
        $this->assertEquals(15.7, $manifest->getTotalWeight());
    }

    /** @test */
    public function it_excludes_packages_with_zero_weight_from_total()
    {
        $manifest = Manifest::factory()->create();
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 10.5
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 0
        ]);
        
        $this->assertEquals(10.5, $manifest->getTotalWeight());
    }

    /** @test */
    public function it_returns_zero_weight_for_manifest_with_no_packages()
    {
        $manifest = Manifest::factory()->create();
        
        $this->assertEquals(0.0, $manifest->getTotalWeight());
    }

    /** @test */
    public function it_calculates_total_volume_from_cubic_feet()
    {
        $manifest = Manifest::factory()->create();
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'cubic_feet' => 2.5
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'cubic_feet' => 3.7
        ]);
        
        $this->assertEquals(6.2, $manifest->getTotalVolume());
    }

    /** @test */
    public function it_calculates_total_volume_from_dimensions()
    {
        $manifest = Manifest::factory()->create();
        
        // Package with dimensions: 12" x 12" x 12" = 1728 cubic inches = 1 cubic foot
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'length_inches' => 12,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);
        
        // Package with dimensions: 24" x 12" x 12" = 3456 cubic inches = 2 cubic feet
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'length_inches' => 24,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);
        
        $this->assertEquals(3.0, $manifest->getTotalVolume());
    }

    /** @test */
    public function it_returns_zero_volume_for_manifest_with_no_packages()
    {
        $manifest = Manifest::factory()->create();
        
        $this->assertEquals(0.0, $manifest->getTotalVolume());
    }

    /** @test */
    public function it_returns_true_for_complete_weight_data_when_all_packages_have_weight()
    {
        $manifest = Manifest::factory()->create();
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 10.5
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 5.2
        ]);
        
        $this->assertTrue($manifest->hasCompleteWeightData());
    }

    /** @test */
    public function it_returns_false_for_complete_weight_data_when_some_packages_have_zero_weight()
    {
        $manifest = Manifest::factory()->create();
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 10.5
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'weight' => 0
        ]);
        
        $this->assertFalse($manifest->hasCompleteWeightData());
    }

    /** @test */
    public function it_returns_true_for_complete_weight_data_when_no_packages_exist()
    {
        $manifest = Manifest::factory()->create();
        
        $this->assertTrue($manifest->hasCompleteWeightData());
    }

    /** @test */
    public function it_returns_true_for_complete_volume_data_when_all_packages_have_cubic_feet()
    {
        $manifest = Manifest::factory()->create();
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'cubic_feet' => 2.5
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'cubic_feet' => 3.7
        ]);
        
        $this->assertTrue($manifest->hasCompleteVolumeData());
    }

    /** @test */
    public function it_returns_true_for_complete_volume_data_when_all_packages_have_dimensions()
    {
        $manifest = Manifest::factory()->create();
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'length_inches' => 12,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'length_inches' => 24,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);
        
        $this->assertTrue($manifest->hasCompleteVolumeData());
    }

    /** @test */
    public function it_returns_false_for_complete_volume_data_when_some_packages_missing_volume_info()
    {
        $manifest = Manifest::factory()->create();
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'cubic_feet' => 2.5
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'cubic_feet' => null,
            'length_inches' => null,
            'width_inches' => null,
            'height_inches' => null
        ]);
        
        $this->assertFalse($manifest->hasCompleteVolumeData());
    }

    /** @test */
    public function it_returns_true_for_complete_volume_data_when_no_packages_exist()
    {
        $manifest = Manifest::factory()->create();
        
        $this->assertTrue($manifest->hasCompleteVolumeData());
    }

    /** @test */
    public function it_handles_mixed_volume_data_sources_correctly()
    {
        $manifest = Manifest::factory()->create();
        
        // Package with cubic_feet
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'cubic_feet' => 2.5
        ]);
        
        // Package with dimensions
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'user_id' => $this->user->id,
            'length_inches' => 12,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);
        
        $this->assertTrue($manifest->hasCompleteVolumeData());
        $this->assertEquals(3.5, $manifest->getTotalVolume()); // 2.5 + 1.0
    }
}