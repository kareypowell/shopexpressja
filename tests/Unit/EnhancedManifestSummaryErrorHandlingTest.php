<?php

namespace Tests\Unit;

use App\Http\Livewire\Manifests\EnhancedManifestSummary;
use App\Models\Manifest;
use App\Models\Role;
use App\Models\User;
use App\Services\ManifestSummaryCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnhancedManifestSummaryErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and authenticate using existing role
        $customerRole = Role::where('name', 'customer')->first();
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        $this->actingAs($user);
    }

    /** @test */
    public function it_handles_cache_service_errors_gracefully()
    {
        $manifest = Manifest::create([
            'name' => 'TEST-001',
            'type' => 'air',
            'shipment_date' => now(),
        ]);
        
        // Mock both services to throw exceptions
        $this->mock(ManifestSummaryCacheService::class, function ($mock) {
            $mock->shouldReceive('getCachedDisplaySummary')
                 ->andThrow(new \Exception('Cache service unavailable'));
        });
        
        $this->mock(\App\Services\ManifestSummaryService::class, function ($mock) {
            $mock->shouldReceive('getDisplaySummary')
                 ->andThrow(new \Exception('Service unavailable'));
        });

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Should handle the error gracefully and set error state
        $component->assertSet('hasError', true);
        $component->assertSet('errorMessage', 'Service temporarily unavailable. Please retry or contact support.');
        $component->assertSee('Summary Calculation Error');
    }

    /** @test */
    public function it_provides_emergency_fallback_data_when_all_services_fail()
    {
        $manifest = Manifest::create([
            'name' => 'TEST-002',
            'type' => 'sea',
            'shipment_date' => now(),
        ]);
        
        // Mock both services to throw exceptions
        $this->mock(ManifestSummaryCacheService::class, function ($mock) {
            $mock->shouldReceive('getCachedDisplaySummary')
                 ->andThrow(new \Exception('Complete service failure'));
        });
        
        $this->mock(\App\Services\ManifestSummaryService::class, function ($mock) {
            $mock->shouldReceive('getDisplaySummary')
                 ->andThrow(new \Exception('Complete service failure'));
        });

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Should provide emergency fallback data
        $component->assertSet('hasError', true);
        $component->assertSet('manifestType', 'sea');
        $component->assertSet('summary.package_count', 0);
        $component->assertSet('summary.total_value', 0.0);
        $component->assertSet('summary.incomplete_data', true);
    }

    /** @test */
    public function it_allows_users_to_retry_failed_calculations()
    {
        $manifest = Manifest::create([
            'name' => 'TEST-003',
            'type' => 'air',
            'shipment_date' => now(),
        ]);
        
        // Mock both services to fail initially
        $this->mock(ManifestSummaryCacheService::class, function ($mock) use ($manifest) {
            $mock->shouldReceive('getCachedDisplaySummary')
                 ->andThrow(new \Exception('Temporary failure'));
            $mock->shouldReceive('invalidateManifestCache')
                 ->with($manifest);
        });
        
        $this->mock(\App\Services\ManifestSummaryService::class, function ($mock) {
            $mock->shouldReceive('getDisplaySummary')
                 ->andThrow(new \Exception('Temporary failure'));
        });

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Initial error state
        $component->assertSet('hasError', true);
        
        // Test that retry method can be called without throwing exceptions
        $component->call('retryCalculation');
        
        // Should reset the retrying flag
        $component->assertSet('isRetrying', false);
    }

    /** @test */
    public function it_categorizes_different_error_types_correctly()
    {
        $manifest = Manifest::create([
            'name' => 'TEST-004',
            'type' => 'air',
            'shipment_date' => now(),
        ]);
        
        // Test validation error
        $this->mock(ManifestSummaryCacheService::class, function ($mock) {
            $mock->shouldReceive('getCachedDisplaySummary')
                 ->andThrow(new \InvalidArgumentException('Invalid data'));
        });
        
        $this->mock(\App\Services\ManifestSummaryService::class, function ($mock) {
            $mock->shouldReceive('getDisplaySummary')
                 ->andThrow(new \InvalidArgumentException('Invalid data'));
        });

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        $component->assertSet('hasError', true);
        $component->assertSet('errorMessage', 'Invalid manifest data detected. Please check the manifest configuration.');
    }

    /** @test */
    public function it_handles_invalid_manifest_objects()
    {
        // Create a manifest with missing required data
        $manifest = new Manifest();
        
        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Should handle invalid manifest gracefully
        $component->assertSet('hasError', true);
        $component->assertSet('manifestType', 'unknown');
        $component->assertSet('summary.package_count', 0);
    }

    /** @test */
    public function it_sanitizes_metric_values_for_display()
    {
        $manifest = Manifest::create([
            'name' => 'TEST-005',
            'type' => 'air',
            'shipment_date' => now(),
        ]);
        
        // Mock service to return potentially unsafe data
        $this->mock(ManifestSummaryCacheService::class, function ($mock) {
            $mock->shouldReceive('getCachedDisplaySummary')
                 ->andReturn([
                     'manifest_type' => 'air',
                     'package_count' => 1,
                     'total_value' => '100.00',
                     'incomplete_data' => false,
                     'primary_metric' => [
                         'type' => 'weight',
                         'value' => '<script>alert("xss")</script>10.0 lbs',
                         'secondary' => '4.5 kg',
                     ]
                 ]);
        });

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $manifest]);
        
        // Should sanitize the metric values
        $component->assertSet('hasError', false);
        $component->assertSet('summary.weight.lbs', 'scriptalertxssscript10.0 lbs'); // Sanitized
    }
}