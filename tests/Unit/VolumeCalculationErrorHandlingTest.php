<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VolumeCalculationService;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class VolumeCalculationErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected VolumeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VolumeCalculationService();
    }

    /** @test */
    public function it_handles_empty_package_collection()
    {
        $packages = collect();
        
        $result = $this->service->calculateTotalVolume($packages);
        
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function it_handles_packages_with_null_dimensions()
    {
        $packages = collect([
            new Package(['length_inches' => null, 'width_inches' => null, 'height_inches' => null]),
            new Package(['length_inches' => 10, 'width_inches' => 5, 'height_inches' => 3]),
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        // Only the second package should contribute: (10 * 5 * 3) / 1728 = 0.087
        $this->assertEquals(0.087, $result);
    }

    /** @test */
    public function it_handles_negative_dimensions()
    {
        $packages = collect([
            new Package(['length_inches' => -10, 'width_inches' => 5, 'height_inches' => 3]),
            new Package(['length_inches' => 12, 'width_inches' => 6, 'height_inches' => 4]),
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        // Only the second package should contribute: (12 * 6 * 4) / 1728 = 0.167
        $this->assertEquals(0.167, $result);
    }

    /** @test */
    public function it_caps_extremely_large_dimensions()
    {
        Log::fake();
        
        $packages = collect([
            new Package(['length_inches' => 200, 'width_inches' => 200, 'height_inches' => 200]),
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        // Dimensions should be capped at 120 inches each: (120 * 120 * 120) / 1728 = 1000
        $this->assertEquals(1000.0, $result);
        
        Log::assertLogged('warning', function ($log) {
            return str_contains($log['message'], 'Extremely large package dimensions found');
        });
    }

    /** @test */
    public function it_handles_non_numeric_dimensions()
    {
        $packages = collect([
            new Package(['length_inches' => 'invalid', 'width_inches' => 5, 'height_inches' => 3]),
            new Package(['length_inches' => 10, 'width_inches' => 8, 'height_inches' => 2]),
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        // Only the second package should contribute: (10 * 8 * 2) / 1728 = 0.093
        $this->assertEquals(0.093, $result);
    }

    /** @test */
    public function it_handles_extremely_high_volumes()
    {
        Log::fake();
        
        $packages = collect([
            new Package(['cubic_feet' => 2000.0]), // Extremely high volume
            new Package(['cubic_feet' => 5.0])
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        $this->assertEquals(1005.0, $result); // 1000 (capped) + 5
        
        Log::assertLogged('warning', function ($log) {
            return str_contains($log['message'], 'Extremely high volume value found');
        });
    }

    /** @test */
    public function it_handles_invalid_volume_values()
    {
        Log::fake();
        
        $packages = collect([
            new Package(['cubic_feet' => 'invalid']),
            new Package(['cubic_feet' => 10.5])
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        $this->assertEquals(10.5, $result);
        
        Log::assertLogged('warning');
    }

    /** @test */
    public function it_validates_invalid_collection_items()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Collection must contain only Package instances');
        
        $packages = collect([
            new Package(['cubic_feet' => 10.0]),
            'invalid_item',
            new Package(['cubic_feet' => 5.0])
        ]);
        
        $this->service->calculateTotalVolume($packages);
    }

    /** @test */
    public function it_handles_dimension_calculation_errors()
    {
        Log::fake();
        
        // Create a package that will cause calculation error
        $package = new Package(['length_inches' => 10, 'width_inches' => 5, 'height_inches' => 3]);
        
        // Mock the package to throw exception during property access
        $package = new class extends Package {
            public function __get($key)
            {
                if ($key === 'length_inches') {
                    throw new \Exception('Database error');
                }
                return parent::__get($key);
            }
        };
        
        $result = $this->service->calculateVolumeFromDimensions($package);
        
        $this->assertEquals(0, $result);
        
        Log::assertLogged('error');
    }

    /** @test */
    public function it_validates_calculation_results()
    {
        Log::fake();
        
        $results = [
            'total_volume' => -50.0,
            'average_volume' => 200000.0,
            'valid_field' => 'test'
        ];
        
        $validated = $this->service->validateCalculationResults($results);
        
        $this->assertEquals(0, $validated['total_volume']);
        $this->assertEquals(100000, $validated['average_volume']);
        $this->assertEquals('test', $validated['valid_field']);
        
        Log::assertLogged('warning');
    }

    /** @test */
    public function it_handles_statistics_calculation_errors()
    {
        Log::fake();
        
        $packages = collect([
            new Package(['cubic_feet' => 'invalid']),
            new Package(['cubic_feet' => null])
        ]);
        
        $result = $this->service->getVolumeStatistics($packages);
        
        // Should return safe defaults
        $this->assertEquals(0, $result['total_volume']);
        $this->assertEquals(0, $result['average_volume']);
        $this->assertEquals(0, $result['min_volume']);
        $this->assertEquals(0, $result['max_volume']);
    }

    /** @test */
    public function it_filters_invalid_volumes_in_statistics()
    {
        Log::fake();
        
        $packages = collect([
            new Package(['cubic_feet' => 10.0]),
            new Package(['cubic_feet' => -5.0]), // Invalid
            new Package(['cubic_feet' => 'abc']), // Invalid
            new Package(['cubic_feet' => 2000.0]), // Too high, will be capped
            new Package(['cubic_feet' => 15.0])
        ]);
        
        $result = $this->service->getVolumeStatistics($packages);
        
        // Should only include valid volumes: 10.0, 15.0
        $this->assertEquals(25.0, $result['total_volume']);
        $this->assertEquals(12.5, $result['average_volume']);
    }

    /** @test */
    public function it_handles_exception_during_total_calculation()
    {
        Log::fake();
        
        // Create a mock collection that throws exception
        $packages = new class extends Collection {
            public function sum($callback = null)
            {
                throw new \Exception('Database connection failed');
            }
            
            public function count()
            {
                return 2;
            }
        };
        
        $result = $this->service->calculateTotalVolume($packages);
        
        $this->assertEquals(0.0, $result);
        
        Log::assertLogged('error', function ($log) {
            return str_contains($log['message'], 'Failed to calculate total volume');
        });
    }

    /** @test */
    public function it_handles_zero_dimensions_correctly()
    {
        $packages = collect([
            new Package(['length_inches' => 0, 'width_inches' => 5, 'height_inches' => 3]),
            new Package(['length_inches' => 10, 'width_inches' => 0, 'height_inches' => 3]),
            new Package(['length_inches' => 10, 'width_inches' => 5, 'height_inches' => 0]),
            new Package(['length_inches' => 12, 'width_inches' => 6, 'height_inches' => 4])
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        // Only the last package should contribute: (12 * 6 * 4) / 1728 = 0.167
        $this->assertEquals(0.167, $result);
    }

    /** @test */
    public function it_prefers_cubic_feet_over_calculated_dimensions()
    {
        $packages = collect([
            new Package([
                'cubic_feet' => 5.0,
                'length_inches' => 12,
                'width_inches' => 12,
                'height_inches' => 12
            ])
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        // Should use cubic_feet value (5.0) instead of calculating from dimensions
        $this->assertEquals(5.0, $result);
    }

    /** @test */
    public function it_falls_back_to_dimensions_when_cubic_feet_invalid()
    {
        $packages = collect([
            new Package([
                'cubic_feet' => 'invalid',
                'length_inches' => 12,
                'width_inches' => 6,
                'height_inches' => 4
            ])
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        // Should calculate from dimensions: (12 * 6 * 4) / 1728 = 0.167
        $this->assertEquals(0.167, $result);
    }

    /** @test */
    public function it_rounds_results_appropriately()
    {
        $packages = collect([
            new Package(['cubic_feet' => 10.123456]),
            new Package(['cubic_feet' => 5.987654])
        ]);
        
        $result = $this->service->calculateTotalVolume($packages);
        
        // Should be rounded to 3 decimal places
        $this->assertEquals(16.111, $result);
    }

    /** @test */
    public function it_handles_has_volume_data_with_invalid_values()
    {
        $package1 = new Package(['cubic_feet' => 'invalid']);
        $package2 = new Package(['length_inches' => 'invalid', 'width_inches' => 5, 'height_inches' => 3]);
        $package3 = new Package(['length_inches' => 10, 'width_inches' => 5, 'height_inches' => 3]);
        
        $this->assertFalse($this->service->hasVolumeData($package1));
        $this->assertFalse($this->service->hasVolumeData($package2));
        $this->assertTrue($this->service->hasVolumeData($package3));
    }

    /** @test */
    public function it_estimates_volume_from_weight_with_invalid_weight()
    {
        $package = new Package(['weight' => 'invalid']);
        
        $result = $this->service->estimateVolumeFromWeight($package);
        
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_estimates_volume_from_weight_correctly()
    {
        $package = new Package(['weight' => 22.0]); // 22 lbs
        
        $result = $this->service->estimateVolumeFromWeight($package);
        
        // 22 / 11 = 2.0 cubic feet
        $this->assertEquals(2.0, $result);
    }

    /** @test */
    public function it_handles_exception_during_statistics_calculation()
    {
        Log::fake();
        
        // Create packages that will cause exception during processing
        $packages = collect([
            new Package(['cubic_feet' => 10.0])
        ]);
        
        // Mock the filter method to throw exception
        $packages = new class($packages->all()) extends Collection {
            public function filter($callback = null)
            {
                throw new \Exception('Filter operation failed');
            }
            
            public function count()
            {
                return 1;
            }
        };
        
        $result = $this->service->getVolumeStatistics($packages);
        
        // Should return safe defaults
        $this->assertEquals(0, $result['total_volume']);
        
        Log::assertLogged('error');
    }

    /** @test */
    public function it_validates_packages_collection_with_mixed_types()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $packages = collect([
            new Package(['cubic_feet' => 10.0]),
            new \stdClass(), // Invalid type
            new Package(['cubic_feet' => 5.0])
        ]);
        
        $this->service->calculateTotalVolume($packages);
    }
}