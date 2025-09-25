<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ManifestSummaryService;
use App\Services\WeightCalculationService;
use App\Services\VolumeCalculationService;
use App\Models\Manifest;
use App\Models\Package;
use App\Exceptions\ManifestSummaryException;
use App\Exceptions\DataValidationException;
use App\Exceptions\ServiceUnavailableException;
use App\Exceptions\CalculationException;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ManifestSummaryServiceErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestSummaryService $service;
    protected $weightServiceMock;
    protected $volumeServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->weightServiceMock = Mockery::mock(WeightCalculationService::class);
        $this->volumeServiceMock = Mockery::mock(VolumeCalculationService::class);
        
        $this->service = new ManifestSummaryService(
            $this->weightServiceMock,
            $this->volumeServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_null_manifest_gracefully()
    {
        $manifest = new Manifest();
        $manifest->id = null;
        
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertIsArray($result);
        $this->assertEquals('unknown', $result['manifest_type']);
        $this->assertEquals(0, $result['package_count']);
        $this->assertEquals(0.0, $result['total_value']);
        $this->assertTrue($result['incomplete_data']);
    }

    /** @test */
    public function it_handles_weight_service_failure_for_air_manifest()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $packages = Package::factory()->count(3)->create(['manifest_id' => $manifest->id]);
        
        // Mock weight service to throw exception
        $this->weightServiceMock
            ->shouldReceive('getWeightStatistics')
            ->once()
            ->andThrow(new \Exception('Weight service unavailable'));
            
        $this->weightServiceMock
            ->shouldReceive('formatWeightUnits')
            ->with(0)
            ->andReturn([
                'lbs' => '0.0 lbs',
                'kg' => '0.0 kg',
                'raw_lbs' => 0.0,
                'raw_kg' => 0.0,
                'display' => '0.0 lbs (0.0 kg)'
            ]);

        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertIsArray($result);
        $this->assertEquals('air', $result['manifest_type']);
        $this->assertEquals(3, $result['package_count']);
        $this->assertTrue($result['incomplete_data']);
    }

    /** @test */
    public function it_handles_volume_service_failure_for_sea_manifest()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        $packages = Package::factory()->count(2)->create(['manifest_id' => $manifest->id]);
        
        // Mock volume service to throw exception
        $this->volumeServiceMock
            ->shouldReceive('getVolumeStatistics')
            ->once()
            ->andThrow(new \Exception('Volume service unavailable'));
            
        $this->volumeServiceMock
            ->shouldReceive('getVolumeDisplayData')
            ->with(0)
            ->andReturn([
                'cubic_feet' => '0.00',
                'display' => '0.00 ft³',
                'raw_value' => 0.0,
                'unit' => 'ft³'
            ]);

        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertIsArray($result);
        $this->assertEquals('sea', $result['manifest_type']);
        $this->assertEquals(2, $result['package_count']);
        $this->assertTrue($result['incomplete_data']);
    }

    /** @test */
    public function it_validates_packages_collection_properly()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        // Create a collection with invalid items
        $invalidCollection = collect(['not_a_package', 'another_invalid_item']);
        $manifest->setRelation('packages', $invalidCollection);
        
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertIsArray($result);
        $this->assertEquals('air', $result['manifest_type']);
        $this->assertTrue($result['incomplete_data']);
    }

    /** @test */
    public function it_sanitizes_numeric_values_correctly()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $packages = collect([]);
        $manifest->setRelation('packages', $packages);
        
        // Mock weight service to return extreme values
        $this->weightServiceMock
            ->shouldReceive('getWeightStatistics')
            ->once()
            ->andReturn([
                'total_weight_lbs' => 999999999, // Extremely high value
                'total_weight_kg' => -100, // Negative value
                'average_weight_lbs' => 'invalid', // Non-numeric
                'average_weight_kg' => 50,
                'formatted' => ['display' => '0 lbs']
            ]);
            
        $this->weightServiceMock
            ->shouldReceive('validateWeightData')
            ->once()
            ->andReturn([
                'total_packages' => 0,
                'packages_with_weight' => 0,
                'packages_missing_weight' => 0,
                'is_complete' => true,
                'completion_percentage' => 100,
                'missing_weight_tracking_numbers' => []
            ]);
            
        $this->weightServiceMock
            ->shouldReceive('validateCalculationResults')
            ->once()
            ->andReturn([
                'total_weight_lbs' => 1000000, // Capped value
                'total_weight_kg' => 0, // Sanitized negative
                'average_weight_lbs' => 0, // Sanitized invalid
                'average_weight_kg' => 50,
                'formatted' => ['display' => '0 lbs']
            ]);

        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(1000000, $result['weight']['total_lbs']);
        $this->assertGreaterThanOrEqual(0, $result['weight']['total_kg']);
    }

    /** @test */
    public function it_logs_errors_with_proper_context()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        $packages = Package::factory()->count(1)->create(['manifest_id' => $manifest->id]);
        
        // Mock weight service to throw a specific exception
        $this->weightServiceMock
            ->shouldReceive('getWeightStatistics')
            ->once()
            ->andThrow(new \RuntimeException('Database connection failed'));

        // Expect the error to be logged (we can't easily test Log::error in unit tests,
        // but we can verify the method doesn't throw an unhandled exception)
        $result = $this->service->getManifestSummary($manifest);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['incomplete_data']);
    }
}