<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Manifest;
use App\Models\User;
use App\Http\Livewire\Manifests\EditManifest;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EditManifestComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user for authentication
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    /** @test */
    public function it_validates_required_vessel_fields_for_sea_manifest()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);

        $component = Livewire::test(EditManifest::class)
            ->set('manifest_id', $manifest->id)
            ->set('type', 'sea')
            ->set('name', 'Test Manifest')
            ->set('reservation_number', 'RES001')
            ->set('exchange_rate', '1.0')
            ->set('shipment_date', '2025-08-01')
            ->set('vessel_name', '')
            ->set('voyage_number', '')
            ->set('departure_port', '')
            ->call('update');

        $component->assertHasErrors(['vessel_name', 'voyage_number', 'departure_port']);
    }

    /** @test */
    public function it_validates_required_flight_fields_for_air_manifest()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);

        $component = Livewire::test(EditManifest::class)
            ->set('manifest_id', $manifest->id)
            ->set('type', 'air')
            ->set('name', 'Test Manifest')
            ->set('reservation_number', 'RES001')
            ->set('exchange_rate', '1.0')
            ->set('shipment_date', '2025-08-01')
            ->set('flight_number', '')
            ->set('flight_destination', '')
            ->call('update');

        $component->assertHasErrors(['flight_number', 'flight_destination']);
    }

    /** @test */
    public function it_updates_sea_manifest_with_vessel_information()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);

        Livewire::test(EditManifest::class)
            ->set('manifest_id', $manifest->id)
            ->set('type', 'sea')
            ->set('name', 'Updated Sea Manifest')
            ->set('reservation_number', 'RES002')
            ->set('exchange_rate', '1.2')
            ->set('shipment_date', '2025-08-10')
            ->set('vessel_name', 'Updated Vessel')
            ->set('voyage_number', 'UV002')
            ->set('departure_port', 'Updated Port A')
            ->set('arrival_port', 'Updated Port B')
            ->set('estimated_arrival_date', '2025-08-20')
            ->call('update');

        $manifest->refresh();

        $this->assertEquals('sea', $manifest->type);
        $this->assertEquals('Updated Sea Manifest', $manifest->name);
        $this->assertEquals('Updated Vessel', $manifest->vessel_name);
        $this->assertEquals('UV002', $manifest->voyage_number);
        $this->assertEquals('Updated Port A', $manifest->departure_port);
        $this->assertEquals('Updated Port B', $manifest->arrival_port);
        $this->assertEquals('2025-08-20', $manifest->estimated_arrival_date);
        $this->assertNull($manifest->flight_number);
        $this->assertNull($manifest->flight_destination);
    }

    /** @test */
    public function it_updates_air_manifest_with_flight_information()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);

        Livewire::test(EditManifest::class)
            ->set('manifest_id', $manifest->id)
            ->set('type', 'air')
            ->set('name', 'Updated Air Manifest')
            ->set('reservation_number', 'RES003')
            ->set('exchange_rate', '1.3')
            ->set('shipment_date', '2025-08-15')
            ->set('flight_number', 'FL456')
            ->set('flight_destination', 'Los Angeles')
            ->call('update');

        $manifest->refresh();

        $this->assertEquals('air', $manifest->type);
        $this->assertEquals('Updated Air Manifest', $manifest->name);
        $this->assertEquals('FL456', $manifest->flight_number);
        $this->assertEquals('Los Angeles', $manifest->flight_destination);
        $this->assertNull($manifest->vessel_name);
        $this->assertNull($manifest->voyage_number);
        $this->assertNull($manifest->departure_port);
        $this->assertNull($manifest->arrival_port);
        $this->assertNull($manifest->estimated_arrival_date);
    }

    /** @test */
    public function it_validates_estimated_arrival_date_is_after_shipment_date()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);

        $component = Livewire::test(EditManifest::class)
            ->set('manifest_id', $manifest->id)
            ->set('type', 'sea')
            ->set('name', 'Test Manifest')
            ->set('reservation_number', 'RES001')
            ->set('exchange_rate', '1.0')
            ->set('shipment_date', '2025-08-15')
            ->set('vessel_name', 'Test Vessel')
            ->set('voyage_number', 'TV001')
            ->set('departure_port', 'Port A')
            ->set('estimated_arrival_date', '2025-08-10') // Before shipment date
            ->call('update');

        $component->assertHasErrors(['estimated_arrival_date']);
    }
}