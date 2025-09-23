<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ManifestSummaryService;
use App\Services\WeightCalculationService;
use App\Services\VolumeCalculationService;
use App\Models\Manifest;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ManifestSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestSummaryService $service;
    protected WeightCalculationService $weightService;
    protected VolumeCalculationService $volumeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->weightService = new WeightCalculationService();
        $this->volumeService = new VolumeCalculationService();
        $this->service = new ManifestSummaryService($this->weightService, $this->volumeService);
    }

    /** @test */
    public function it_determines_air_manifest_type_from_type_field()
    {
        $manifest = new Manifest();
        $manifest->type = 'air';

        $type = $this->service->getManifestType($manifest);

        $this->assertEquals('air', $type);
    }

    /** @test */
    public function it_determines_sea_manifest_type_from_type_field()
    {
        $manifest = new Manifest();
        $manifest->type = 'SEA'; // Test case insensitivity

        $type = $this->service->getManifestType($manifest);

        $this->assertEquals('sea', $type);
    }

    /** @test */
    public function it_determines_sea_manifest_type_from_vessel_fields()
    {
        $manifest = new Manifest();
        $manifest->type = null;
        $manifest->vessel_name = 'Test Vessel';
        $manifest->voyage_number = 'V123';

        $type = $this->service->getManifestType($manifest);

        $this->assertEquals('sea', $type);
    }

    /** @test */
    public function it_determines_air_manifest_type_from_flight_fields()
    {
        $manifest = new Manifest();
        $manifest->type = null;
        $manifest->flight_number = 'FL123';
        $manifest->flight_destination = 'JFK';

        $type = $this->service->getManifestType($manifest);

        $this->assertEquals('air', $type);
    }

    /** @test */
    public function it_returns_unknown_for_indeterminate_manifest_type()
    {
        $manifest = new Manifest();
        $manifest->type = null;

        $type = $this->service->getManifestType($manifest);

        $this->assertEquals('unknown', $type);
    }

    /** @test */
    public function it_calculates_air_manifest_summary()
    {
        $packages = collect([
            $this->createPackageWithWeight(10.0),
            $this->createPackageWithWeight(20.0),
        ]);

        $summary = $this->service->calculateAirManifestSummary($packages);

        $this->assertEquals('weight', $summary['primary_metric']);
        $this->assertEquals(30.0, $summary['weight']['total_lbs']);
        $this->assertEquals(13.61, $summary['weight']['total_kg']); // 30 * 0.453592 rounded
        $this->assertEquals(15.0, $summary['weight']['average_lbs']);
        $this->assertTrue($summary['weight_validation']['is_complete']);
        $this->assertFalse($summary['incomplete_data']);
    }

    /** @test */
    public function it_calculates_sea_manifest_summary()
    {
        $packages = collect([
            $this->createPackageWithVolume(5.0),
            $this->createPackageWithVolume(3.0),
        ]);

        $summary = $this->service->calculateSeaManifestSummary($packages);

        $this->assertEquals('volume', $summary['primary_metric']);
        $this->assertEquals(8.0, $summary['volume']['total_cubic_feet']);
        $this->assertEquals(4.0, $summary['volume']['average_cubic_feet']);
        $this->assertTrue($summary['volume_validation']['is_complete']);
        $this->assertFalse($summary['incomplete_data']);
    }

    /** @test */
    public function it_gets_complete_manifest_summary_for_air_manifest()
    {
        $manifest = $this->createAirManifest();
        $packages = collect([
            $this->createPackageWithWeight(15.0, 100.0), // weight, value
            $this->createPackageWithWeight(25.0, 200.0),
        ]);
        
        // Mock the packages relationship
        $manifest->setRelation('packages', $packages);

        $summary = $this->service->getManifestSummary($manifest);

        $this->assertEquals('air', $summary['manifest_type']);
        $this->assertEquals(2, $summary['package_count']);
        $this->assertEquals(300.0, $summary['total_value']);
        $this->assertEquals(40.0, $summary['weight']['total_lbs']);
        $this->assertArrayHasKey('weight_validation', $summary);
    }

    /** @test */
    public function it_gets_complete_manifest_summary_for_sea_manifest()
    {
        $manifest = $this->createSeaManifest();
        $packages = collect([
            $this->createPackageWithVolume(3.0, 150.0), // volume, value
            $this->createPackageWithVolume(5.0, 250.0),
        ]);
        
        // Mock the packages relationship
        $manifest->setRelation('packages', $packages);

        $summary = $this->service->getManifestSummary($manifest);

        $this->assertEquals('sea', $summary['manifest_type']);
        $this->assertEquals(2, $summary['package_count']);
        $this->assertEquals(400.0, $summary['total_value']);
        $this->assertEquals(8.0, $summary['volume']['total_cubic_feet']);
        $this->assertArrayHasKey('volume_validation', $summary);
    }

    /** @test */
    public function it_gets_display_summary_for_air_manifest()
    {
        $manifest = $this->createAirManifest();
        $packages = collect([
            $this->createPackageWithWeight(20.0, 100.0),
        ]);
        
        $manifest->setRelation('packages', $packages);

        $displaySummary = $this->service->getDisplaySummary($manifest);

        $this->assertEquals('air', $displaySummary['manifest_type']);
        $this->assertEquals(1, $displaySummary['package_count']);
        $this->assertEquals('100.00', $displaySummary['total_value']);
        $this->assertEquals('weight', $displaySummary['primary_metric']['type']);
        $this->assertEquals('Total Weight', $displaySummary['primary_metric']['label']);
        $this->assertStringContainsString('20.0 lbs', $displaySummary['primary_metric']['display']);
    }

    /** @test */
    public function it_gets_display_summary_for_sea_manifest()
    {
        $manifest = $this->createSeaManifest();
        $packages = collect([
            $this->createPackageWithVolume(4.5, 200.0),
        ]);
        
        $manifest->setRelation('packages', $packages);

        $displaySummary = $this->service->getDisplaySummary($manifest);

        $this->assertEquals('sea', $displaySummary['manifest_type']);
        $this->assertEquals(1, $displaySummary['package_count']);
        $this->assertEquals('200.00', $displaySummary['total_value']);
        $this->assertEquals('volume', $displaySummary['primary_metric']['type']);
        $this->assertEquals('Total Volume', $displaySummary['primary_metric']['label']);
        $this->assertStringContainsString('4.50 ft³', $displaySummary['primary_metric']['display']);
    }

    /** @test */
    public function it_gets_validation_warnings_for_incomplete_air_manifest()
    {
        $manifest = $this->createAirManifest();
        $packages = collect([
            $this->createPackageWithWeight(20.0, 100.0, 'PKG001'),
            $this->createPackageWithWeight(null, 150.0, 'PKG002'), // Missing weight
        ]);
        
        $manifest->setRelation('packages', $packages);

        $warnings = $this->service->getValidationWarnings($manifest);

        $this->assertCount(1, $warnings);
        $this->assertEquals('weight', $warnings[0]['type']);
        $this->assertStringContainsString('Weight data missing for 1 out of 2 packages', $warnings[0]['message']);
        $this->assertEquals(50.0, $warnings[0]['completion_percentage']);
        $this->assertEquals(['PKG002'], $warnings[0]['missing_packages']);
    }

    /** @test */
    public function it_gets_validation_warnings_for_incomplete_sea_manifest()
    {
        $manifest = $this->createSeaManifest();
        $packages = collect([
            $this->createPackageWithVolume(3.0, 100.0, 'PKG001'),
            $this->createPackageWithVolume(null, 150.0, 'PKG002'), // Missing volume
            $this->createPackageWithVolume(null, 200.0, 'PKG003'), // Missing volume
        ]);
        
        $manifest->setRelation('packages', $packages);

        $warnings = $this->service->getValidationWarnings($manifest);

        $this->assertCount(1, $warnings);
        $this->assertEquals('volume', $warnings[0]['type']);
        $this->assertStringContainsString('Volume data missing for 2 out of 3 packages', $warnings[0]['message']);
        $this->assertEquals(33.3, $warnings[0]['completion_percentage']);
        $this->assertEquals(['PKG002', 'PKG003'], $warnings[0]['missing_packages']);
    }

    /** @test */
    public function it_returns_no_warnings_for_complete_data()
    {
        $manifest = $this->createAirManifest();
        $packages = collect([
            $this->createPackageWithWeight(20.0, 100.0, 'PKG001'),
            $this->createPackageWithWeight(15.0, 150.0, 'PKG002'),
        ]);
        
        $manifest->setRelation('packages', $packages);

        $warnings = $this->service->getValidationWarnings($manifest);

        $this->assertEmpty($warnings);
    }

    /** @test */
    public function it_checks_complete_data_status()
    {
        $manifest = $this->createAirManifest();
        $completePackages = collect([
            $this->createPackageWithWeight(20.0, 100.0),
        ]);
        $manifest->setRelation('packages', $completePackages);

        $this->assertTrue($this->service->hasCompleteData($manifest));

        $incompletePackages = collect([
            $this->createPackageWithWeight(20.0, 100.0),
            $this->createPackageWithWeight(null, 150.0), // Missing weight
        ]);
        $manifest->setRelation('packages', $incompletePackages);

        $this->assertFalse($this->service->hasCompleteData($manifest));
    }

    /** @test */
    public function it_handles_unknown_manifest_type_with_both_metrics()
    {
        $manifest = new Manifest(); // Unknown type
        $packages = collect([
            $this->createPackageWithWeightAndVolume(20.0, 3.0, 100.0),
        ]);
        
        $manifest->setRelation('packages', $packages);

        $summary = $this->service->getManifestSummary($manifest);

        $this->assertEquals('unknown', $summary['manifest_type']);
        $this->assertArrayHasKey('weight', $summary);
        $this->assertArrayHasKey('volume', $summary);
        $this->assertEquals(20.0, $summary['weight']['total_lbs']);
        $this->assertEquals(3.0, $summary['volume']['total_cubic_feet']);
    }

    /**
     * Create an air manifest
     */
    private function createAirManifest(): Manifest
    {
        $manifest = new Manifest();
        $manifest->type = 'air';
        $manifest->flight_number = 'FL123';
        return $manifest;
    }

    /**
     * Create a sea manifest
     */
    private function createSeaManifest(): Manifest
    {
        $manifest = new Manifest();
        $manifest->type = 'sea';
        $manifest->vessel_name = 'Test Vessel';
        return $manifest;
    }

    /**
     * Create a package with weight and estimated value
     */
    private function createPackageWithWeight(?float $weight, ?float $value = null, string $trackingNumber = null): Package
    {
        $package = new Package();
        $package->weight = $weight;
        $package->estimated_value = $value;
        $package->tracking_number = $trackingNumber ?? 'PKG' . rand(1000, 9999);
        
        // Add cost fields so total_cost works correctly
        if ($value !== null) {
            // Split the value into cost components for realistic total_cost calculation
            $package->freight_price = $value * 0.6; // 60% of value
            $package->clearance_fee = $value * 0.2;  // 20% of value
            $package->storage_fee = $value * 0.15;  // 15% of value
            $package->delivery_fee = $value * 0.05; // 5% of value
            // total_cost will be 100% of the original value
        }
        
        return $package;
    }

    /**
     * Create a package with volume and estimated value
     */
    private function createPackageWithVolume(?float $volume, ?float $value = null, string $trackingNumber = null): Package
    {
        $package = new Package();
        $package->cubic_feet = $volume;
        $package->estimated_value = $value;
        $package->tracking_number = $trackingNumber ?? 'PKG' . rand(1000, 9999);
        
        // Add cost fields so total_cost works correctly
        if ($value !== null) {
            // Split the value into cost components for realistic total_cost calculation
            $package->freight_price = $value * 0.6; // 60% of value
            $package->clearance_fee = $value * 0.2;  // 20% of value
            $package->storage_fee = $value * 0.15;  // 15% of value
            $package->delivery_fee = $value * 0.05; // 5% of value
            // total_cost will be 100% of the original value
        }
        
        return $package;
    }

    /**
     * Create a package with both weight and volume
     */
    private function createPackageWithWeightAndVolume(?float $weight, ?float $volume, ?float $value = null): Package
    {
        $package = new Package();
        $package->weight = $weight;
        $package->cubic_feet = $volume;
        $package->estimated_value = $value;
        $package->tracking_number = 'PKG' . rand(1000, 9999);
        
        // Add cost fields so total_cost works correctly
        if ($value !== null) {
            // Split the value into cost components for realistic total_cost calculation
            $package->freight_price = $value * 0.6; // 60% of value
            $package->clearance_fee = $value * 0.2;  // 20% of value
            $package->storage_fee = $value * 0.15;  // 15% of value
            $package->delivery_fee = $value * 0.05; // 5% of value
            // total_cost will be 100% of the original value
        }
        
        return $package;
    }
}