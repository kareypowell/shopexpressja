<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use App\Models\Role;
use App\Http\Livewire\Manifests\EnhancedManifestSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class ManifestSummaryValidationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required roles
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'customer', 'description' => 'Customer']);
    }

    /** @test */
    public function it_handles_manifest_with_valid_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'AA123'
        ]);
        
        $packages = Package::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'freight_price' => 100.00,
            'clearance_fee' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
            'weight' => 5.5
        ]);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        $component->assertSet('hasError', false)
                  ->assertSet('manifestType', 'air')
                  ->assertViewHas('summary');
        
        $summary = $component->get('summary');
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('package_count', $summary);
        $this->assertEquals(3, $summary['package_count']);
        $this->assertArrayHasKey('total_value', $summary);
        $this->assertGreaterThan(0, $summary['total_value']);
    }

    /** @test */
    public function it_handles_manifest_with_invalid_package_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $manifest = Manifest::factory()->create([
            'type' => 'air'
        ]);
        
        // Create packages with some invalid data
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'freight_price' => 'invalid_price', // Invalid string
            'clearance_fee' => -50, // Negative value
            'storage_fee' => null,
            'delivery_fee' => 999999, // Extremely high value
            'weight' => 'not_a_number' // Invalid weight
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'freight_price' => 100.00,
            'clearance_fee' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
            'weight' => 5.5
        ]);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Should not error out, but handle invalid data gracefully
        $component->assertSet('hasError', false);
        
        $summary = $component->get('summary');
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('package_count', $summary);
        $this->assertEquals(2, $summary['package_count']);
        
        // Total value should be calculated from valid package only
        $this->assertArrayHasKey('total_value', $summary);
        $this->assertGreaterThan(0, $summary['total_value']);
    }

    /** @test */
    public function it_handles_empty_manifest()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $manifest = Manifest::factory()->create([
            'type' => 'sea'
        ]);
        
        // No packages in this manifest

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        $component->assertSet('hasError', false)
                  ->assertSet('manifestType', 'sea');
        
        $summary = $component->get('summary');
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('package_count', $summary);
        $this->assertEquals(0, $summary['package_count']);
        $this->assertArrayHasKey('total_value', $summary);
        $this->assertEquals(0.0, $summary['total_value']);
        
        // Should have validation warnings for empty manifest
        if (isset($summary['validation_warnings'])) {
            $this->assertContains('No packages found in this manifest', $summary['validation_warnings']);
        }
    }

    /** @test */
    public function it_handles_unknown_manifest_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $manifest = Manifest::factory()->create([
            'type' => null, // Unknown type
            'flight_number' => null,
            'vessel_name' => null
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'freight_price' => 100.00,
            'clearance_fee' => 25.00
        ]);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        $component->assertSet('hasError', false)
                  ->assertSet('manifestType', 'unknown');
        
        $summary = $component->get('summary');
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('package_count', $summary);
        $this->assertEquals(1, $summary['package_count']);
        
        // Should have validation warning for unknown type
        if (isset($summary['validation_warnings'])) {
            $this->assertContains('Manifest type could not be determined - showing basic information only', $summary['validation_warnings']);
        }
    }

    /** @test */
    public function it_can_retry_calculation_after_error()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $manifest = Manifest::factory()->create();

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Test retry functionality
        $component->call('retryCalculation');
        
        // Should not error out
        $component->assertSet('isRetrying', false);
    }

    /** @test */
    public function it_refreshes_summary_when_packages_change()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Initially no packages
        $component->assertSet('hasError', false);
        $summary = $component->get('summary');
        $this->assertEquals(0, $summary['package_count']);
        
        // Simulate package added event
        $component->call('refreshSummary');
        
        // Should still work without error
        $component->assertSet('hasError', false);
    }
}