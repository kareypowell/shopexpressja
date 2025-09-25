<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Manifest;
use App\Http\Livewire\Manifests\EnhancedManifestSummary;
use App\Services\ManifestSummaryService;
use App\Services\WeightCalculationService;
use App\Services\VolumeCalculationService;

class ValidationFunctionalityTest extends TestCase
{
    /** @test */
    public function it_validates_numeric_values_correctly()
    {
        $service = new ManifestSummaryService(
            app(WeightCalculationService::class),
            app(VolumeCalculationService::class)
        );
        
        // Test valid values
        $this->assertTrue($this->invokeMethod($service, 'isValidNumericValue', [100]));
        $this->assertTrue($this->invokeMethod($service, 'isValidNumericValue', [0]));
        $this->assertTrue($this->invokeMethod($service, 'isValidNumericValue', [null]));
        
        // Test invalid values
        $this->assertFalse($this->invokeMethod($service, 'isValidNumericValue', ['abc']));
        $this->assertFalse($this->invokeMethod($service, 'isValidNumericValue', [INF]));
        $this->assertFalse($this->invokeMethod($service, 'isValidNumericValue', [NAN]));
    }

    /** @test */
    public function it_sanitizes_numeric_values_correctly()
    {
        $service = new ManifestSummaryService(
            app(WeightCalculationService::class),
            app(VolumeCalculationService::class)
        );
        
        // Test normal values
        $this->assertEquals(100.0, $this->invokeMethod($service, 'sanitizeNumericValue', [100]));
        $this->assertEquals(0.0, $this->invokeMethod($service, 'sanitizeNumericValue', [null]));
        
        // Test clamping
        $this->assertEquals(0.0, $this->invokeMethod($service, 'sanitizeNumericValue', [-100]));
        $this->assertEquals(1000000.0, $this->invokeMethod($service, 'sanitizeNumericValue', [2000000]));
    }

    /** @test */
    public function it_sanitizes_string_values_correctly()
    {
        $service = new ManifestSummaryService(
            app(WeightCalculationService::class),
            app(VolumeCalculationService::class)
        );
        
        // Test normal string
        $result = $this->invokeMethod($service, 'sanitizeStringValue', ['Hello World']);
        $this->assertEquals('Hello World', $result);
        
        // Test string with HTML tags
        $result = $this->invokeMethod($service, 'sanitizeStringValue', ['<script>alert("xss")</script>']);
        $this->assertEquals('alert(&quot;xss&quot;)', $result);
    }

    /** @test */
    public function component_validates_display_summary_correctly()
    {
        $manifest = new Manifest(['id' => 1, 'type' => 'air']);
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        $displaySummary = [
            'manifest_type' => 'air',
            'package_count' => '10',
            'total_value' => '$1,234.56',
            'incomplete_data' => true
        ];
        
        $result = $this->invokeMethod($component, 'validateAndSanitizeDisplaySummary', [$displaySummary]);
        
        $this->assertEquals('air', $result['manifest_type']);
        $this->assertEquals(10.0, $result['package_count']);
        $this->assertEquals(1234.56, $result['total_value']);
        $this->assertTrue($result['incomplete_data']);
    }

    /** @test */
    public function component_handles_invalid_display_summary_data()
    {
        $manifest = new Manifest(['id' => 1, 'type' => 'air']);
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        $displaySummary = [
            'manifest_type' => 123, // Should be string
            'package_count' => 'invalid', // Should be numeric
            'total_value' => 'not_a_number',
            'incomplete_data' => 'yes', // Should be boolean
        ];
        
        $result = $this->invokeMethod($component, 'validateAndSanitizeDisplaySummary', [$displaySummary]);
        
        $this->assertEquals('unknown', $result['manifest_type']);
        $this->assertEquals(0.0, $result['package_count']);
        $this->assertEquals(0.0, $result['total_value']);
        $this->assertTrue($result['incomplete_data']); // 'yes' should be truthy
    }

    /** @test */
    public function component_sanitizes_string_values_correctly()
    {
        $manifest = new Manifest(['id' => 1, 'type' => 'air']);
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        // Test normal string
        $result = $this->invokeMethod($component, 'sanitizeString', ['Hello World']);
        $this->assertEquals('Hello World', $result);
        
        // Test null input
        $result = $this->invokeMethod($component, 'sanitizeString', [null]);
        $this->assertEquals('', $result);
        
        // Test array input
        $result = $this->invokeMethod($component, 'sanitizeString', [['test']]);
        $this->assertEquals('Array', $result);
    }

    /** @test */
    public function component_validates_numeric_ranges_correctly()
    {
        $manifest = new Manifest(['id' => 1, 'type' => 'air']);
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        // Test normal values
        $result = $this->invokeMethod($component, 'validateAndSanitizeNumericValue', [100, 0, 1000, 'test']);
        $this->assertEquals(100.0, $result);
        
        // Test clamping to minimum
        $result = $this->invokeMethod($component, 'validateAndSanitizeNumericValue', [-50, 0, 1000, 'test']);
        $this->assertEquals(0.0, $result);
        
        // Test clamping to maximum
        $result = $this->invokeMethod($component, 'validateAndSanitizeNumericValue', [2000, 0, 1000, 'test']);
        $this->assertEquals(1000.0, $result);
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