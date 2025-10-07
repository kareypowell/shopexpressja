<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AirRateCalculator;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Rate;
use App\Exceptions\AirRateNotFoundException;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AirRateCalculatorTest extends TestCase
{
    use DatabaseTransactions;

    protected AirRateCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new AirRateCalculator();
    }

    /** @test */
    public function it_calculates_freight_price_for_air_package()
    {
        // Create air manifest
        $manifest = Manifest::create([
            'name' => 'Test Air Manifest',
            'type' => 'air',
            'exchange_rate' => 1.5,
            'shipment_date' => now(),
            'is_open' => true
        ]);

        // Create air rate
        $rate = Rate::create([
            'type' => 'air',
            'weight' => 10,
            'price' => 25.00,
            'processing_fee' => 5.00
        ]);

        // Create air package with minimal required fields
        $package = new Package([
            'manifest_id' => $manifest->id,
            'user_id' => 1, // Minimal user ID
            'tracking_number' => 'TEST123',
            'weight' => 9.5, // Should round up to 10
            'description' => 'Test package'
        ]);
        
        // Set the manifest relationship manually to avoid database save
        $package->setRelation('manifest', $manifest);

        $result = $this->calculator->calculateFreightPrice($package);

        // Expected: (25.00 + 5.00) * 1.5 = 45.00
        $this->assertEquals(45.00, $result);
    }

    /** @test */
    public function it_rounds_up_weight_for_calculation()
    {
        $manifest = Manifest::create([
            'name' => 'Test Air Manifest 2',
            'type' => 'air',
            'exchange_rate' => 1.0,
            'shipment_date' => now(),
            'is_open' => true
        ]);

        $rate = Rate::create([
            'type' => 'air',
            'weight' => 5,
            'price' => 20.00,
            'processing_fee' => 0.00
        ]);

        $package = Package::create([
            'manifest_id' => $manifest->id,
            'tracking_number' => 'TEST124',
            'weight' => 4.1, // Should round up to 5
            'description' => 'Test package 2'
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        $this->assertEquals(20.00, $result);
    }

    /** @test */
    public function it_uses_fallback_exchange_rate_when_null()
    {
        // Create manifest first, then update exchange_rate to null
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.0
        ]);
        
        // Update to null after creation to bypass factory validation
        $manifest->update(['exchange_rate' => null]);

        $rate = Rate::factory()->create([
            'type' => 'air',
            'weight' => 5,
            'price' => 10.00,
            'processing_fee' => 0.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 5.0
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Should use exchange rate of 1.0
        $this->assertEquals(10.00, $result);
    }

    /** @test */
    public function it_uses_fallback_exchange_rate_when_zero()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 0
        ]);

        $rate = Rate::factory()->create([
            'type' => 'air',
            'weight' => 5,
            'price' => 15.00,
            'processing_fee' => 5.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 5.0
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Should use exchange rate of 1.0: (15.00 + 5.00) * 1.0 = 20.00
        $this->assertEquals(20.00, $result);
    }

    /** @test */
    public function it_finds_closest_higher_weight_rate_when_exact_match_not_found()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.0
        ]);

        // Create rates for weights 5 and 15, but not 10
        Rate::factory()->create([
            'type' => 'air',
            'weight' => 5,
            'price' => 10.00,
            'processing_fee' => 0.00
        ]);

        $higherRate = Rate::factory()->create([
            'type' => 'air',
            'weight' => 15,
            'price' => 30.00,
            'processing_fee' => 0.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 10.0 // Should use the 15lb rate
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        $this->assertEquals(30.00, $result);
    }

    /** @test */
    public function it_throws_exception_when_no_air_rates_found()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.0
        ]);

        // Create only sea rates
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1,
            'max_cubic_feet' => 10,
            'price' => 20.00,
            'processing_fee' => 0.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 5.0
        ]);

        $this->expectException(AirRateNotFoundException::class);
        $this->expectExceptionMessage('No air shipping rate found for 5 lbs');

        $this->calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_throws_exception_for_sea_package()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.0
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 5.0
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must belong to an air manifest');

        $this->calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_throws_exception_for_zero_weight()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.0
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 0
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must have valid weight greater than 0');

        $this->calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_throws_exception_for_negative_weight()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.0
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => -5.0
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must have valid weight greater than 0');

        $this->calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_provides_detailed_rate_breakdown()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.25
        ]);

        $rate = Rate::factory()->create([
            'type' => 'air',
            'weight' => 8,
            'price' => 20.00,
            'processing_fee' => 5.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 7.3 // Should round up to 8
        ]);

        $breakdown = $this->calculator->getRateBreakdown($package);

        $this->assertEquals([
            'weight' => 7.3,
            'rounded_weight' => 8.0,
            'rate_weight' => 8.0,
            'base_price' => 20.00,
            'processing_fee' => 5.00,
            'subtotal' => 25.00,
            'exchange_rate' => 1.25,
            'total' => 31.25,
        ], $breakdown);
    }

    /** @test */
    public function it_handles_backward_compatibility_with_legacy_rates()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.0
        ]);

        // Create a legacy air rate without using the forAirShipment scope
        $rate = Rate::factory()->create([
            'type' => 'air',
            'weight' => 10,
            'price' => 15.00,
            'processing_fee' => 2.50
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'weight' => 10.0
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Expected: (15.00 + 2.50) * 1.0 = 17.50
        $this->assertEquals(17.50, $result);
    }
}