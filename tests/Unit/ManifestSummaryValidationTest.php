<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Manifest;
use App\Models\Package;
use App\Services\ManifestSummaryService;
use App\Services\WeightCalculationService;
use App\Services\VolumeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

class ManifestSummaryValidationTest extends TestCase
{
    use RefreshDatabase;

    protected ManifestSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ManifestSummaryService(
            app(WeightCalculationService::class),
            app(VolumeCalculationService::class)
        );
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /** @test */
    public function it_validates_manifest_input_correctly()
    {
        // Test with null manifest
        $result = $this->invokeMethod($this->service, 'validateManifestInput', [null]);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('null', $result['message']);

        // Test with invalid manifest ID
        $manifest = new Manifest();
        $result = $this->invokeMethod($this->service, 'validateManifestInput', [$manifest]);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid manifest ID', $result['message']);

        // Test with valid manifest
        $manifest = Manifest::factory()->create();
        $result = $this->invokeMethod($this->service, 'validateManifestInput', [$manifest]);
        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_validates_packages_collection_correctly()
    {
        // Test with empty collection
        $packages = collect([]);
        $result = $this->invokeMethod($this->service, 'validatePackagesCollection', [$packages]);
        $this->assertTrue($result['valid']);
        $this->assertEquals(0, $result['package_count']);

        // Test with mock packages
        $mockPackage1 = new Package(['id' => 1, 'tracking_number' => 'TEST001']);
        $mockPackage2 = new Package(['id' => 2, 'tracking_number' => 'TEST002']);
        $mockPackage3 = new Package(['id' => 3, 'tracking_number' => 'TEST003']);
        
        $packages = collect([$mockPackage1, $mockPackage2, $mockPackage3]);
        $result = $this->invokeMethod($this->service, 'validatePackagesCollection', [$packages]);
        $this->assertTrue($result['valid']);
        $this->assertEquals(3, $result['package_count']);
    }

    /** @test */
    public function it_validates_package_objects_correctly()
    {
        // Test with null package
        $result = $this->invokeMethod($this->service, 'validatePackageObject', [null]);
        $this->assertFalse($result);

        // Test with invalid package ID
        $package = new Package();
        $result = $this->invokeMethod($this->service, 'validatePackageObject', [$package]);
        $this->assertFalse($result);

        // Test with valid package
        $package = new Package(['id' => 1, 'tracking_number' => 'TEST001']);
        $result = $this->invokeMethod($this->service, 'validatePackageObject', [$package]);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_numeric_values_correctly()
    {
        // Test valid numeric values
        $this->assertTrue($this->invokeMethod($this->service, 'isValidNumericValue', [100]));
        $this->assertTrue($this->invokeMethod($this->service, 'isValidNumericValue', [0]));
        $this->assertTrue($this->invokeMethod($this->service, 'isValidNumericValue', [null]));
        $this->assertTrue($this->invokeMethod($this->service, 'isValidNumericValue', ['']));

        // Test invalid numeric values
        $this->assertFalse($this->invokeMethod($this->service, 'isValidNumericValue', ['abc']));
        $this->assertFalse($this->invokeMethod($this->service, 'isValidNumericValue', [1000001])); // Too large
        $this->assertFalse($this->invokeMethod($this->service, 'isValidNumericValue', [-1000001])); // Too small
        $this->assertFalse($this->invokeMethod($this->service, 'isValidNumericValue', [INF]));
        $this->assertFalse($this->invokeMethod($this->service, 'isValidNumericValue', [NAN]));
    }

    /** @test */
    public function it_sanitizes_numeric_values_correctly()
    {
        // Test normal values
        $this->assertEquals(100.0, $this->invokeMethod($this->service, 'sanitizeNumericValue', [100]));
        $this->assertEquals(0.0, $this->invokeMethod($this->service, 'sanitizeNumericValue', [null]));
        $this->assertEquals(0.0, $this->invokeMethod($this->service, 'sanitizeNumericValue', ['']));

        // Test clamping
        $this->assertEquals(0.0, $this->invokeMethod($this->service, 'sanitizeNumericValue', [-100]));
        $this->assertEquals(1000000.0, $this->invokeMethod($this->service, 'sanitizeNumericValue', [2000000]));

        // Test invalid values
        $this->assertEquals(0.0, $this->invokeMethod($this->service, 'sanitizeNumericValue', ['abc']));
        $this->assertEquals(0.0, $this->invokeMethod($this->service, 'sanitizeNumericValue', [INF]));
        $this->assertEquals(0.0, $this->invokeMethod($this->service, 'sanitizeNumericValue', [NAN]));
    }

    /** @test */
    public function it_sanitizes_string_values_correctly()
    {
        // Test normal string
        $result = $this->invokeMethod($this->service, 'sanitizeStringValue', ['Hello World']);
        $this->assertEquals('Hello World', $result);

        // Test string with HTML tags
        $result = $this->invokeMethod($this->service, 'sanitizeStringValue', ['<script>alert("xss")</script>']);
        $this->assertEquals('alert(&quot;xss&quot;)', $result);

        // Test string with special characters
        $result = $this->invokeMethod($this->service, 'sanitizeStringValue', ['Test & "quotes" <tag>']);
        $this->assertEquals('Test &amp; &quot;quotes&quot;', $result);

        // Test length limiting
        $longString = str_repeat('a', 300);
        $result = $this->invokeMethod($this->service, 'sanitizeStringValue', [$longString, 100]);
        $this->assertEquals(100, strlen($result));
    }

    /** @test */
    public function it_sanitizes_validation_data_correctly()
    {
        $validationData = [
            'total_packages' => 10,
            'packages_with_weight' => 8,
            'packages_missing_weight' => 2,
            'is_complete' => true,
            'completion_percentage' => 80,
            'missing_weight_tracking_numbers' => ['TN001', 'TN002']
        ];

        $result = $this->invokeMethod($this->service, 'sanitizeValidationData', [$validationData]);

        $this->assertEquals(10, $result['total_packages']);
        $this->assertEquals(8, $result['packages_with_weight']);
        $this->assertEquals(2, $result['packages_missing_weight']);
        $this->assertTrue($result['is_complete']);
        $this->assertEquals(80, $result['completion_percentage']);
        $this->assertEquals(['TN001', 'TN002'], $result['missing_weight_tracking_numbers']);
    }

    /** @test */
    public function it_handles_invalid_validation_data()
    {
        // Test with non-array input
        $result = $this->invokeMethod($this->service, 'sanitizeValidationData', ['invalid']);
        
        $this->assertEquals(0, $result['total_packages']);
        $this->assertFalse($result['is_complete']);
        $this->assertEquals(0, $result['completion_percentage']);

        // Test with invalid numeric values
        $validationData = [
            'total_packages' => 'invalid',
            'completion_percentage' => 150, // Over 100%
            'missing_weight_tracking_numbers' => 'not_an_array'
        ];

        $result = $this->invokeMethod($this->service, 'sanitizeValidationData', [$validationData]);

        $this->assertEquals(0, $result['total_packages']);
        $this->assertEquals(100, $result['completion_percentage']); // Clamped to max
        $this->assertEquals([], $result['missing_weight_tracking_numbers']);
    }

    /** @test */
    public function it_calculates_total_value_with_validation()
    {
        // Create packages with valid cost data
        $packages = collect([
            new Package([
                'id' => 1,
                'freight_price' => 100.50,
                'clearance_fee' => 25.00,
                'storage_fee' => 10.00,
                'delivery_fee' => 15.00
            ]),
            new Package([
                'id' => 2,
                'freight_price' => 200.00,
                'clearance_fee' => 30.00,
                'storage_fee' => 0,
                'delivery_fee' => 20.00
            ])
        ]);

        $result = $this->invokeMethod($this->service, 'calculateTotalValue', [$packages]);
        
        // Expected: (100.50 + 25 + 10 + 15) + (200 + 30 + 0 + 20) = 150.50 + 250 = 400.50
        $this->assertEquals(400.50, $result);
    }

    /** @test */
    public function it_handles_invalid_cost_data_in_total_value_calculation()
    {
        // Create packages with invalid cost data
        $packages = collect([
            new Package([
                'id' => 1,
                'freight_price' => 'invalid',
                'clearance_fee' => null,
                'storage_fee' => -100, // Negative value
                'delivery_fee' => 200000 // Extremely high value, should be clamped
            ])
        ]);

        $result = $this->invokeMethod($this->service, 'calculateTotalValue', [$packages]);
        
        // Should handle invalid data gracefully
        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /**
     * Helper method to invoke protected/private methods for testing
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}