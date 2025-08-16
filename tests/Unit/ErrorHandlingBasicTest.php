<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Manifests\ManifestTabsContainer;
use App\Services\WeightCalculationService;
use App\Services\VolumeCalculationService;
use App\Services\ManifestSummaryService;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ErrorHandlingBasicTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->manifest = Manifest::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function tab_container_handles_invalid_tab_names()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Test invalid tab names that should be sanitized and defaulted
        $invalidTabs = ['invalid', '../../etc/passwd', 'nonexistent'];
        
        foreach ($invalidTabs as $invalidTab) {
            $component->call('switchTab', $invalidTab);
            
            // Should default to 'individual' tab
            $component->assertSet('activeTab', 'individual');
        }
    }

    /** @test */
    public function tab_container_validates_special_characters()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Test XSS attempt with special characters
        $component->call('switchTab', '<script>alert("xss")</script>');
        
        $component->assertSet('hasError', true);
        $component->assertSet('errorMessage', 'Invalid request. Please try again.');
    }

    /** @test */
    public function weight_service_handles_empty_collection()
    {
        $service = new WeightCalculationService();
        $packages = collect();
        
        $result = $service->calculateTotalWeight($packages);
        
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function weight_service_handles_null_weights()
    {
        $service = new WeightCalculationService();
        $packages = collect([
            new Package(['weight' => null]),
            new Package(['weight' => 10.5]),
            new Package(['weight' => null])
        ]);
        
        $result = $service->calculateTotalWeight($packages);
        
        $this->assertEquals(10.5, $result);
    }

    /** @test */
    public function weight_service_handles_negative_weights()
    {
        $service = new WeightCalculationService();
        $packages = collect([
            new Package(['weight' => -5.0]),
            new Package(['weight' => 10.0])
        ]);
        
        $result = $service->calculateTotalWeight($packages);
        
        $this->assertEquals(10.0, $result);
    }

    /** @test */
    public function weight_service_caps_extremely_high_weights()
    {
        $service = new WeightCalculationService();
        $packages = collect([
            new Package(['weight' => 50000.0]), // Extremely high weight
            new Package(['weight' => 10.0])
        ]);
        
        $result = $service->calculateTotalWeight($packages);
        
        $this->assertEquals(10010.0, $result); // 10000 (capped) + 10
    }

    /** @test */
    public function weight_service_handles_invalid_collection_items_gracefully()
    {
        $service = new WeightCalculationService();
        $packages = collect([
            new Package(['weight' => 10.0]),
            'invalid_item',
            new Package(['weight' => 5.0])
        ]);
        
        // Should return 0.0 due to error handling
        $result = $service->calculateTotalWeight($packages);
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function weight_service_validates_conversion_input()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Weight in pounds must be a non-negative number');
        
        $service = new WeightCalculationService();
        $service->convertLbsToKg(-10.0);
    }

    /** @test */
    public function volume_service_handles_empty_collection()
    {
        $service = new VolumeCalculationService();
        $packages = collect();
        
        $result = $service->calculateTotalVolume($packages);
        
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function volume_service_handles_null_dimensions()
    {
        $service = new VolumeCalculationService();
        $packages = collect([
            new Package(['length_inches' => null, 'width_inches' => null, 'height_inches' => null]),
            new Package(['length_inches' => 10, 'width_inches' => 5, 'height_inches' => 3]),
        ]);
        
        $result = $service->calculateTotalVolume($packages);
        
        // Only the second package should contribute: (10 * 5 * 3) / 1728 = 0.087
        $this->assertEquals(0.087, $result);
    }

    /** @test */
    public function volume_service_handles_negative_dimensions()
    {
        $service = new VolumeCalculationService();
        $packages = collect([
            new Package(['length_inches' => -10, 'width_inches' => 5, 'height_inches' => 3]),
            new Package(['length_inches' => 12, 'width_inches' => 6, 'height_inches' => 4]),
        ]);
        
        $result = $service->calculateTotalVolume($packages);
        
        // Only the second package should contribute: (12 * 6 * 4) / 1728 = 0.167
        $this->assertEquals(0.167, $result);
    }

    /** @test */
    public function volume_service_caps_large_dimensions()
    {
        $service = new VolumeCalculationService();
        $packages = collect([
            new Package(['length_inches' => 200, 'width_inches' => 200, 'height_inches' => 200]),
        ]);
        
        $result = $service->calculateTotalVolume($packages);
        
        // Dimensions should be capped at 120 inches each: (120 * 120 * 120) / 1728 = 1000
        $this->assertEquals(1000.0, $result);
    }

    /** @test */
    public function volume_service_handles_invalid_collection_items_gracefully()
    {
        $service = new VolumeCalculationService();
        $packages = collect([
            new Package(['cubic_feet' => 10.0]),
            'invalid_item',
            new Package(['cubic_feet' => 5.0])
        ]);
        
        // Should return 0.0 due to error handling
        $result = $service->calculateTotalVolume($packages);
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function weight_service_validates_calculation_results()
    {
        $service = new WeightCalculationService();
        
        $results = [
            'total_weight' => -100.0,
            'average_weight' => 2000000.0,
            'valid_field' => 'test'
        ];
        
        $validated = $service->validateCalculationResults($results);
        
        $this->assertEquals(0, $validated['total_weight']);
        $this->assertEquals(1000000, $validated['average_weight']);
        $this->assertEquals('test', $validated['valid_field']);
    }

    /** @test */
    public function volume_service_validates_calculation_results()
    {
        $service = new VolumeCalculationService();
        
        $results = [
            'total_volume' => -50.0,
            'average_volume' => 200000.0,
            'valid_field' => 'test'
        ];
        
        $validated = $service->validateCalculationResults($results);
        
        $this->assertEquals(0, $validated['total_volume']);
        $this->assertEquals(100000, $validated['average_volume']);
        $this->assertEquals('test', $validated['valid_field']);
    }

    /** @test */
    public function weight_service_handles_formatting_errors()
    {
        $service = new WeightCalculationService();
        
        $result = $service->formatWeightUnits(-10.0, -5.0);
        
        // Should return safe fallback
        $this->assertEquals([
            'lbs' => '0.0 lbs',
            'kg' => '0.0 kg',
            'raw_lbs' => 0.0,
            'raw_kg' => 0.0,
            'display' => '0.0 lbs (0.0 kg)'
        ], $result);
    }

    /** @test */
    public function tab_container_handles_extremely_long_tab_names()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        $longTabName = str_repeat('a', 100);
        $component->call('switchTab', $longTabName);
        
        $component->assertSet('hasError', true);
        $component->assertSet('errorMessage', 'Invalid request. Please try again.');
    }

    /** @test */
    public function tab_container_clears_error_state_on_valid_operations()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Set error state
        $component->set('hasError', true);
        $component->set('errorMessage', 'Test error');

        // Perform valid operation
        $component->call('switchTab', 'consolidated');
        
        $component->assertSet('hasError', false);
        $component->assertSet('errorMessage', '');
    }

    /** @test */
    public function weight_service_rounds_results_appropriately()
    {
        $service = new WeightCalculationService();
        $packages = collect([
            new Package(['weight' => 10.123456]),
            new Package(['weight' => 5.987654])
        ]);
        
        $result = $service->calculateTotalWeight($packages);
        
        // Should be rounded to 2 decimal places
        $this->assertEquals(16.11, $result);
    }

    /** @test */
    public function volume_service_rounds_results_appropriately()
    {
        $service = new VolumeCalculationService();
        $packages = collect([
            new Package(['cubic_feet' => 10.123456]),
            new Package(['cubic_feet' => 5.987654])
        ]);
        
        $result = $service->calculateTotalVolume($packages);
        
        // Should be rounded to 3 decimal places
        $this->assertEquals(16.111, $result);
    }
}