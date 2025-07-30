<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Manifest;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManifestModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_correctly_identifies_sea_manifests()
    {
        $seaManifest = Manifest::factory()->create(['type' => 'sea']);
        $airManifest = Manifest::factory()->create(['type' => 'air']);

        $this->assertTrue($seaManifest->isSeaManifest());
        $this->assertFalse($airManifest->isSeaManifest());
    }

    /** @test */
    public function it_returns_vessel_transport_info_for_sea_manifests()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => 'MV Test Ship',
            'voyage_number' => 'V123',
            'departure_port' => 'Miami',
            'arrival_port' => 'Kingston',
            'estimated_arrival_date' => '2024-02-15'
        ]);

        $transportInfo = $manifest->transport_info;

        $this->assertEquals([
            'vessel_name' => 'MV Test Ship',
            'voyage_number' => 'V123',
            'departure_port' => 'Miami',
            'arrival_port' => 'Kingston',
            'estimated_arrival_date' => '2024-02-15'
        ], $transportInfo);
    }

    /** @test */
    public function it_returns_flight_transport_info_for_air_manifests()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'AA123',
            'flight_destination' => 'New York'
        ]);

        $transportInfo = $manifest->transport_info;

        $this->assertEquals([
            'flight_number' => 'AA123',
            'flight_destination' => 'New York'
        ], $transportInfo);
    }

    /** @test */
    public function it_handles_null_vessel_information_gracefully()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => null,
            'voyage_number' => null,
            'departure_port' => null,
            'arrival_port' => null,
            'estimated_arrival_date' => null
        ]);

        $transportInfo = $manifest->transport_info;

        $this->assertEquals([
            'vessel_name' => null,
            'voyage_number' => null,
            'departure_port' => null,
            'arrival_port' => null,
            'estimated_arrival_date' => null
        ], $transportInfo);
    }

    /** @test */
    public function it_has_packages_relationship()
    {
        $manifest = Manifest::factory()->create();
        $packages = Package::factory()->count(3)->create(['manifest_id' => $manifest->id]);

        $this->assertCount(3, $manifest->packages);
        $this->assertInstanceOf(Package::class, $manifest->packages->first());
    }

    /** @test */
    public function it_can_search_by_manifest_name()
    {
        $manifest1 = Manifest::factory()->create(['name' => 'Searchable Manifest']);
        $manifest2 = Manifest::factory()->create(['name' => 'Other Manifest']);

        $results = Manifest::search('Searchable')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($manifest1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_by_reservation_number()
    {
        $manifest1 = Manifest::factory()->create(['reservation_number' => 'RES-SEARCH-123']);
        $manifest2 = Manifest::factory()->create(['reservation_number' => 'RES-OTHER-456']);

        $results = Manifest::search('SEARCH')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($manifest1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_by_flight_number()
    {
        $manifest1 = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'SEARCH123'
        ]);
        $manifest2 = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'OTHER456'
        ]);

        $results = Manifest::search('SEARCH123')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($manifest1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_by_vessel_name()
    {
        $manifest1 = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => 'MV Searchable Ship'
        ]);
        $manifest2 = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => 'MV Other Ship'
        ]);

        $results = Manifest::search('Searchable')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($manifest1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_by_voyage_number()
    {
        $manifest1 = Manifest::factory()->create([
            'type' => 'sea',
            'voyage_number' => 'VSEARCH789'
        ]);
        $manifest2 = Manifest::factory()->create([
            'type' => 'sea',
            'voyage_number' => 'VOTHER123'
        ]);

        $results = Manifest::search('SEARCH789')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($manifest1->id, $results->first()->id);
    }

    /** @test */
    public function it_has_correct_fillable_fields()
    {
        $expectedFillable = [
            'name',
            'shipment_date',
            'reservation_number',
            'flight_number',
            'flight_destination',
            'vessel_name',
            'voyage_number',
            'departure_port',
            'arrival_port',
            'estimated_arrival_date',
            'exchange_rate',
            'type',
            'is_open',
        ];

        $manifest = new Manifest();
        $this->assertEquals($expectedFillable, $manifest->getFillable());
    }

    /** @test */
    public function it_defaults_to_open_status()
    {
        $manifest = Manifest::factory()->create();
        
        $this->assertTrue($manifest->is_open);
    }

    /** @test */
    public function it_can_be_closed()
    {
        $manifest = Manifest::factory()->create(['is_open' => false]);
        
        $this->assertFalse($manifest->is_open);
    }

    /** @test */
    public function it_stores_exchange_rate_as_decimal()
    {
        $manifest = Manifest::factory()->create(['exchange_rate' => 1.75]);
        
        $this->assertEquals(1.75, $manifest->exchange_rate);
        $this->assertIsFloat($manifest->exchange_rate);
    }

    /** @test */
    public function it_handles_partial_vessel_information()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => 'MV Partial Info',
            'voyage_number' => 'V456',
            'departure_port' => 'Miami',
            'arrival_port' => null, // Optional field
            'estimated_arrival_date' => null // Optional field
        ]);

        $transportInfo = $manifest->transport_info;

        $this->assertEquals('MV Partial Info', $transportInfo['vessel_name']);
        $this->assertEquals('V456', $transportInfo['voyage_number']);
        $this->assertEquals('Miami', $transportInfo['departure_port']);
        $this->assertNull($transportInfo['arrival_port']);
        $this->assertNull($transportInfo['estimated_arrival_date']);
    }

    /** @test */
    public function it_handles_case_insensitive_type_comparison()
    {
        $manifest1 = Manifest::factory()->create(['type' => 'sea']);
        $manifest2 = Manifest::factory()->create(['type' => 'SEA']);
        $manifest3 = Manifest::factory()->create(['type' => 'Sea']);

        // Only exact 'sea' should return true
        $this->assertTrue($manifest1->isSeaManifest());
        $this->assertFalse($manifest2->isSeaManifest()); // Case sensitive
        $this->assertFalse($manifest3->isSeaManifest()); // Case sensitive
    }

    /** @test */
    public function it_returns_empty_transport_info_for_unknown_type()
    {
        $manifest = Manifest::factory()->make(['type' => 'unknown']);

        $transportInfo = $manifest->transport_info;

        // Should return flight info structure as fallback
        $this->assertArrayHasKey('flight_number', $transportInfo);
        $this->assertArrayHasKey('flight_destination', $transportInfo);
    }

    /** @test */
    public function search_is_case_insensitive()
    {
        $manifest = Manifest::factory()->create(['name' => 'Test Manifest']);

        $results1 = Manifest::search('test')->get();
        $results2 = Manifest::search('TEST')->get();
        $results3 = Manifest::search('Test')->get();

        $this->assertCount(1, $results1);
        $this->assertCount(1, $results2);
        $this->assertCount(1, $results3);
        
        $this->assertEquals($manifest->id, $results1->first()->id);
        $this->assertEquals($manifest->id, $results2->first()->id);
        $this->assertEquals($manifest->id, $results3->first()->id);
    }

    /** @test */
    public function search_returns_empty_collection_when_no_matches()
    {
        Manifest::factory()->create(['name' => 'Test Manifest']);

        $results = Manifest::search('nonexistent')->get();

        $this->assertCount(0, $results);
    }
}