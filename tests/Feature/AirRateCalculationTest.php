<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\Rate;
use App\Services\AirRateCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AirRateCalculationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function air_rate_calculator_integrates_with_package_model()
    {
        // Create air manifest
        $manifest = Manifest::create([
            'name' => 'Test Air Manifest',
            'type' => 'air',
            'exchange_rate' => 1.25,
            'shipment_date' => now(),
            'is_open' => true
        ]);

        // Create air rate
        $rate = Rate::create([
            'type' => 'air',
            'weight' => 15,
            'price' => 30.00,
            'processing_fee' => 10.00
        ]);

        // Create air package
        $package = new Package([
            'manifest_id' => $manifest->id,
            'user_id' => 1,
            'tracking_number' => 'AIR123',
            'weight' => 14.7, // Should round up to 15
            'description' => 'Test air package'
        ]);
        
        // Set the manifest relationship
        $package->setRelation('manifest', $manifest);

        // Test that package is correctly identified as air package
        $this->assertTrue($package->isAirPackage());
        $this->assertFalse($package->isSeaPackage());

        // Test air rate calculation
        $calculator = new AirRateCalculator();
        $result = $calculator->calculateFreightPrice($package);

        // Expected: (30.00 + 10.00) * 1.25 = 50.00
        $this->assertEquals(50.00, $result);

        // Test rate breakdown
        $breakdown = $calculator->getRateBreakdown($package);
        
        $this->assertEquals([
            'weight' => 14.7,
            'rounded_weight' => 15.0,
            'rate_weight' => 15.0,
            'base_price' => 30.00,
            'processing_fee' => 10.00,
            'subtotal' => 40.00,
            'exchange_rate' => 1.25,
            'total' => 50.00,
        ], $breakdown);
    }

    /** @test */
    public function air_rate_calculator_handles_missing_rates_gracefully()
    {
        // Create air manifest
        $manifest = Manifest::create([
            'name' => 'Test Air Manifest 2',
            'type' => 'air',
            'exchange_rate' => 1.0,
            'shipment_date' => now(),
            'is_open' => true
        ]);

        // Don't create any rates

        // Create air package
        $package = new Package([
            'manifest_id' => $manifest->id,
            'user_id' => 1,
            'tracking_number' => 'AIR124',
            'weight' => 10.0,
            'description' => 'Test air package without rates'
        ]);
        
        // Set the manifest relationship
        $package->setRelation('manifest', $manifest);

        // Test that appropriate exception is thrown
        $calculator = new AirRateCalculator();
        
        $this->expectException(\App\Exceptions\AirRateNotFoundException::class);
        $this->expectExceptionMessage('No air shipping rate found for 10 lbs');
        
        $calculator->calculateFreightPrice($package);
    }
}