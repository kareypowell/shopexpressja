<?php

namespace Tests\Unit;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\Rate;
use App\Services\SeaRateCalculator;
use App\Exceptions\SeaRateNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SeaRateCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected SeaRateCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SeaRateCalculator();
    }

    /** @test */
    public function it_calculates_freight_price_for_sea_package()
    {
        // Create a sea manifest
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.5
        ]);

        // Create a sea rate
        $rate = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 10.00,
            'processing_fee' => 5.00
        ]);

        // Create a sea package
        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 3.0
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Expected: ((10.00 + 5.00) * 3.0) * 1.5 = 67.5
        $this->assertEquals(67.5, $result);
    }

    /** @test */
    public function it_throws_exception_for_non_sea_package()
    {
        // Create an air manifest
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.0
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 3.0
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must belong to a sea manifest');

        $this->calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_throws_exception_for_zero_cubic_feet()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.0
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 0
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must have valid cubic feet greater than 0');

        $this->calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_throws_exception_for_negative_cubic_feet()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.0
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => -1.5
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must have valid cubic feet greater than 0');

        $this->calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_uses_fallback_rate_when_exact_range_not_found()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.0
        ]);

        // Create rates that don't exactly match our cubic feet
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 3.0,
            'price' => 8.00,
            'processing_fee' => 3.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 5.0,
            'max_cubic_feet' => 10.0,
            'price' => 12.00,
            'processing_fee' => 7.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 4.0 // Falls between ranges
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Should use the 5.0-10.0 range (closest higher range)
        // Expected: ((12.00 + 7.00) * 4.0) * 1.0 = 76.0
        $this->assertEquals(76.0, $result);
    }

    /** @test */
    public function it_uses_highest_rate_as_final_fallback()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.0
        ]);

        // Create rates all below our cubic feet
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 3.0,
            'price' => 8.00,
            'processing_fee' => 3.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 4.0,
            'max_cubic_feet' => 6.0,
            'price' => 15.00,
            'processing_fee' => 10.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 10.0 // Higher than all ranges
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Should use the highest range (4.0-6.0)
        // Expected: ((15.00 + 10.00) * 10.0 ) * 1.0 = 250.0
        $this->assertEquals(250.0, $result);
    }

    /** @test */
    public function it_throws_exception_when_no_sea_rates_exist()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.0
        ]);

        // Create only air rates
        Rate::factory()->create([
            'type' => 'air',
            'weight' => 5,
            'price' => 10.00,
            'processing_fee' => 5.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 3.0
        ]);

        $this->expectException(SeaRateNotFoundException::class);
        $this->expectExceptionMessage('No sea shipping rate found for 3 cubic feet');

        $this->calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_handles_default_exchange_rate_when_zero()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 0
        ]);

        $rate = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 10.00,
            'processing_fee' => 5.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 2.0
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Expected: ((10.00 + 5.00) * 2.0 ) * 1 = 30.0 (fallback to 1 when exchange rate is 0)
        $this->assertEquals(30.0, $result);
    }

    /** @test */
    public function it_provides_detailed_rate_breakdown()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.25
        ]);

        $rate = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 2.0,
            'max_cubic_feet' => 8.0,
            'price' => 12.50,
            'processing_fee' => 7.50
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 4.0
        ]);

        $breakdown = $this->calculator->getRateBreakdown($package);

        $this->assertEquals([
            'cubic_feet' => 4.0,
            'rate_per_cubic_foot' => 12.50,
            'freight_cost' => 50.0, // 12.50 * 4.0
            'processing_fee' => 7.50,
            'subtotal' => 57.5, // 50.0 + 7.50
            'exchange_rate' => 1.25,
            'total' => 71.875, // 57.5 * 1.25
            'rate_range' => [
                'min_cubic_feet' => 2.0,
                'max_cubic_feet' => 8.0,
            ]
        ], $breakdown);
    }

    /** @test */
    public function it_throws_exception_for_rate_breakdown_with_non_sea_package()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'exchange_rate' => 1.0
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 3.0
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must belong to a sea manifest');

        $this->calculator->getRateBreakdown($package);
    }

    /** @test */
    public function it_calculates_with_decimal_cubic_feet()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.0
        ]);

        $rate = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 0.5,
            'max_cubic_feet' => 2.5,
            'price' => 20.00,
            'processing_fee' => 2.50
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 1.75
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Expected: ((20.00 + 2.50) * 1.75) * 1.0 = 39.375
        $this->assertEquals(39.375, $result);
    }

    /** @test */
    public function it_finds_exact_range_match_when_available()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'exchange_rate' => 1.0
        ]);

        // Create multiple overlapping ranges
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 10.00,
            'processing_fee' => 5.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 3.0,
            'max_cubic_feet' => 8.0,
            'price' => 8.00,
            'processing_fee' => 4.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 4.0 // Should match both ranges, but get first one found
        ]);

        $result = $this->calculator->calculateFreightPrice($package);

        // Should use one of the matching rates
        $this->assertTrue($result == 60.0 || $result == 48.0); // Either rate could be selected
    }
}