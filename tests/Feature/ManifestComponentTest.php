<?php

namespace Tests\Feature;

use App\Http\Livewire\Manifests\Manifest;
use App\Models\Manifest as ManifestModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManifestComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_sea_manifest_with_vessel_information()
    {
        Livewire::test(Manifest::class)
            ->set('type', 'sea')
            ->set('name', 'Test Sea Manifest')
            ->set('reservation_number', 'RSV123')
            ->set('vessel_name', 'MV Ocean Carrier')
            ->set('voyage_number', 'VOY001')
            ->set('departure_port', 'Miami')
            ->set('arrival_port', 'Kingston')
            ->set('estimated_arrival_date', '2025-08-01')
            ->set('exchange_rate', '150.00')
            ->set('shipment_date', '2025-07-30')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('manifests', [
            'type' => 'sea',
            'name' => 'Test Sea Manifest',
            'vessel_name' => 'MV Ocean Carrier',
            'voyage_number' => 'VOY001',
            'departure_port' => 'Miami',
            'arrival_port' => 'Kingston',
            'estimated_arrival_date' => '2025-08-01',
        ]);
    }

    /** @test */
    public function it_can_create_air_manifest_with_flight_information()
    {
        Livewire::test(Manifest::class)
            ->set('type', 'air')
            ->set('name', 'Test Air Manifest')
            ->set('reservation_number', 'RSV456')
            ->set('flight_number', 'AA123')
            ->set('flight_destination', 'MIA-KGN')
            ->set('exchange_rate', '150.00')
            ->set('shipment_date', '2025-07-30')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('manifests', [
            'type' => 'air',
            'name' => 'Test Air Manifest',
            'flight_number' => 'AA123',
            'flight_destination' => 'MIA-KGN',
        ]);
    }

    /** @test */
    public function it_validates_required_vessel_fields_for_sea_manifest()
    {
        Livewire::test(Manifest::class)
            ->set('type', 'sea')
            ->set('name', 'Test Sea Manifest')
            ->set('reservation_number', 'RSV123')
            ->set('exchange_rate', '150.00')
            ->set('shipment_date', '2025-07-30')
            ->call('store')
            ->assertHasErrors([
                'vessel_name' => 'required',
                'voyage_number' => 'required',
                'departure_port' => 'required',
            ]);
    }

    /** @test */
    public function it_validates_required_flight_fields_for_air_manifest()
    {
        Livewire::test(Manifest::class)
            ->set('type', 'air')
            ->set('name', 'Test Air Manifest')
            ->set('reservation_number', 'RSV456')
            ->set('exchange_rate', '150.00')
            ->set('shipment_date', '2025-07-30')
            ->call('store')
            ->assertHasErrors([
                'flight_number' => 'required',
                'flight_destination' => 'required',
            ]);
    }

    /** @test */
    public function it_validates_estimated_arrival_date_is_after_shipment_date()
    {
        Livewire::test(Manifest::class)
            ->set('type', 'sea')
            ->set('name', 'Test Sea Manifest')
            ->set('reservation_number', 'RSV123')
            ->set('vessel_name', 'MV Ocean Carrier')
            ->set('voyage_number', 'VOY001')
            ->set('departure_port', 'Miami')
            ->set('estimated_arrival_date', '2025-07-29') // Before shipment date
            ->set('exchange_rate', '150.00')
            ->set('shipment_date', '2025-07-30')
            ->call('store')
            ->assertHasErrors(['estimated_arrival_date']);
    }

    /** @test */
    public function it_resets_all_fields_including_vessel_information()
    {
        $component = Livewire::test(Manifest::class)
            ->set('type', 'sea')
            ->set('name', 'Test')
            ->set('vessel_name', 'Test Vessel')
            ->set('voyage_number', 'VOY001')
            ->call('create'); // This calls resetInputFields

        $this->assertEquals('', $component->get('type'));
        $this->assertEquals('', $component->get('name'));
        $this->assertEquals('', $component->get('vessel_name'));
        $this->assertEquals('', $component->get('voyage_number'));
        $this->assertEquals('', $component->get('departure_port'));
        $this->assertEquals('', $component->get('arrival_port'));
        $this->assertEquals('', $component->get('estimated_arrival_date'));
    }

    /** @test */
    public function it_clears_flight_fields_when_switching_to_sea_type()
    {
        $component = Livewire::test(Manifest::class)
            ->set('type', 'air')
            ->set('flight_number', 'AA123')
            ->set('flight_destination', 'MIA-KGN')
            ->set('type', 'sea'); // Switch to sea type

        $this->assertEquals('', $component->get('flight_number'));
        $this->assertEquals('', $component->get('flight_destination'));
    }

    /** @test */
    public function it_clears_vessel_fields_when_switching_to_air_type()
    {
        $component = Livewire::test(Manifest::class)
            ->set('type', 'sea')
            ->set('vessel_name', 'MV Ocean Carrier')
            ->set('voyage_number', 'VOY001')
            ->set('departure_port', 'Miami')
            ->set('arrival_port', 'Kingston')
            ->set('estimated_arrival_date', '2025-08-01')
            ->set('type', 'air'); // Switch to air type

        $this->assertEquals('', $component->get('vessel_name'));
        $this->assertEquals('', $component->get('voyage_number'));
        $this->assertEquals('', $component->get('departure_port'));
        $this->assertEquals('', $component->get('arrival_port'));
        $this->assertEquals('', $component->get('estimated_arrival_date'));
    }
}