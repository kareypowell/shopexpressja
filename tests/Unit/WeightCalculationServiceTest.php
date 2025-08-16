<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WeightCalculationService;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeightCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WeightCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WeightCalculationService();
    }

    /** @test */
    public function it_calculates_total_weight_from_packages()
    {
        $packages = collect([
            $this->createPackageWithWeight(10.5),
            $this->createPackageWithWeight(25.0),
            $this->createPackageWithWeight(5.25),
        ]);

        $totalWeight = $this->service->calculateTotalWeight($packages);

        $this->assertEquals(40.75, $totalWeight);
    }

    /** @test */
    public function it_handles_packages_with_null_weight()
    {
        $packages = collect([
            $this->createPackageWithWeight(10.0),
            $this->createPackageWithWeight(null),
            $this->createPackageWithWeight(15.0),
        ]);

        $totalWeight = $this->service->calculateTotalWeight($packages);

        $this->assertEquals(25.0, $totalWeight);
    }

    /** @test */
    public function it_handles_empty_package_collection()
    {
        $packages = collect([]);

        $totalWeight = $this->service->calculateTotalWeight($packages);

        $this->assertEquals(0, $totalWeight);
    }

    /** @test */
    public function it_converts_pounds_to_kilograms_correctly()
    {
        $lbs = 100;
        $kg = $this->service->convertLbsToKg($lbs);

        $this->assertEquals(45.36, $kg); // 100 * 0.453592 rounded to 2 decimals
    }

    /** @test */
    public function it_converts_fractional_pounds_to_kilograms()
    {
        $lbs = 22.5;
        $kg = $this->service->convertLbsToKg($lbs);

        $this->assertEquals(10.21, $kg); // 22.5 * 0.453592 rounded to 2 decimals
    }

    /** @test */
    public function it_formats_weight_units_correctly()
    {
        $lbs = 150.75;
        $formatted = $this->service->formatWeightUnits($lbs);

        $expectedKg = round(150.75 * 0.453592, 2); // 68.38

        $this->assertEquals([
            'lbs' => '150.8 lbs',
            'kg' => '68.4 kg', // Formatted to 1 decimal place
            'raw_lbs' => 150.75,
            'raw_kg' => $expectedKg, // Raw value keeps 2 decimal places
            'display' => '150.8 lbs (68.4 kg)' // Display uses 1 decimal place
        ], $formatted);
    }

    /** @test */
    public function it_formats_weight_units_with_provided_kg()
    {
        $lbs = 100;
        $kg = 45.36;
        $formatted = $this->service->formatWeightUnits($lbs, $kg);

        $this->assertEquals([
            'lbs' => '100.0 lbs',
            'kg' => '45.4 kg',
            'raw_lbs' => 100,
            'raw_kg' => 45.36,
            'display' => '100.0 lbs (45.4 kg)'
        ], $formatted);
    }

    /** @test */
    public function it_validates_complete_weight_data()
    {
        $packages = collect([
            $this->createPackageWithWeight(10.0, 'PKG001'),
            $this->createPackageWithWeight(20.0, 'PKG002'),
            $this->createPackageWithWeight(15.0, 'PKG003'),
        ]);

        $validation = $this->service->validateWeightData($packages);

        $this->assertEquals([
            'total_packages' => 3,
            'packages_with_weight' => 3,
            'packages_missing_weight' => 0,
            'is_complete' => true,
            'completion_percentage' => 100.0,
            'missing_weight_tracking_numbers' => []
        ], $validation);
    }

    /** @test */
    public function it_validates_incomplete_weight_data()
    {
        $packages = collect([
            $this->createPackageWithWeight(10.0, 'PKG001'),
            $this->createPackageWithWeight(null, 'PKG002'),
            $this->createPackageWithWeight(0, 'PKG003'),
            $this->createPackageWithWeight(15.0, 'PKG004'),
        ]);

        $validation = $this->service->validateWeightData($packages);

        $this->assertEquals([
            'total_packages' => 4,
            'packages_with_weight' => 2,
            'packages_missing_weight' => 2,
            'is_complete' => false,
            'completion_percentage' => 50.0,
            'missing_weight_tracking_numbers' => ['PKG002', 'PKG003']
        ], $validation);
    }

    /** @test */
    public function it_validates_empty_package_collection()
    {
        $packages = collect([]);

        $validation = $this->service->validateWeightData($packages);

        $this->assertEquals([
            'total_packages' => 0,
            'packages_with_weight' => 0,
            'packages_missing_weight' => 0,
            'is_complete' => true,
            'completion_percentage' => 0,
            'missing_weight_tracking_numbers' => []
        ], $validation);
    }

    /** @test */
    public function it_calculates_weight_statistics()
    {
        $packages = collect([
            $this->createPackageWithWeight(10.0),
            $this->createPackageWithWeight(20.0),
            $this->createPackageWithWeight(30.0),
        ]);

        $stats = $this->service->getWeightStatistics($packages);

        $this->assertEquals(60.0, $stats['total_weight_lbs']);
        $this->assertEquals(27.22, $stats['total_weight_kg']); // 60 * 0.453592 rounded
        $this->assertEquals(20.0, $stats['average_weight_lbs']);
        $this->assertEquals(10.0, $stats['min_weight_lbs']);
        $this->assertEquals(30.0, $stats['max_weight_lbs']);
        $this->assertArrayHasKey('formatted', $stats);
    }

    /** @test */
    public function it_handles_statistics_with_no_weight_data()
    {
        $packages = collect([
            $this->createPackageWithWeight(null),
            $this->createPackageWithWeight(0),
        ]);

        $stats = $this->service->getWeightStatistics($packages);

        $this->assertEquals(0, $stats['total_weight_lbs']);
        $this->assertEquals(0, $stats['total_weight_kg']);
        $this->assertEquals(0, $stats['average_weight_lbs']);
        $this->assertEquals(0, $stats['min_weight_lbs']);
        $this->assertEquals(0, $stats['max_weight_lbs']);
    }

    /** @test */
    public function it_handles_statistics_with_mixed_weight_data()
    {
        $packages = collect([
            $this->createPackageWithWeight(10.0),
            $this->createPackageWithWeight(null),
            $this->createPackageWithWeight(20.0),
            $this->createPackageWithWeight(0),
        ]);

        $stats = $this->service->getWeightStatistics($packages);

        // Should only consider packages with valid weight (10.0 and 20.0)
        $this->assertEquals(30.0, $stats['total_weight_lbs']);
        $this->assertEquals(15.0, $stats['average_weight_lbs']);
        $this->assertEquals(10.0, $stats['min_weight_lbs']);
        $this->assertEquals(20.0, $stats['max_weight_lbs']);
    }

    /**
     * Create a mock package with specified weight
     */
    private function createPackageWithWeight(?float $weight, string $trackingNumber = null): Package
    {
        $package = new Package();
        $package->weight = $weight;
        $package->tracking_number = $trackingNumber ?? 'PKG' . rand(1000, 9999);
        return $package;
    }
}