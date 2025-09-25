<?php

namespace Tests\Feature;

use App\Http\Livewire\Manifests\EnhancedManifestSummary;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnhancedManifestSummaryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and authenticate using existing role
        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        $this->actingAs($user);
    }

    /** @test */
    public function it_displays_air_manifest_summary_with_real_data()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        // Create packages with weight data and cost breakdown
        Package::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'weight' => 15.5,
            'estimated_value' => 100.00,
            'freight_price' => 150.00,
            'clearance_fee' => 30.00,
            'storage_fee' => 15.00,
            'delivery_fee' => 5.00,
            // total_cost = 150 + 30 + 15 + 5 = 200.00 per package, 3 packages = $600.00
        ]);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        $component->assertSet('manifestType', 'air')
                  ->assertSee('Total Packages')
                  ->assertSee('3')
                  ->assertSee('Total Weight')
                  ->assertSee('lbs')
                  ->assertSee('kg')
                  ->assertSee('Total Cost')
                  ->assertSee('$600.00');
    }

    /** @test */
    public function it_displays_sea_manifest_summary_with_real_data()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        // Create packages with volume data and cost breakdown
        Package::factory()->count(2)->create([
            'manifest_id' => $manifest->id,
            'length_inches' => 24,
            'width_inches' => 18,
            'height_inches' => 12,
            'estimated_value' => 100.00,
            'freight_price' => 100.00,
            'clearance_fee' => 25.00,
            'storage_fee' => 20.00,
            'delivery_fee' => 5.00,
            // total_cost = 100 + 25 + 20 + 5 = 150.00 per package, 2 packages = $300.00
        ]);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        $component->assertSet('manifestType', 'sea')
                  ->assertSee('Total Packages')
                  ->assertSee('2')
                  ->assertSee('Total Volume')
                  ->assertSee('ft³')
                  ->assertSee('Total Cost')
                  ->assertSee('$300.00');
    }

    /** @test */
    public function it_updates_summary_when_packages_are_added()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        // Start with one package
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 10.0,
            'estimated_value' => 100.00,
        ]);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Initial state
        $component->assertSee('1'); // package count
        
        // Add another package
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 20.0,
            'estimated_value' => 200.00,
        ]);
        
        // Trigger refresh
        $component->call('refreshSummary');
        
        // Should show updated data
        $component->assertSee('2'); // updated package count
    }

    /** @test */
    public function it_handles_real_time_updates_via_events()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 5.0,
        ]);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Initial state
        $component->assertSee('1'); // package count
        
        // Add another package to the database
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 10.0,
        ]);
        
        // Simulate receiving the packageAdded event (this would normally come from another component)
        $component->call('refreshSummary'); // This is what the listener would call
        
        // Should show updated count
        $component->assertSee('2'); // updated package count
    }
}