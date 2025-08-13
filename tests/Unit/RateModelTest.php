<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Rate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_finds_sea_rates_within_cubic_feet_range()
    {
        // Create sea rates with different ranges
        $rate1 = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 3.0,
            'price' => 20.00
        ]);

        $rate2 = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 3.1,
            'max_cubic_feet' => 8.0,
            'price' => 15.00
        ]);

        $rate3 = Rate::factory()->create([
            'type' => 'air',
            'weight' => 5,
            'price' => 25.00
        ]);

        // Test cubic feet within first range
        $result = Rate::forSeaShipment(2.5)->first();
        $this->assertNotNull($result);
        $this->assertEquals($rate1->id, $result->id);

        // Test cubic feet within second range
        $result = Rate::forSeaShipment(5.0)->first();
        $this->assertNotNull($result);
        $this->assertEquals($rate2->id, $result->id);

        // Test cubic feet outside all ranges
        $result = Rate::forSeaShipment(10.0)->first();
        $this->assertNull($result);
    }

    /** @test */
    public function it_finds_sea_rates_at_exact_boundaries()
    {
        $rate = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 2.0,
            'max_cubic_feet' => 5.0,
            'price' => 18.00
        ]);

        // Test exact minimum boundary
        $result = Rate::forSeaShipment(2.0)->first();
        $this->assertNotNull($result);
        $this->assertEquals($rate->id, $result->id);

        // Test exact maximum boundary
        $result = Rate::forSeaShipment(5.0)->first();
        $this->assertNotNull($result);
        $this->assertEquals($rate->id, $result->id);

        // Test just outside boundaries
        $result = Rate::forSeaShipment(1.9)->first();
        $this->assertNull($result);

        $result = Rate::forSeaShipment(5.1)->first();
        $this->assertNull($result);
    }

    /** @test */
    public function it_finds_air_rates_by_exact_weight_match()
    {
        $rate1 = Rate::factory()->create([
            'type' => 'air',
            'weight' => 10,
            'price' => 15.00
        ]);

        $rate2 = Rate::factory()->create([
            'type' => 'air',
            'weight' => 25,
            'price' => 12.00
        ]);

        $rate3 = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 20.00
        ]);

        // Test exact weight match
        $result = Rate::forAirShipment(10)->first();
        $this->assertNotNull($result);
        $this->assertEquals($rate1->id, $result->id);

        $result = Rate::forAirShipment(25)->first();
        $this->assertNotNull($result);
        $this->assertEquals($rate2->id, $result->id);

        // Test no match
        $result = Rate::forAirShipment(15)->first();
        $this->assertNull($result);
    }

    /** @test */
    public function it_only_returns_sea_rates_for_sea_shipment_scope()
    {
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 20.00
        ]);

        Rate::factory()->create([
            'type' => 'air',
            'weight' => 3, // This could match if type wasn't filtered
            'price' => 25.00
        ]);

        $results = Rate::forSeaShipment(3.0)->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('sea', $results->first()->type);
    }

    /** @test */
    public function it_only_returns_air_rates_for_air_shipment_scope()
    {
        Rate::factory()->create([
            'type' => 'air',
            'weight' => 10,
            'price' => 15.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 5.0,
            'max_cubic_feet' => 15.0, // This could match if type wasn't filtered
            'price' => 20.00
        ]);

        $results = Rate::forAirShipment(10)->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('air', $results->first()->type);
    }

    /** @test */
    public function it_handles_overlapping_sea_rate_ranges()
    {
        $rate1 = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 20.00
        ]);

        $rate2 = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 3.0,
            'max_cubic_feet' => 8.0,
            'price' => 15.00
        ]);

        // Cubic feet 4.0 should match both rates
        $results = Rate::forSeaShipment(4.0)->get();
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $rate1->id));
        $this->assertTrue($results->contains('id', $rate2->id));
    }

    /** @test */
    public function it_can_search_rates_by_weight()
    {
        $rate1 = Rate::factory()->create([
            'weight' => 10, 
            'type' => 'air',
            'price' => 20.00, // Ensure price doesn't contain '10'
            'min_cubic_feet' => null,
            'max_cubic_feet' => null
        ]);
        
        $rate2 = Rate::factory()->create([
            'weight' => 25, 
            'type' => 'air',
            'price' => 30.00, // Ensure price doesn't contain '10'
            'min_cubic_feet' => null,
            'max_cubic_feet' => null
        ]);

        $results = Rate::search('10')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals($rate1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_rates_by_price()
    {
        $rate1 = Rate::factory()->create([
            'price' => 15.50,
            'weight' => 20, // Ensure weight doesn't contain '15'
            'type' => 'air',
            'min_cubic_feet' => null,
            'max_cubic_feet' => null
        ]);
        
        $rate2 = Rate::factory()->create([
            'price' => 25.75,
            'weight' => 30, // Ensure weight doesn't contain '15'
            'type' => 'air',
            'min_cubic_feet' => null,
            'max_cubic_feet' => null
        ]);

        $results = Rate::search('15')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals($rate1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_rates_by_type()
    {
        $seaRate = Rate::factory()->create(['type' => 'sea']);
        $airRate = Rate::factory()->create(['type' => 'air']);

        $seaResults = Rate::search('sea')->get();
        $airResults = Rate::search('air')->get();
        
        $this->assertCount(1, $seaResults);
        $this->assertEquals($seaRate->id, $seaResults->first()->id);
        
        $this->assertCount(1, $airResults);
        $this->assertEquals($airRate->id, $airResults->first()->id);
    }

    /** @test */
    public function it_has_correct_fillable_fields()
    {
        $expectedFillable = [
            'weight',
            'min_cubic_feet',
            'max_cubic_feet',
            'price',
            'processing_fee',
            'type'
        ];

        $rate = new Rate();
        $this->assertEquals($expectedFillable, $rate->getFillable());
    }

    /** @test */
    public function it_handles_decimal_cubic_feet_ranges()
    {
        $rate = Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 0.5,
            'max_cubic_feet' => 2.75,
            'price' => 30.00
        ]);

        // Test decimal values within range
        $result = Rate::forSeaShipment(1.25)->first();
        $this->assertNotNull($result);
        $this->assertEquals($rate->id, $result->id);

        $result = Rate::forSeaShipment(2.75)->first();
        $this->assertNotNull($result);
        $this->assertEquals($rate->id, $result->id);

        // Test decimal values outside range
        $result = Rate::forSeaShipment(0.4)->first();
        $this->assertNull($result);

        $result = Rate::forSeaShipment(2.8)->first();
        $this->assertNull($result);
    }

    /** @test */
    public function it_handles_null_weight_for_sea_rates()
    {
        $seaRate = Rate::factory()->create([
            'type' => 'sea',
            'weight' => null,
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 20.00
        ]);

        $this->assertNull($seaRate->weight);
        $this->assertNotNull($seaRate->min_cubic_feet);
        $this->assertNotNull($seaRate->max_cubic_feet);
    }

    /** @test */
    public function it_handles_null_cubic_feet_for_air_rates()
    {
        $airRate = Rate::factory()->create([
            'type' => 'air',
            'weight' => 15,
            'min_cubic_feet' => null,
            'max_cubic_feet' => null,
            'price' => 18.00
        ]);

        $this->assertNotNull($airRate->weight);
        $this->assertNull($airRate->min_cubic_feet);
        $this->assertNull($airRate->max_cubic_feet);
    }

    /** @test */
    public function search_is_case_insensitive()
    {
        $rate = Rate::factory()->create(['type' => 'sea']);

        $results1 = Rate::search('sea')->get();
        $results2 = Rate::search('SEA')->get();
        $results3 = Rate::search('Sea')->get();

        $this->assertCount(1, $results1);
        $this->assertCount(1, $results2);
        $this->assertCount(1, $results3);
        
        $this->assertEquals($rate->id, $results1->first()->id);
        $this->assertEquals($rate->id, $results2->first()->id);
        $this->assertEquals($rate->id, $results3->first()->id);
    }

    /** @test */
    public function it_returns_multiple_matching_rates_for_sea_shipment()
    {
        // Create multiple rates that could match the same cubic feet
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 10.0,
            'price' => 15.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 2.0,
            'max_cubic_feet' => 6.0,
            'price' => 20.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 4.0,
            'max_cubic_feet' => 8.0,
            'price' => 18.00
        ]);

        $results = Rate::forSeaShipment(5.0)->get();
        
        // All three rates should match cubic feet of 5.0
        $this->assertCount(3, $results);
    }

    /** @test */
    public function search_supports_partial_matching()
    {
        $rate1 = Rate::factory()->create([
            'weight' => 105,
            'type' => 'air',
            'price' => 20.00
        ]);
        
        $rate2 = Rate::factory()->create([
            'weight' => 50,
            'type' => 'air', 
            'price' => 10.75 // Contains '10'
        ]);
        
        $rate3 = Rate::factory()->create([
            'weight' => 75,
            'type' => 'air',
            'price' => 30.00
        ]);

        // Search for '10' should find both rate1 (weight 105 contains '10') and rate2 (price 10.75 contains '10')
        $results = Rate::search('10')->get();
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $rate1->id));
        $this->assertTrue($results->contains('id', $rate2->id));
        $this->assertFalse($results->contains('id', $rate3->id));
    }
}