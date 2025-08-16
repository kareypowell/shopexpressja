<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WeightCalculationService;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WeightCalculationErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected WeightCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WeightCalculationService();
    }

    /** @test */
    public function it_handles_empty_package_collection()
    {
        $packages = collect();
        
        $result = $this->service->calculateTotalWeight($packages);
        
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function it_handles_packages_with_null_weights()
    {
        $packages = collect([
            new Package(['weight' => null]),
            new Package(['weight' => 10.5]),
            new Package(['weight' => null])
        ]);
        
        $result = $this->service->calculateTotalWeight($packages);
        
        $this->assertEquals(10.5, $result);
        
        // Note: In a real implementation, we would check logs, but for now we verify the behavior
    }

    /** @test */
    public function it_handles_negative_weights()
    {
        $packages = collect([
            new Package(['weight' => -5.0]),
            new Package(['weight' => 10.0])
        ]);
        
        $result = $this->service->calculateTotalWeight($packages);
        
        $this->assertEquals(10.0, $result);
    }

    /** @test */
    public function it_caps_extremely_high_weights()
    {
        Log::fake();
        
        $packages = collect([
            new Package(['weight' => 50000.0]), // Extremely high weight
            new Package(['weight' => 10.0])
        ]);
        
        $result = $this->service->calculateTotalWeight($packages);
        
        $this->assertEquals(10010.0, $result); // 10000 (capped) + 10
        
        Log::assertLogged('warning', function ($log) {
            return str_contains($log['message'], 'Extremely high weight value found');
        });
    }

    /** @test */
    public function it_handles_non_numeric_weights()
    {
        Log::fake();
        
        $packages = collect([
            new Package(['weight' => 'invalid']),
            new Package(['weight' => 15.5])
        ]);
        
        $result = $this->service->calculateTotalWeight($packages);
        
        $this->assertEquals(15.5, $result);
        
        Log::assertLogged('warning');
    }

    /** @test */
    public function it_validates_invalid_collection_items()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Collection must contain only Package instances');
        
        $packages = collect([
            new Package(['weight' => 10.0]),
            'invalid_item',
            new Package(['weight' => 5.0])
        ]);
        
        $this->service->calculateTotalWeight($packages);
    }

    /** @test */
    public function it_handles_conversion_errors_gracefully()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Weight in pounds must be a non-negative number');
        
        $this->service->convertLbsToKg(-10.0);
    }

    /** @test */
    public function it_logs_extremely_high_weight_conversions()
    {
        Log::fake();
        
        $result = $this->service->convertLbsToKg(100000.0);
        
        $this->assertEquals(45359.2, $result);
        
        Log::assertLogged('warning', function ($log) {
            return str_contains($log['message'], 'Extremely high weight conversion requested');
        });
    }

    /** @test */
    public function it_handles_formatting_errors_gracefully()
    {
        Log::fake();
        
        $result = $this->service->formatWeightUnits(-10.0, -5.0);
        
        // Should return safe fallback
        $this->assertEquals([
            'lbs' => '0.0 lbs',
            'kg' => '0.0 kg',
            'raw_lbs' => 0.0,
            'raw_kg' => 0.0,
            'display' => '0.0 lbs (0.0 kg)'
        ], $result);
        
        Log::assertLogged('warning');
    }

    /** @test */
    public function it_validates_calculation_results()
    {
        Log::fake();
        
        $results = [
            'total_weight' => -100.0,
            'average_weight' => 2000000.0,
            'valid_field' => 'test'
        ];
        
        $validated = $this->service->validateCalculationResults($results);
        
        $this->assertEquals(0, $validated['total_weight']);
        $this->assertEquals(1000000, $validated['average_weight']);
        $this->assertEquals('test', $validated['valid_field']);
        
        Log::assertLogged('warning');
    }

    /** @test */
    public function it_handles_statistics_calculation_errors()
    {
        Log::fake();
        
        // Mock a scenario where calculation fails
        $packages = collect([
            new Package(['weight' => 'invalid']),
            new Package(['weight' => null])
        ]);
        
        $result = $this->service->getWeightStatistics($packages);
        
        // Should return safe defaults
        $this->assertEquals(0, $result['total_weight_lbs']);
        $this->assertEquals(0, $result['total_weight_kg']);
        $this->assertEquals(0, $result['average_weight_lbs']);
    }

    /** @test */
    public function it_filters_invalid_weights_in_statistics()
    {
        Log::fake();
        
        $packages = collect([
            new Package(['weight' => 10.0]),
            new Package(['weight' => -5.0]), // Invalid
            new Package(['weight' => 'abc']), // Invalid
            new Package(['weight' => 20000.0]), // Too high, will be capped
            new Package(['weight' => 15.0])
        ]);
        
        $result = $this->service->getWeightStatistics($packages);
        
        // Should only include valid weights: 10.0, 15.0
        $this->assertEquals(25.0, $result['total_weight_lbs']);
        $this->assertEquals(12.5, $result['average_weight_lbs']);
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
        
        $result = $this->service->calculateTotalWeight($packages);
        
        $this->assertEquals(0.0, $result);
        
        Log::assertLogged('error', function ($log) {
            return str_contains($log['message'], 'Failed to calculate total weight');
        });
    }

    /** @test */
    public function it_handles_exception_during_statistics_calculation()
    {
        Log::fake();
        
        // Create packages that will cause exception during processing
        $packages = collect([
            new Package(['weight' => 10.0])
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
        
        $result = $this->service->getWeightStatistics($packages);
        
        // Should return safe defaults
        $this->assertEquals(0, $result['total_weight_lbs']);
        
        Log::assertLogged('error');
    }

    /** @test */
    public function it_handles_formatting_with_invalid_kg_parameter()
    {
        Log::fake();
        
        $result = $this->service->formatWeightUnits(10.0, 'invalid');
        
        // Should recalculate kg from lbs
        $this->assertEquals('10.0 lbs', $result['lbs']);
        $this->assertEquals('4.5 kg', $result['kg']);
        
        Log::assertLogged('warning');
    }

    /** @test */
    public function it_rounds_results_appropriately()
    {
        $packages = collect([
            new Package(['weight' => 10.123456]),
            new Package(['weight' => 5.987654])
        ]);
        
        $result = $this->service->calculateTotalWeight($packages);
        
        // Should be rounded to 2 decimal places
        $this->assertEquals(16.11, $result);
    }

    /** @test */
    public function it_handles_zero_weights_correctly()
    {
        $packages = collect([
            new Package(['weight' => 0]),
            new Package(['weight' => 10.0]),
            new Package(['weight' => 0.0])
        ]);
        
        $result = $this->service->calculateTotalWeight($packages);
        
        $this->assertEquals(10.0, $result);
    }

    /** @test */
    public function it_validates_packages_collection_with_mixed_types()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $packages = collect([
            new Package(['weight' => 10.0]),
            new \stdClass(), // Invalid type
            new Package(['weight' => 5.0])
        ]);
        
        $this->service->calculateTotalWeight($packages);
    }
}