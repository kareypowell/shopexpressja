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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Http\Livewire\Manifests\Manifest as ManifestComponent;

class SeaManifestCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Office $office;
    protected Shipper $shipper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_create_sea_manifest_with_vessel_information()
    {
        Livewire::test(ManifestComponent::class)
            ->set('name', 'Test Sea Manifest')
            ->set('shipment_date', '2024-01-15')
            ->set('reservation_number', 'SEA-001')
            ->set('type', 'sea')
            ->set('vessel_name', 'MV Ocean Carrier')
            ->set('voyage_number', 'V123')
            ->set('departure_port', 'Port of Miami')
            ->set('arrival_port', 'Port of Kingston')
            ->set('estimated_arrival_date', '2024-01-25')
            ->set('exchange_rate', 1.25)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('manifests', [
            'name' => 'Test Sea Manifest',
            'type' => 'sea',
            'vessel_name' => 'MV Ocean Carrier',
            'voyage_number' => 'V123',
            'departure_port' => 'Port of Miami',
            'arrival_port' => 'Port of Kingston',
            'estimated_arrival_date' => '2024-01-25',
            'exchange_rate' => 1.25
        ]);
    }

    /** @test */
    public function it_validates_required_vessel_fields_for_sea_manifest()
    {
        Livewire::test(ManifestComponent::class)
            ->set('name', 'Test Sea Manifest')
            ->set('shipment_date', '2024-01-15')
            ->set('reservation_number', 'SEA-002')
            ->set('type', 'sea')
            ->set('exchange_rate', 1.25)
            ->call('store')
            ->assertHasErrors(['vessel_name', 'voyage_number', 'departure_port']);
    }

    /** @test */
    public function it_does_not_require_vessel_fields_for_air_manifest()
    {
        Livewire::test(ManifestComponent::class)
            ->set('name', 'Test Air Manifest')
            ->set('shipment_date', '2024-01-15')
            ->set('reservation_number', 'AIR-001')
            ->set('type', 'air')
            ->set('flight_number', 'AA123')
            ->set('flight_destination', 'New York')
            ->set('exchange_rate', 1.25)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('manifests', [
            'name' => 'Test Air Manifest',
            'type' => 'air',
            'flight_number' => 'AA123',
            'flight_destination' => 'New York'
        ]);
    }

    /** @test */
    public function it_allows_optional_vessel_fields()
    {
        Livewire::test(ManifestComponent::class)
            ->set('name', 'Test Sea Manifest')
            ->set('shipment_date', '2024-01-15')
            ->set('reservation_number', 'SEA-003')
            ->set('type', 'sea')
            ->set('vessel_name', 'MV Ocean Carrier')
            ->set('voyage_number', 'V123')
            ->set('departure_port', 'Port of Miami')
            ->set('exchange_rate', 1.25)
            // arrival_port and estimated_arrival_date are optional
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('manifests', [
            'name' => 'Test Sea Manifest',
            'type' => 'sea',
            'vessel_name' => 'MV Ocean Carrier',
            'voyage_number' => 'V123',
            'departure_port' => 'Port of Miami',
            'arrival_port' => null,
            'estimated_arrival_date' => null
        ]);
    }

    /** @test */
    public function it_validates_estimated_arrival_date_is_after_shipment_date()
    {
        Livewire::test(ManifestComponent::class)
            ->set('name', 'Test Sea Manifest')
            ->set('shipment_date', '2024-01-15')
            ->set('reservation_number', 'SEA-004')
            ->set('type', 'sea')
            ->set('vessel_name', 'MV Ocean Carrier')
            ->set('voyage_number', 'V123')
            ->set('departure_port', 'Port of Miami')
            ->set('estimated_arrival_date', '2024-01-10') // Before shipment date
            ->set('exchange_rate', 1.25)
            ->call('store')
            ->assertHasErrors(['estimated_arrival_date']);
    }

    /** @test */
    public function sea_manifest_shows_vessel_information_in_transport_info()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => 'MV Test Vessel',
            'voyage_number' => 'V456',
            'departure_port' => 'Test Port',
            'arrival_port' => 'Destination Port',
            'estimated_arrival_date' => '2024-02-01'
        ]);

        $transportInfo = $manifest->transport_info;

        $this->assertEquals([
            'vessel_name' => 'MV Test Vessel',
            'voyage_number' => 'V456',
            'departure_port' => 'Test Port',
            'arrival_port' => 'Destination Port',
            'estimated_arrival_date' => '2024-02-01'
        ], $transportInfo);
    }

    /** @test */
    public function air_manifest_shows_flight_information_in_transport_info()
    {
        $manifest = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'AA789',
            'flight_destination' => 'Miami'
        ]);

        $transportInfo = $manifest->transport_info;

        $this->assertEquals([
            'flight_number' => 'AA789',
            'flight_destination' => 'Miami'
        ], $transportInfo);
    }

    /** @test */
    public function it_correctly_identifies_sea_manifests()
    {
        $seaManifest = Manifest::factory()->create(['type' => 'sea']);
        $airManifest = Manifest::factory()->create(['type' => 'air']);

        $this->assertTrue($seaManifest->isSeaManifest());
        $this->assertFalse($airManifest->isSeaManifest());
    }

    /** @test */
    public function it_can_search_manifests_by_vessel_information()
    {
        $seaManifest = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => 'MV Searchable Vessel',
            'voyage_number' => 'SEARCH123'
        ]);

        $otherManifest = Manifest::factory()->create([
            'type' => 'sea',
            'vessel_name' => 'MV Other Vessel',
            'voyage_number' => 'OTHER456'
        ]);

        $results = Manifest::search('Searchable')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($seaManifest->id, $results->first()->id);

        $results = Manifest::search('SEARCH123')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($seaManifest->id, $results->first()->id);
    }

    /** @test */
    public function it_creates_manifest_with_default_open_status()
    {
        $manifest = Manifest::factory()->create();
        
        $this->assertTrue($manifest->is_open);
    }

    /** @test */
    public function it_stores_exchange_rate_correctly()
    {
        $manifest = Manifest::factory()->create(['exchange_rate' => 1.75]);
        
        $this->assertEquals(1.75, $manifest->exchange_rate);
    }
}