<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Manifest;
use App\Models\Package;
use App\Http\Livewire\Manifests\EnhancedManifestSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class EnhancedManifestSummaryValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_input_data_correctly()
    {
        $manifest = Manifest::factory()->create();
        
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        $result = $this->invokeMethod($component, 'validateInputData');
        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_rejects_invalid_manifest_input()
    {
        $component = new EnhancedManifestSummary();
        
        // Test with null manifest
        $component->manifest = null;
        $result = $this->invokeMethod($component, 'validateInputData');
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('null', $result['message']);

        // Test with manifest without ID
        $component->manifest = new Manifest();
        $result = $this->invokeMethod($component, 'validateInputData');
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('missing', $result['message']);
    }

    /** @test */
    public function it_validates_packages_collection_correctly()
    {
        $manifest = Manifest::factory()->create();
        $packages = Package::factory()->count(3)->create(['manifest_id' => $manifest->id]);
        
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        $result = $this->invokeMethod($component, 'validatePackagesCollection', [$packages]);
        $this->assertTrue($result['valid']);
        $this->assertEquals(3, $result['total_packages']);
    }

    /** @test */
    public function it_validates_and_sanitizes_display_summary()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        $displaySummary = [
            'manifest_type' => 'air',
            'package_count' => '10',
            'total_value' => '$1,234.56',
            'incomplete_data' => true,
            'primary_metric' => [
                'type' => 'weight',
                'label' => 'Total Weight',
                'value' => '100.5 lbs',
                'secondary' => '45.6 kg',
                'display' => '100.5 lbs (45.6 kg)'
            ]
        ];
        
        $result = $this->invokeMethod($component, 'validateAndSanitizeDisplaySummary', [$displaySummary]);
        
        $this->assertEquals('air', $result['manifest_type']);
        $this->assertEquals(10.0, $result['package_count']);
        $this->assertEquals(1234.56, $result['total_value']);
        $this->assertTrue($result['incomplete_data']);
        $this->assertIsArray($result['primary_metric']);
    }

    /** @test */
    public function it_handles_invalid_display_summary_data()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        // Test with invalid data types
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
    public function it_validates_numeric_values_correctly()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        // Test valid values
        $this->assertTrue($this->invokeMethod($component, 'isValidNumericValue', [100]));
        $this->assertTrue($this->invokeMethod($component, 'isValidNumericValue', [0]));
        $this->assertTrue($this->invokeMethod($component, 'isValidNumericValue', [null]));
        
        // Test invalid values
        $this->assertFalse($this->invokeMethod($component, 'isValidNumericValue', ['abc']));
        $this->assertFalse($this->invokeMethod($component, 'isValidNumericValue', [INF]));
        $this->assertFalse($this->invokeMethod($component, 'isValidNumericValue', [NAN]));
        $this->assertFalse($this->invokeMethod($component, 'isValidNumericValue', [2000000])); // Too large
    }

    /** @test */
    public function it_sanitizes_numeric_values_with_range_validation()
    {
        $manifest = Manifest::factory()->create();
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
        
        // Test invalid input
        $result = $this->invokeMethod($component, 'validateAndSanitizeNumericValue', ['invalid', 0, 1000, 'test']);
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function it_sanitizes_string_values_correctly()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        // Test normal string
        $result = $this->invokeMethod($component, 'sanitizeString', ['Hello World']);
        $this->assertEquals('Hello World', $result);
        
        // Test HTML removal
        $result = $this->invokeMethod($component, 'sanitizeString', ['<script>alert("xss")</script>']);
        $this->assertEquals('alert("xss")', $result);
        
        // Test length limiting
        $longString = str_repeat('a', 300);
        $result = $this->invokeMethod($component, 'sanitizeString', [$longString, 100]);
        $this->assertEquals(100, strlen($result));
        
        // Test null input
        $result = $this->invokeMethod($component, 'sanitizeString', [null]);
        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_sanitizes_array_values_recursively()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        $testArray = [
            'string_value' => 'Hello <script>World</script>',
            'numeric_value' => 123,
            'boolean_value' => true,
            'nested_array' => [
                'nested_string' => 'Test & "quotes"',
                'nested_number' => 456
            ]
        ];
        
        $result = $this->invokeMethod($component, 'sanitizeArray', [$testArray]);
        
        $this->assertEquals('Hello World', $result['string_value']);
        $this->assertEquals(123, $result['numeric_value']);
        $this->assertTrue($result['boolean_value']);
        $this->assertIsArray($result['nested_array']);
        $this->assertEquals('Test &amp; &quot;quotes&quot;', $result['nested_array']['nested_string']);
        $this->assertEquals(456, $result['nested_array']['nested_number']);
    }

    /** @test */
    public function it_validates_primary_metric_data()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        $metric = [
            'type' => 'weight',
            'label' => 'Total Weight',
            'value' => '100.5 lbs',
            'secondary' => '45.6 kg',
            'display' => '100.5 lbs (45.6 kg)'
        ];
        
        $result = $this->invokeMethod($component, 'validateAndSanitizePrimaryMetric', [$metric]);
        
        $this->assertEquals('weight', $result['type']);
        $this->assertEquals('Total Weight', $result['label']);
        $this->assertEquals('100.5 lbs', $result['value']);
        $this->assertEquals('45.6 kg', $result['secondary']);
        $this->assertEquals('100.5 lbs (45.6 kg)', $result['display']);
    }

    /** @test */
    public function it_handles_invalid_primary_metric_data()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        $metric = [
            'type' => 'invalid_type',
            'label' => null,
            'value' => ['not_a_string'],
        ];
        
        $result = $this->invokeMethod($component, 'validateAndSanitizePrimaryMetric', [$metric]);
        
        $this->assertEquals('weight', $result['type']); // Should default to weight
        $this->assertEquals('Metric', $result['label']); // Should use default
        $this->assertEquals('Array', $result['value']); // Should convert array to string
        $this->assertNull($result['secondary']);
    }

    /** @test */
    public function it_performs_progressive_validation()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        $component->manifestType = 'air';
        $component->hasIncompleteData = true;
        $component->summary = [
            'package_count' => 0,
            'total_value' => 0.0
        ];
        
        $this->invokeMethod($component, 'performProgressiveValidation');
        
        // Should have validation warnings
        $this->assertArrayHasKey('validation_warnings', $component->summary);
        $this->assertNotEmpty($component->summary['validation_warnings']);
        
        // Check for expected warnings
        $warnings = $component->summary['validation_warnings'];
        $this->assertContains('No packages found in this manifest', $warnings);
        $this->assertContains('Total value is zero - package pricing may be incomplete', $warnings);
        $this->assertContains('Weight information is missing for air manifest', $warnings);
        $this->assertContains('Some package data is incomplete - calculations may not be accurate', $warnings);
    }

    /** @test */
    public function it_sanitizes_metric_values_correctly()
    {
        $manifest = Manifest::factory()->create();
        $component = new EnhancedManifestSummary();
        $component->manifest = $manifest;
        
        // Test normal metric value
        $result = $this->invokeMethod($component, 'sanitizeMetricValue', ['100.5 lbs']);
        $this->assertEquals('100.5 lbs', $result);
        
        // Test value with potentially harmful content
        $result = $this->invokeMethod($component, 'sanitizeMetricValue', ['100.5<script>alert("xss")</script> lbs']);
        $this->assertEquals('100.5alert("xss") lbs', $result);
        
        // Test null/empty values
        $result = $this->invokeMethod($component, 'sanitizeMetricValue', [null]);
        $this->assertEquals('0.0', $result);
        
        $result = $this->invokeMethod($component, 'sanitizeMetricValue', ['']);
        $this->assertEquals('0.0', $result);
        
        // Test non-string input
        $result = $this->invokeMethod($component, 'sanitizeMetricValue', [123]);
        $this->assertEquals('123', $result);
        
        // Test value without numeric data
        $result = $this->invokeMethod($component, 'sanitizeMetricValue', ['no numbers here']);
        $this->assertEquals('0.0', $result);
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