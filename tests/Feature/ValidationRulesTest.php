<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Rules\ValidVesselInformation;
use App\Rules\ValidContainerDimensions;
use App\Rules\ValidPackageItems;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function valid_vessel_information_rule_works()
    {
        $rule = new ValidVesselInformation('sea', 'vessel_name');
        
        // Should pass for sea manifests with valid vessel name
        $this->assertTrue($rule->passes('vessel_name', 'Test Vessel'));
        
        // Should fail for sea manifests with empty vessel name
        $this->assertFalse($rule->passes('vessel_name', ''));
        $this->assertFalse($rule->passes('vessel_name', '   '));
        
        // Should pass for air manifests (not validated)
        $airRule = new ValidVesselInformation('air', 'vessel_name');
        $this->assertTrue($airRule->passes('vessel_name', ''));
    }

    /** @test */
    public function valid_container_dimensions_rule_works()
    {
        $rule = new ValidContainerDimensions(true, 'length_inches');
        
        // Should pass for valid dimensions
        $this->assertTrue($rule->passes('length_inches', '10'));
        $this->assertTrue($rule->passes('length_inches', '0.1'));
        $this->assertTrue($rule->passes('length_inches', '999'));
        
        // Should fail for invalid dimensions
        $this->assertFalse($rule->passes('length_inches', '0'));
        $this->assertFalse($rule->passes('length_inches', '-5'));
        $this->assertFalse($rule->passes('length_inches', '1001'));
        $this->assertFalse($rule->passes('length_inches', 'abc'));
        
        // Should pass for air manifests (not validated)
        $airRule = new ValidContainerDimensions(false, 'length_inches');
        $this->assertTrue($airRule->passes('length_inches', '0'));
    }

    /** @test */
    public function valid_package_items_rule_works()
    {
        $rule = new ValidPackageItems(true);
        
        // Should pass for valid items
        $validItems = [
            ['description' => 'Test Item', 'quantity' => 1],
            ['description' => 'Another Item', 'quantity' => 5, 'weight_per_item' => '2.5']
        ];
        $this->assertTrue($rule->passes('items', $validItems));
        
        // Should fail for empty items
        $this->assertFalse($rule->passes('items', []));
        
        // Should fail for invalid items
        $invalidItems = [
            ['description' => '', 'quantity' => 0], // Empty description, zero quantity
        ];
        $this->assertFalse($rule->passes('items', $invalidItems));
        
        // Should pass for air manifests (not validated)
        $airRule = new ValidPackageItems(false);
        $this->assertTrue($airRule->passes('items', []));
    }
}