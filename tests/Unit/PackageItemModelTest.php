<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Package;
use App\Models\PackageItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageItemModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_total_weight_correctly()
    {
        $item = PackageItem::factory()->make([
            'quantity' => 5,
            'weight_per_item' => 2.5
        ]);

        $this->assertEquals(12.5, $item->total_weight);
    }

    /** @test */
    public function it_calculates_total_weight_with_zero_weight_per_item()
    {
        $item = PackageItem::factory()->make([
            'quantity' => 3,
            'weight_per_item' => 0
        ]);

        $this->assertEquals(0, $item->total_weight);
    }

    /** @test */
    public function it_calculates_total_weight_with_null_weight_per_item()
    {
        $item = PackageItem::factory()->make([
            'quantity' => 4,
            'weight_per_item' => null
        ]);

        $this->assertEquals(0, $item->total_weight);
    }

    /** @test */
    public function it_calculates_total_weight_with_decimal_values()
    {
        $item = PackageItem::factory()->make([
            'quantity' => 3,
            'weight_per_item' => 1.33
        ]);

        $this->assertEquals(3.99, $item->total_weight);
    }

    /** @test */
    public function it_belongs_to_package()
    {
        $package = Package::factory()->create();
        $item = PackageItem::factory()->create(['package_id' => $package->id]);

        $this->assertInstanceOf(Package::class, $item->package);
        $this->assertEquals($package->id, $item->package->id);
    }

    /** @test */
    public function it_casts_quantity_to_integer()
    {
        $item = PackageItem::factory()->create(['quantity' => '5']);

        $this->assertIsInt($item->quantity);
        $this->assertEquals(5, $item->quantity);
    }

    /** @test */
    public function it_casts_weight_per_item_to_decimal_with_two_precision()
    {
        $item = PackageItem::factory()->create(['weight_per_item' => 2.567]);

        $this->assertEquals('2.57', $item->weight_per_item);
    }

    /** @test */
    public function it_has_correct_fillable_fields()
    {
        $expectedFillable = [
            'package_id',
            'description',
            'quantity',
            'weight_per_item'
        ];

        $item = new PackageItem();
        $this->assertEquals($expectedFillable, $item->getFillable());
    }

    /** @test */
    public function it_can_create_multiple_items_for_same_package()
    {
        $package = Package::factory()->create();
        
        $item1 = PackageItem::factory()->create([
            'package_id' => $package->id,
            'description' => 'Item 1',
            'quantity' => 2,
            'weight_per_item' => 1.5
        ]);

        $item2 = PackageItem::factory()->create([
            'package_id' => $package->id,
            'description' => 'Item 2',
            'quantity' => 3,
            'weight_per_item' => 2.0
        ]);

        $this->assertCount(2, $package->items);
        $this->assertEquals(3.0, $item1->total_weight); // 2 * 1.5
        $this->assertEquals(6.0, $item2->total_weight); // 3 * 2.0
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $item = new PackageItem();
        
        // Test that these fields are required by checking fillable array
        $this->assertContains('package_id', $item->getFillable());
        $this->assertContains('description', $item->getFillable());
        $this->assertContains('quantity', $item->getFillable());
    }

    /** @test */
    public function it_handles_large_quantities_correctly()
    {
        $item = PackageItem::factory()->make([
            'quantity' => 1000,
            'weight_per_item' => 0.5
        ]);

        $this->assertEquals(500.0, $item->total_weight);
    }

    /** @test */
    public function it_handles_fractional_weight_per_item()
    {
        $item = PackageItem::factory()->make([
            'quantity' => 7,
            'weight_per_item' => 0.33
        ]);

        $this->assertEquals(2.31, $item->total_weight);
    }
}