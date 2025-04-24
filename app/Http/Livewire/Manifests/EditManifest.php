<?php

namespace App\Http\Livewire\Manifests;

use Livewire\Component;
use App\Models\Manifest;

class EditManifest extends Component
{
    public string $type;

    public string $name;

    public string $reservation_number;

    public string $flight_number;

    public string $flight_destination;

    public string $exchange_rate;

    public string $shipment_date;

    public int $manifest_id;

    public function mount()
    {
        $this->manifest_id = request()->route('manifest_id');;

        // Load the manifest data
        $manifest = Manifest::find($this->manifest_id);
        if ($manifest) {
            $this->type = $manifest->type;
            $this->name = $manifest->name;
            $this->reservation_number = $manifest->reservation_number;
            $this->flight_number = $manifest->flight_number;
            $this->flight_destination = $manifest->flight_destination;
            $this->exchange_rate = $manifest->exchange_rate;
            $this->shipment_date = $manifest->shipment_date;
        }
    }

    public function update()
    {
        $this->validate([
            'type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'reservation_number' => 'required|string|max:255',
            'flight_number' => 'required|string|max:255',
            'flight_destination' => 'required|string|max:255',
            'exchange_rate' => 'required|numeric',
            'shipment_date' => 'required|date',
        ]);

        // Update the manifest in the database
        $manifest = Manifest::find($this->manifest_id);
        $manifest->update([
            'type' => $this->type,
            'name' => $this->name,
            'reservation_number' => $this->reservation_number,
            'flight_number' => $this->flight_number,
            'flight_destination' => $this->flight_destination,
            'exchange_rate' => $this->exchange_rate,
            'shipment_date' => $this->shipment_date,
        ]);

        // session()->flash('message', 'Manifest updated successfully.');

        if ($manifest) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Manifest updated successfully.',
            ]);
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Manifest update failed.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.manifests.edit-manifest');
    }
}
