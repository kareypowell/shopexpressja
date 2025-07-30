<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Rate;
use App\Models\Office;
use App\Models\Shipper;
use App\Services\SeaRateCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Manifests\Packages\ManifestPackage;

class SeaPricingCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Manifest $seaManifest;
    protected Office $office;
    protected Shipper $shipper;
    protected SeaRateCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->seaManifest = Manifest::factory()->sea()->create(['exchange_rate' => 1.5]);
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();
        $this->calculator = new SeaRateCalculator();
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_calculates_freight_price_using_cubic_feet_for_sea_packages()
    {
        // Create sea rates with different ranges
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 0.5,
            'max_cubic_feet' => 2.0,
            'price' => 20.00,
            'processing_fee' => 5.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 2.1,
            'max_cubic_feet' => 5.0,
            'price' => 15.00,
            'processing_fee' => 8.00
        ]);

        // Create package with 1.5 cubic feet (should use first rate)
        $package = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 1.5,
            'length_inches' => 18.0,
            'width_inches' => 12.0,
            'height_inches' => 12.0
        ]);

        $freightPrice = $this->calculator->calculateFreightPrice($package);

        // Expected: (20.00 * 1.5 + 5.00) * 1.5 = 52.5
        $this->assertEquals(52.5, $freightPrice);
    }

    /** @test */
    public function it_uses_correct_rate_range_based_on_cubic_feet()
    {
        // Create multiple rate ranges
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 0.1,
            'max_cubic_feet' => 1.0,
            'price' => 25.00,
            'processing_fee' => 3.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.1,
            'max_cubic_feet' => 3.0,
            'price' => 18.00,
            'processing_fee' => 6.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 3.1,
            'max_cubic_feet' => 10.0,
            'price' => 12.00,
            'processing_fee' => 10.00
        ]);

        // Test package in first range
        $smallPackage = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 0.8
        ]);

        $smallPrice = $this->calculator->calculateFreightPrice($smallPackage);
        // Expected: (25.00 * 0.8 + 3.00) * 1.5 = 34.5
        $this->assertEquals(34.5, $smallPrice);

        // Test package in second range
        $mediumPackage = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 2.5
        ]);

        $mediumPrice = $this->calculator->calculateFreightPrice($mediumPackage);
        // Expected: (18.00 * 2.5 + 6.00) * 1.5 = 76.5
        $this->assertEquals(76.5, $mediumPrice);

        // Test package in third range
        $largePackage = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 5.0
        ]);

        $largePrice = $this->calculator->calculateFreightPrice($largePackage);
        // Expected: (12.00 * 5.0 + 10.00) * 1.5 = 105.0
        $this->assertEquals(105.0, $largePrice);
    }

    /** @test */
    public function it_provides_detailed_pricing_breakdown()
    {
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 4.0,
            'price' => 16.50,
            'processing_fee' => 7.25
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 2.8
        ]);

        $breakdown = $this->calculator->getRateBreakdown($package);

        $this->assertEquals('2.800', $breakdown['cubic_feet']);
        $this->assertEquals(16.50, $breakdown['rate_per_cubic_foot']);
        $this->assertEqualsWithDelta(46.2, $breakdown['freight_cost'], 0.01);
        $this->assertEquals(7.25, $breakdown['processing_fee']);
        $this->assertEqualsWithDelta(53.45, $breakdown['subtotal'], 0.01);
        $this->assertEquals(1.5, $breakdown['exchange_rate']);
        $this->assertEquals(80.175, $breakdown['total']);
        $this->assertEquals([
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 4.0,
        ], $breakdown['rate_range']);
    }

    /** @test */
    public function it_handles_edge_case_cubic_feet_values()
    {
        // Create rate for exact boundary values
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 3.0,
            'price' => 20.00,
            'processing_fee' => 5.00
        ]);

        // Test exact minimum boundary
        $minPackage = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 1.0
        ]);

        $minPrice = $this->calculator->calculateFreightPrice($minPackage);
        // Expected: (20.00 * 1.0 + 5.00) * 1.5 = 37.5
        $this->assertEquals(37.5, $minPrice);

        // Test exact maximum boundary
        $maxPackage = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 3.0
        ]);

        $maxPrice = $this->calculator->calculateFreightPrice($maxPackage);
        // Expected: (20.00 * 3.0 + 5.00) * 1.5 = 97.5
        $this->assertEquals(97.5, $maxPrice);
    }

    /** @test */
    public function it_uses_fallback_rate_when_no_exact_match()
    {
        // Create rates that don't cover all ranges
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 2.0,
            'price' => 15.00,
            'processing_fee' => 4.00
        ]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 4.0,
            'max_cubic_feet' => 8.0,
            'price' => 10.00,
            'processing_fee' => 6.00
        ]);

        // Package with 3.0 cubic feet (falls between ranges)
        $package = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 3.0
        ]);

        $price = $this->calculator->calculateFreightPrice($package);

        // Should use the 4.0-8.0 range as fallback (closest higher range)
        // Expected: (10.00 * 3.0 + 6.00) * 1.5 = 54.0
        $this->assertEquals(54.0, $price);
    }

    /** @test */
    public function it_integrates_pricing_calculation_in_package_creation()
    {
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 0.5,
            'max_cubic_feet' => 3.0,
            'price' => 22.00,
            'processing_fee' => 8.50
        ]);

        Livewire::test(ManifestPackage::class)
            ->set('user_id', $this->user->id)
            ->set('manifest_id', $this->seaManifest->id)
            ->set('shipper_id', $this->shipper->id)
            ->set('office_id', $this->office->id)
            ->set('tracking_number', 'TRK123456789')
            ->set('description', 'Test pricing package')
            ->set('weight', 25.5)
            ->set('estimated_value', 200.00)
            ->set('container_type', 'box')
            ->set('length_inches', 18.0)
            ->set('width_inches', 12.0)
            ->set('height_inches', 12.0) // Results in 1.5 cubic feet
            ->set('items', [
                ['description' => 'Test Item', 'quantity' => 1, 'weight_per_item' => 5.0]
            ])
            ->call('store')
            ->assertHasNoErrors();

        $package = Package::where('tracking_number', 'TRK123456789')->first();
        
        // Verify cubic feet calculation
        $this->assertEquals(1.5, (float)$package->cubic_feet);
        
        // Verify freight price calculation
        $expectedFreightPrice = (22.00 * 1.5 + 8.50) * 1.5; // 58.125
        $this->assertEquals($expectedFreightPrice, (float)$package->freight_price);
    }

    /** @test */
    public function it_handles_different_exchange_rates()
    {
        $manifest1 = Manifest::factory()->sea()->create(['exchange_rate' => 1.0]);
        $manifest2 = Manifest::factory()->sea()->create(['exchange_rate' => 2.0]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 10.00,
            'processing_fee' => 5.00
        ]);

        $package1 = Package::factory()->create([
            'manifest_id' => $manifest1->id,
            'cubic_feet' => 2.0
        ]);

        $package2 = Package::factory()->create([
            'manifest_id' => $manifest2->id,
            'cubic_feet' => 2.0
        ]);

        $price1 = $this->calculator->calculateFreightPrice($package1);
        $price2 = $this->calculator->calculateFreightPrice($package2);

        // Same base calculation, different exchange rates
        // Package 1: (10.00 * 2.0 + 5.00) * 1.0 = 25.0
        // Package 2: (10.00 * 2.0 + 5.00) * 2.0 = 50.0
        $this->assertEquals(25.0, $price1);
        $this->assertEquals(50.0, $price2);
    }

    /** @test */
    public function it_handles_zero_exchange_rate_with_fallback()
    {
        $manifest = Manifest::factory()->sea()->create(['exchange_rate' => 0]);

        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 5.0,
            'price' => 15.00,
            'processing_fee' => 3.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'cubic_feet' => 1.5
        ]);

        $price = $this->calculator->calculateFreightPrice($package);

        // Should fallback to exchange rate of 1
        // Expected: (15.00 * 1.5 + 3.00) * 1 = 25.5
        $this->assertEquals(25.5, $price);
    }

    /** @test */
    public function it_finds_rates_using_scope_methods()
    {
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 1.0,
            'max_cubic_feet' => 3.0,
            'price' => 20.00,
            'processing_fee' => 5.00
        ]);

        Rate::factory()->create([
            'type' => 'air',
            'weight' => 10,
            'price' => 15.00,
            'processing_fee' => 3.00
        ]);

        // Test sea shipment scope
        $seaRate = Rate::forSeaShipment(2.0)->first();
        $this->assertNotNull($seaRate);
        $this->assertEquals('sea', $seaRate->type);
        $this->assertEquals(20.00, $seaRate->price);

        // Test air shipment scope
        $airRate = Rate::forAirShipment(10)->first();
        $this->assertNotNull($airRate);
        $this->assertEquals('air', $airRate->type);
        $this->assertEquals(15.00, $airRate->price);

        // Test no match for sea shipment outside range
        $noSeaRate = Rate::forSeaShipment(5.0)->first();
        $this->assertNull($noSeaRate);
    }

    /** @test */
    public function it_calculates_pricing_for_very_small_packages()
    {
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 0.1,
            'max_cubic_feet' => 1.0,
            'price' => 50.00, // Higher rate for small packages
            'processing_fee' => 2.00
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 0.25
        ]);

        $price = $this->calculator->calculateFreightPrice($package);

        // Expected: (50.00 * 0.25 + 2.00) * 1.5 = 21.75
        $this->assertEquals(21.75, $price);
    }

    /** @test */
    public function it_calculates_pricing_for_very_large_packages()
    {
        Rate::factory()->create([
            'type' => 'sea',
            'min_cubic_feet' => 10.0,
            'max_cubic_feet' => 50.0,
            'price' => 8.00, // Lower rate for large packages
            'processing_fee' => 25.00 // Higher processing fee
        ]);

        $package = Package::factory()->create([
            'manifest_id' => $this->seaManifest->id,
            'cubic_feet' => 25.0
        ]);

        $price = $this->calculator->calculateFreightPrice($package);

        // Expected: (8.00 * 25.0 + 25.00) * 1.5 = 337.5
        $this->assertEquals(337.5, $price);
    }
}