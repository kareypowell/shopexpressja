<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VolumeCalculationService;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VolumeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VolumeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VolumeCalculationService();
    }

    /** @test */
    public function it_calculates_total_volume_from_cubic_feet_field()
    {
        $packages = collect([
            $this->createPackageWithVolume(5.5),
            $this->createPackageWithVolume(10.0),
            $this->createPackageWithVolume(2.25),
        ]);

        $totalVolume = $this->service->calculateTotalVolume($packages);

        $this->assertEquals(17.75, $totalVolume);
    }

    /** @test */
    public function it_calculates_total_volume_from_dimensions()
    {
        $packages = collect([
            $this->createPackageWithDimensions(12, 12, 12), // 1 cubic foot
            $this->createPackageWithDimensions(24, 12, 12), // 2 cubic feet
        ]);

        $totalVolume = $this->service->calculateTotalVolume($packages);

        $this->assertEquals(3.0, $totalVolume);
    }

    /** @test */
    public function it_prefers_cubic_feet_field_over_dimensions()
    {
        $package = $this->createPackageWithVolume(5.0);
        $package->length_inches = 12;
        $package->width_inches = 12;
        $package->height_inches = 12; // Would calculate to 1 cubic foot

        $packages = collect([$package]);
        $totalVolume = $this->service->calculateTotalVolume($packages);

        // Should use cubic_feet field (5.0) instead of calculating from dimensions (1.0)
        $this->assertEquals(5.0, $totalVolume);
    }

    /** @test */
    public function it_handles_packages_with_no_volume_data()
    {
        $packages = collect([
            $this->createPackageWithVolume(5.0),
            $this->createPackageWithVolume(null),
            $this->createPackageWithVolume(3.0),
        ]);

        $totalVolume = $this->service->calculateTotalVolume($packages);

        $this->assertEquals(8.0, $totalVolume);
    }

    /** @test */
    public function it_calculates_volume_from_dimensions_correctly()
    {
        $package = $this->createPackageWithDimensions(24, 18, 12);
        
        $volume = $this->service->calculateVolumeFromDimensions($package);

        // (24 * 18 * 12) / 1728 = 5184 / 1728 = 3.0
        $this->assertEquals(3.0, $volume);
    }

    /** @test */
    public function it_handles_missing_dimensions()
    {
        $package = new Package();
        $package->length_inches = 12;
        $package->width_inches = null;
        $package->height_inches = 12;

        $volume = $this->service->calculateVolumeFromDimensions($package);

        $this->assertEquals(0, $volume);
    }

    /** @test */
    public function it_formats_volume_display_correctly()
    {
        $volume = 15.75;
        $formatted = $this->service->formatVolumeDisplay($volume);

        $this->assertEquals('15.75 cubic feet', $formatted);
    }

    /** @test */
    public function it_gets_detailed_volume_display_data()
    {
        $volume = 12.345;
        $displayData = $this->service->getVolumeDisplayData($volume);

        $this->assertEquals([
            'cubic_feet' => '12.35',
            'display' => '12.35 cubic feet',
            'raw_value' => 12.345,
            'unit' => 'cubic feet'
        ], $displayData);
    }

    /** @test */
    public function it_validates_complete_volume_data()
    {
        $packages = collect([
            $this->createPackageWithVolume(5.0, 'PKG001'),
            $this->createPackageWithDimensions(12, 12, 12, 'PKG002'),
            $this->createPackageWithVolume(3.0, 'PKG003'),
        ]);

        $validation = $this->service->validateVolumeData($packages);

        $this->assertEquals([
            'total_packages' => 3,
            'packages_with_volume' => 3,
            'packages_missing_volume' => 0,
            'is_complete' => true,
            'completion_percentage' => 100.0,
            'missing_volume_tracking_numbers' => []
        ], $validation);
    }

    /** @test */
    public function it_validates_incomplete_volume_data()
    {
        $packages = collect([
            $this->createPackageWithVolume(5.0, 'PKG001'),
            $this->createPackageWithVolume(null, 'PKG002'),
            $this->createPackageWithDimensions(null, 12, 12, 'PKG003'), // Missing length
            $this->createPackageWithVolume(3.0, 'PKG004'),
        ]);

        $validation = $this->service->validateVolumeData($packages);

        $this->assertEquals([
            'total_packages' => 4,
            'packages_with_volume' => 2,
            'packages_missing_volume' => 2,
            'is_complete' => false,
            'completion_percentage' => 50.0,
            'missing_volume_tracking_numbers' => ['PKG002', 'PKG003']
        ], $validation);
    }

    /** @test */
    public function it_detects_volume_data_from_cubic_feet_field()
    {
        $package = $this->createPackageWithVolume(5.0);
        
        $hasVolumeData = $this->service->hasVolumeData($package);

        $this->assertTrue($hasVolumeData);
    }

    /** @test */
    public function it_detects_volume_data_from_dimensions()
    {
        $package = $this->createPackageWithDimensions(12, 12, 12);
        
        $hasVolumeData = $this->service->hasVolumeData($package);

        $this->assertTrue($hasVolumeData);
    }

    /** @test */
    public function it_detects_missing_volume_data()
    {
        $package = new Package();
        $package->cubic_feet = null;
        $package->length_inches = null;
        $package->width_inches = 12;
        $package->height_inches = 12;
        
        $hasVolumeData = $this->service->hasVolumeData($package);

        $this->assertFalse($hasVolumeData);
    }

    /** @test */
    public function it_calculates_volume_statistics()
    {
        $packages = collect([
            $this->createPackageWithVolume(2.0),
            $this->createPackageWithVolume(4.0),
            $this->createPackageWithVolume(6.0),
        ]);

        $stats = $this->service->getVolumeStatistics($packages);

        $this->assertEquals(12.0, $stats['total_volume']);
        $this->assertEquals(4.0, $stats['average_volume']);
        $this->assertEquals(2.0, $stats['min_volume']);
        $this->assertEquals(6.0, $stats['max_volume']);
        $this->assertArrayHasKey('formatted', $stats);
    }

    /** @test */
    public function it_handles_statistics_with_no_volume_data()
    {
        $packages = collect([
            $this->createPackageWithVolume(null),
            new Package(), // No volume data
        ]);

        $stats = $this->service->getVolumeStatistics($packages);

        $this->assertEquals(0, $stats['total_volume']);
        $this->assertEquals(0, $stats['average_volume']);
        $this->assertEquals(0, $stats['min_volume']);
        $this->assertEquals(0, $stats['max_volume']);
    }

    /** @test */
    public function it_estimates_volume_from_weight()
    {
        $package = new Package();
        $package->weight = 22.0; // Should estimate to 2.0 cubic feet (22/11)

        $estimatedVolume = $this->service->estimateVolumeFromWeight($package);

        $this->assertEquals(2.0, $estimatedVolume);
    }

    /** @test */
    public function it_handles_weight_estimation_with_no_weight()
    {
        $package = new Package();
        $package->weight = null;

        $estimatedVolume = $this->service->estimateVolumeFromWeight($package);

        $this->assertEquals(0, $estimatedVolume);
    }

    /** @test */
    public function it_handles_weight_estimation_with_zero_weight()
    {
        $package = new Package();
        $package->weight = 0;

        $estimatedVolume = $this->service->estimateVolumeFromWeight($package);

        $this->assertEquals(0, $estimatedVolume);
    }

    /**
     * Create a mock package with specified cubic feet volume
     */
    private function createPackageWithVolume(?float $cubicFeet, string $trackingNumber = null): Package
    {
        $package = new Package();
        $package->cubic_feet = $cubicFeet;
        $package->tracking_number = $trackingNumber ?? 'PKG' . rand(1000, 9999);
        return $package;
    }

    /**
     * Create a mock package with specified dimensions
     */
    private function createPackageWithDimensions(?float $length, ?float $width, ?float $height, string $trackingNumber = null): Package
    {
        $package = new Package();
        $package->length_inches = $length;
        $package->width_inches = $width;
        $package->height_inches = $height;
        $package->tracking_number = $trackingNumber ?? 'PKG' . rand(1000, 9999);
        return $package;
    }
}