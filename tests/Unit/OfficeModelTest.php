<?php

namespace Tests\Unit;

use App\Models\Office;
use App\Models\Package;
use App\Models\Profile;
use App\Models\Manifest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->office = Office::factory()->create([
            'name' => 'Test Office',
            'address' => '123 Test Street, Test City'
        ]);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = ['name', 'address'];
        
        $this->assertEquals($fillable, $this->office->getFillable());
    }

    /** @test */
    public function it_has_packages_relationship()
    {
        $package = Package::factory()->create(['office_id' => $this->office->id]);
        
        $this->assertTrue($this->office->packages()->exists());
        $this->assertEquals(1, $this->office->packages()->count());
        $this->assertEquals($package->id, $this->office->packages()->first()->id);
    }

    /** @test */
    public function it_has_profiles_relationship()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'pickup_location' => $this->office->id
        ]);
        
        $this->assertTrue($this->office->profiles()->exists());
        $this->assertEquals(1, $this->office->profiles()->count());
        $this->assertEquals($profile->id, $this->office->profiles()->first()->id);
    }

    /** @test */
    public function it_can_search_by_name()
    {
        Office::factory()->create(['name' => 'Another Office']);
        
        $results = Office::search('Test')->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Test Office', $results->first()->name);
    }

    /** @test */
    public function it_can_search_by_address()
    {
        Office::factory()->create(['address' => 'Different Address']);
        
        $results = Office::search('Test Street')->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Test Office', $results->first()->name);
    }

    /** @test */
    public function it_can_search_with_partial_matches()
    {
        $results = Office::search('est')->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Test Office', $results->first()->name);
    }

    /** @test */
    public function it_returns_empty_results_for_no_matches()
    {
        $results = Office::search('nonexistent')->get();
        
        $this->assertEquals(0, $results->count());
    }

    /** @test */
    public function it_calculates_package_count_attribute()
    {
        Package::factory()->count(3)->create(['office_id' => $this->office->id]);
        
        $this->assertEquals(3, $this->office->package_count);
    }

    /** @test */
    public function it_calculates_profile_count_attribute()
    {
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            Profile::factory()->create([
                'user_id' => $user->id,
                'pickup_location' => $this->office->id
            ]);
        }
        
        $this->assertEquals(2, $this->office->profile_count);
    }

    /** @test */
    public function it_calculates_manifest_count_attribute()
    {
        $manifest1 = Manifest::factory()->create();
        $manifest2 = Manifest::factory()->create();
        
        // Create packages with different manifests
        Package::factory()->create([
            'office_id' => $this->office->id,
            'manifest_id' => $manifest1->id
        ]);
        Package::factory()->create([
            'office_id' => $this->office->id,
            'manifest_id' => $manifest2->id
        ]);
        // Create another package with the same manifest
        Package::factory()->create([
            'office_id' => $this->office->id,
            'manifest_id' => $manifest1->id
        ]);
        
        $this->assertEquals(2, $this->office->manifest_count);
    }

    /** @test */
    public function it_handles_zero_counts_correctly()
    {
        $this->assertEquals(0, $this->office->package_count);
        $this->assertEquals(0, $this->office->profile_count);
        $this->assertEquals(0, $this->office->manifest_count);
    }

    /** @test */
    public function it_counts_distinct_manifests_correctly()
    {
        $manifest = Manifest::factory()->create();
        
        // Create multiple packages with the same manifest
        Package::factory()->count(3)->create([
            'office_id' => $this->office->id,
            'manifest_id' => $manifest->id
        ]);
        
        $this->assertEquals(1, $this->office->manifest_count);
        $this->assertEquals(3, $this->office->package_count);
    }
}