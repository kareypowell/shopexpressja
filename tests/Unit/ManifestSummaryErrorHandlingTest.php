<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ManifestSummaryService;
use App\Services\WeightCalculationService;
use App\Services\VolumeCalculationService;
use App\Models\Manifest;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;

class ManifestSummaryErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestSummaryService $service;
    protected $weightService;
    protected $volumeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->weightService = Mockery::mock(WeightCalculationService::class);
        $this->volumeService = Mockery::mock(VolumeCalculationService::class);
        
        $this->service = new ManifestSummaryService(
            $this->weightService,
            $this->volumeService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_weight_service_exceptions_gracefully()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $packages = collect([Package::factory()->make(['weight' => 10.0])]);
        $manifest->setRelation('packages', $packages);
        
        // Mock weight service to throw exception
        $this->weightService->shouldReceive('getWeightStatistics')
            ->andThrow(new \Exception('Weight calculation failed'));
        
        $this->weightService->shouldReceive('validateWeightData')
            ->andThrow(new \Exception('Weight validation failed'));
        
        $this->weightService->shouldReceive('formatWeightUnits')
            ->with(0)
            ->andReturn([
                'lbs' => '0.0 lbs',
                'kg' => '0.0 kg',
                'display' => '0.0 lbs (0.0 kg)'
            ]);
        
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertEquals('air', $result['manifest_type']);
        $this->assertTrue($result['incomplete_data']);
        $this->assertEquals(0, $result['weight']['total_lbs']);
        
        Log::assertLogged('error', function ($log) {
            return str_contains($log['message'], 'Failed to calculate air manifest summary');
        });
    }

    /** @test */
    public function it_handles_volume_service_exceptions_gracefully()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        $packages = collect([Package::factory()->make(['cubic_feet' => 5.0])]);
        $manifest->setRelation('packages', $packages);
        
        // Mock volume service to throw exception
        $this->volumeService->shouldReceive('getVolumeStatistics')
            ->andThrow(new \Exception('Volume calculation failed'));
        
        $this->volumeService->shouldReceive('validateVolumeData')
            ->andThrow(new \Exception('Volume validation failed'));
        
        $this->volumeService->shouldReceive('getVolumeDisplayData')
            ->with(0)
            ->andReturn([
                'display' => '0.0 ft³',
                'cubic_feet' => '0.0',
                'raw_value' => 0.0
            ]);
        
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertEquals('sea', $result['manifest_type']);
        $this->assertTrue($result['incomplete_data']);
        $this->assertEquals(0, $result['volume']['total_cubic_feet']);
        
        Log::assertLogged('error', function ($log) {
            return str_contains($log['message'], 'Failed to calculate sea manifest summary');
        });
    }

    /** @test */
    public function it_handles_manifest_type_determination_errors()
    {
        Log::fake();
        
        // Create manifest with null type and no identifying fields
        $manifest = new Manifest();
        $packages = collect();
        $manifest->setRelation('packages', $packages);
        
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertEquals('unknown', $result['manifest_type']);
    }

    /** @test */
    public function it_handles_total_value_calculation_errors()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        // Create packages with invalid cost values
        $packages = collect([
            new Package(['total_cost' => 'invalid']),
            new Package(['total_cost' => -100.0]),
            new Package(['total_cost' => 200000.0]), // Extremely high
            new Package(['total_cost' => 50.0])
        ]);
        $manifest->setRelation('packages', $packages);
        
        $this->weightService->shouldReceive('getWeightStatistics')->andReturn([
            'total_weight_lbs' => 0,
            'total_weight_kg' => 0,
            'average_weight_lbs' => 0,
            'average_weight_kg' => 0,
            'formatted' => ['lbs' => '0.0 lbs', 'kg' => '0.0 kg']
        ]);
        
        $this->weightService->shouldReceive('validateWeightData')->andReturn([
            'is_complete' => false
        ]);
        
        $this->weightService->shouldReceive('validateCalculationResults')
            ->andReturnUsing(function ($input) { return $input; });
        
        $result = $this->service->getManifestSummary($manifest);
        
        // Should handle invalid values: 0 + 0 + 100000 (capped) + 50 = 100050
        $this->assertEquals(100050.0, $result['total_value']);
        
        Log::assertLogged('warning', function ($log) {
            return str_contains($log['message'], 'Invalid cost value found') ||
                   str_contains($log['message'], 'Extremely high cost value found');
        });
    }

    /** @test */
    public function it_returns_fallback_summary_on_complete_failure()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $packages = collect([Package::factory()->make()]);
        $manifest->setRelation('packages', $packages);
        
        // Mock all services to throw exceptions
        $this->weightService->shouldReceive('getWeightStatistics')
            ->andThrow(new \Exception('Complete failure'));
        
        $this->weightService->shouldReceive('formatWeightUnits')
            ->with(0)
            ->andReturn([
                'lbs' => '0.0 lbs',
                'kg' => '0.0 kg',
                'display' => '0.0 lbs (0.0 kg)'
            ]);
        
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertEquals('air', $result['manifest_type']);
        $this->assertEquals(0, $result['package_count']);
        $this->assertEquals(0.0, $result['total_value']);
        $this->assertTrue($result['incomplete_data']);
        
        Log::assertLogged('error', function ($log) {
            return str_contains($log['message'], 'Failed to get manifest summary');
        });
    }

    /** @test */
    public function it_validates_summary_data_recursively()
    {
        Log::fake();
        
        $summaryData = [
            'weight' => [
                'total_lbs' => -50.0, // Invalid
                'average_lbs' => 25.0,
                'nested' => [
                    'value' => -10.0 // Invalid nested value
                ]
            ],
            'valid_field' => 'test'
        ];
        
        $validated = $this->service->validateSummaryData($summaryData);
        
        $this->assertEquals(0, $validated['weight']['total_lbs']);
        $this->assertEquals(25.0, $validated['weight']['average_lbs']);
        $this->assertEquals(0, $validated['weight']['nested']['value']);
        $this->assertEquals('test', $validated['valid_field']);
        
        Log::assertLogged('warning');
    }

    /** @test */
    public function it_handles_display_summary_calculation_errors()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $packages = collect([Package::factory()->make()]);
        $manifest->setRelation('packages', $packages);
        
        // Mock getManifestSummary to throw exception
        $service = Mockery::mock(ManifestSummaryService::class)->makePartial();
        $service->shouldReceive('getManifestSummary')
            ->andThrow(new \Exception('Summary calculation failed'));
        
        $result = $service->getDisplaySummary($manifest);
        
        // Should return safe defaults
        $this->assertArrayHasKey('manifest_type', $result);
        $this->assertArrayHasKey('package_count', $result);
        $this->assertArrayHasKey('total_value', $result);
        $this->assertTrue($result['incomplete_data']);
    }

    /** @test */
    public function it_handles_validation_warnings_calculation_errors()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $packages = collect([Package::factory()->make()]);
        $manifest->setRelation('packages', $packages);
        
        // Mock getManifestSummary to throw exception
        $service = Mockery::mock(ManifestSummaryService::class)->makePartial();
        $service->shouldReceive('getManifestSummary')
            ->andThrow(new \Exception('Summary calculation failed'));
        
        $result = $service->getValidationWarnings($manifest);
        
        // Should return empty array on error
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_handles_has_complete_data_check_errors()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $packages = collect([Package::factory()->make()]);
        $manifest->setRelation('packages', $packages);
        
        // Mock getManifestSummary to throw exception
        $service = Mockery::mock(ManifestSummaryService::class)->makePartial();
        $service->shouldReceive('getManifestSummary')
            ->andThrow(new \Exception('Summary calculation failed'));
        
        $result = $service->hasCompleteData($manifest);
        
        // Should return false on error (assume incomplete)
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_packages_relation_loading_errors()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        // Mock packages relation to throw exception
        $manifest = new class extends Manifest {
            public function getPackagesAttribute()
            {
                throw new \Exception('Database connection failed');
            }
        };
        $manifest->type = 'air';
        $manifest->id = 1;
        
        $this->weightService->shouldReceive('formatWeightUnits')
            ->with(0)
            ->andReturn([
                'lbs' => '0.0 lbs',
                'kg' => '0.0 kg',
                'display' => '0.0 lbs (0.0 kg)'
            ]);
        
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertEquals('air', $result['manifest_type']);
        $this->assertEquals(0, $result['package_count']);
        $this->assertTrue($result['incomplete_data']);
        
        Log::assertLogged('error');
    }

    /** @test */
    public function it_handles_manifest_type_determination_with_partial_data()
    {
        // Test with vessel information (should be sea)
        $manifest1 = new Manifest(['vessel_name' => 'Test Vessel']);
        $this->assertEquals('sea', $this->service->getManifestType($manifest1));
        
        // Test with flight information (should be air)
        $manifest2 = new Manifest(['flight_number' => 'FL123']);
        $this->assertEquals('air', $this->service->getManifestType($manifest2));
        
        // Test with no identifying information (should be unknown)
        $manifest3 = new Manifest();
        $this->assertEquals('unknown', $this->service->getManifestType($manifest3));
    }

    /** @test */
    public function it_handles_extremely_large_package_collections()
    {
        Log::fake();
        
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        // Create a large collection that might cause memory issues
        $packages = collect();
        for ($i = 0; $i < 10000; $i++) {
            $packages->push(new Package(['total_cost' => 10.0]));
        }
        $manifest->setRelation('packages', $packages);
        
        $this->weightService->shouldReceive('getWeightStatistics')->andReturn([
            'total_weight_lbs' => 0,
            'total_weight_kg' => 0,
            'average_weight_lbs' => 0,
            'average_weight_kg' => 0,
            'formatted' => ['lbs' => '0.0 lbs', 'kg' => '0.0 kg']
        ]);
        
        $this->weightService->shouldReceive('validateWeightData')->andReturn([
            'is_complete' => true
        ]);
        
        $this->weightService->shouldReceive('validateCalculationResults')
            ->andReturnUsing(function ($input) { return $input; });
        
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertEquals(10000, $result['package_count']);
        $this->assertEquals(100000.0, $result['total_value']); // 10000 * 10.0
    }

    /** @test */
    public function it_handles_fallback_summary_type_determination_errors()
    {
        Log::fake();
        
        // Create a manifest that will cause getManifestType to throw exception
        $manifest = new class extends Manifest {
            public function __get($key)
            {
                if ($key === 'type') {
                    throw new \Exception('Type access failed');
                }
                return parent::__get($key);
            }
        };
        $manifest->id = 1;
        
        $this->weightService->shouldReceive('formatWeightUnits')
            ->with(0)
            ->andReturn([
                'lbs' => '0.0 lbs',
                'kg' => '0.0 kg',
                'display' => '0.0 lbs (0.0 kg)'
            ]);
        
        $this->volumeService->shouldReceive('getVolumeDisplayData')
            ->with(0)
            ->andReturn([
                'display' => '0.0 ft³',
                'cubic_feet' => '0.0',
                'raw_value' => 0.0
            ]);
        
        $result = $this->service->getFallbackSummary($manifest);
        
        $this->assertEquals('unknown', $result['manifest_type']);
        
        Log::assertLogged('error', function ($log) {
            return str_contains($log['message'], 'Failed to determine manifest type for fallback');
        });
    }
}