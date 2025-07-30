<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\PackageItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_cubic_feet_correctly()
    {
        $package = Package::factory()->make([
            'length_inches' => 12,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);

        $cubicFeet = $package->calculateCubicFeet();

        // 12 * 12 * 12 = 1728 cubic inches
        // 1728 / 1728 = 1 cubic foot
        $this->assertEquals(1.0, $cubicFeet);
    }

    /** @test */
    public function it_calculates_cubic_feet_with_decimal_precision()
    {
        $package = Package::factory()->make([
            'length_inches' => 10.5,
            'width_inches' => 8.25,
            'height_inches' => 6.75,
            'cubic_feet' => null
        ]);

        $cubicFeet = $package->calculateCubicFeet();

        // 10.5 * 8.25 * 6.75 = 584.4375 cubic inches
        // 584.4375 / 1728 = 0.338 cubic feet (rounded to 3 decimal places)
        $this->assertEquals(0.338, $cubicFeet);
    }

    /** @test */
    public function it_returns_zero_cubic_feet_when_dimensions_are_missing()
    {
        $package = Package::factory()->make([
            'length_inches' => null,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);

        $this->assertEquals(0, $package->calculateCubicFeet());

        $package = Package::factory()->make([
            'length_inches' => 12,
            'width_inches' => null,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);

        $this->assertEquals(0, $package->calculateCubicFeet());

        $package = Package::factory()->make([
            'length_inches' => 12,
            'width_inches' => 12,
            'height_inches' => null,
            'cubic_feet' => null
        ]);

        $this->assertEquals(0, $package->calculateCubicFeet());
    }

    /** @test */
    public function it_returns_zero_cubic_feet_when_any_dimension_is_zero()
    {
        $package = Package::factory()->make([
            'length_inches' => 0,
            'width_inches' => 12,
            'height_inches' => 12,
            'cubic_feet' => null
        ]);

        $this->assertEquals(0, $package->calculateCubicFeet());
    }

    /** @test */
    public function it_identifies_sea_packages_correctly()
    {
        $seaManifest = Manifest::factory()->sea()->create();
        $airManifest = Manifest::factory()->air()->create();

        $seaPackage = Package::factory()->create(['manifest_id' => $seaManifest->id]);
        $airPackage = Package::factory()->create(['manifest_id' => $airManifest->id]);

        $this->assertTrue($seaPackage->isSeaPackage());
        $this->assertFalse($airPackage->isSeaPackage());
    }

    /** @test */
    public function it_returns_false_for_sea_package_when_manifest_is_null()
    {
        $package = Package::factory()->make(['manifest_id' => null]);
        
        $this->assertFalse($package->isSeaPackage());
    }

    /** @test */
    public function it_has_items_relationship()
    {
        $package = Package::factory()->create();
        $items = PackageItem::factory()->count(3)->create(['package_id' => $package->id]);

        $this->assertCount(3, $package->items);
        $this->assertInstanceOf(PackageItem::class, $package->items->first());
    }

    /** @test */
    public function it_casts_cubic_feet_to_decimal_with_three_precision()
    {
        $package = Package::factory()->create([
            'cubic_feet' => 1.23456789
        ]);

        $this->assertEquals('1.235', $package->cubic_feet);
    }

    /** @test */
    public function it_casts_dimensions_to_decimal_with_two_precision()
    {
        $package = Package::factory()->create([
            'length_inches' => 12.345,
            'width_inches' => 8.567,
            'height_inches' => 6.789
        ]);

        $this->assertEquals('12.35', $package->length_inches);
        $this->assertEquals('8.57', $package->width_inches);
        $this->assertEquals('6.79', $package->height_inches);
    }

    /** @test */
    public function it_formats_weight_correctly()
    {
        $package = Package::factory()->create(['weight' => 12.345]);

        $this->assertEquals('12.35', $package->formatted_weight);
    }

    /** @test */
    public function it_belongs_to_manifest()
    {
        $manifest = Manifest::factory()->create();
        $package = Package::factory()->create(['manifest_id' => $manifest->id]);

        $this->assertInstanceOf(Manifest::class, $package->manifest);
        $this->assertEquals($manifest->id, $package->manifest->id);
    }

    /** @test */
    public function it_has_correct_fillable_fields()
    {
        $expectedFillable = [
            'user_id',
            'manifest_id',
            'shipper_id',
            'office_id',
            'warehouse_receipt_no',
            'tracking_number',
            'description',
            'weight',
            'status',
            'estimated_value',
            'freight_price',
            'customs_duty',
            'storage_fee',
            'delivery_fee',
            'container_type',
            'length_inches',
            'width_inches',
            'height_inches',
            'cubic_feet'
        ];

        $package = new Package();
        $this->assertEquals($expectedFillable, $package->getFillable());
    }
}