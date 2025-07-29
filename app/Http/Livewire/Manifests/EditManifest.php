<?php

namespace App\Http\Livewire\Manifests;

use Livewire\Component;
use App\Models\Manifest;

class EditManifest extends Component
{
    public string $type;

    public string $name;

    public string $reservation_number;

    // Flight-related properties
    public string $flight_number;

    public string $flight_destination;

    // Vessel-related properties
    public string $vessel_name;

    public string $voyage_number;

    public string $departure_port;

    public string $arrival_port;

    public string $estimated_arrival_date;

    public string $exchange_rate;

    public string $shipment_date;

    public ?int $manifest_id;

    public function mount($manifest_id = null)
    {
        // Get manifest_id from route parameter or passed parameter (for testing)
        $this->manifest_id = $manifest_id ?? request()->route('manifest_id');

        // Initialize properties with empty strings to avoid null issues
        $this->flight_number = '';
        $this->flight_destination = '';
        $this->vessel_name = '';
        $this->voyage_number = '';
        $this->departure_port = '';
        $this->arrival_port = '';
        $this->estimated_arrival_date = '';

        // Load the manifest data
        if ($this->manifest_id) {
            $manifest = Manifest::find($this->manifest_id);
            if ($manifest) {
                $this->type = $manifest->type;
                $this->name = $manifest->name;
                $this->reservation_number = $manifest->reservation_number;
                $this->exchange_rate = $manifest->exchange_rate;
                $this->shipment_date = $manifest->shipment_date;

                // Load type-specific information
                if ($manifest->isSeaManifest()) {
                    $this->vessel_name = $manifest->vessel_name ?? '';
                    $this->voyage_number = $manifest->voyage_number ?? '';
                    $this->departure_port = $manifest->departure_port ?? '';
                    $this->arrival_port = $manifest->arrival_port ?? '';
                    $this->estimated_arrival_date = $manifest->estimated_arrival_date ?? '';
                } else {
                    $this->flight_number = $manifest->flight_number ?? '';
                    $this->flight_destination = $manifest->flight_destination ?? '';
                }
            }
        }
    }

    public function update()
    {
        // Base validation rules
        $rules = [
            'type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'reservation_number' => 'required|string|max:255',
            'exchange_rate' => 'required|numeric',
            'shipment_date' => 'required|date',
        ];

        // Add conditional validation based on manifest type
        if ($this->type === 'sea') {
            $rules = array_merge($rules, [
                'vessel_name' => 'required|string|max:255',
                'voyage_number' => 'required|string|max:255',
                'departure_port' => 'required|string|max:255',
                'arrival_port' => 'nullable|string|max:255',
                'estimated_arrival_date' => 'nullable|date|after:shipment_date',
            ]);
        } else {
            $rules = array_merge($rules, [
                'flight_number' => 'required|string|max:255',
                'flight_destination' => 'required|string|max:255',
            ]);
        }

        $this->validate($rules);

        // Update the manifest in the database
        $manifest = Manifest::find($this->manifest_id);
        
        // Base update data
        $updateData = [
            'type' => $this->type,
            'name' => $this->name,
            'reservation_number' => $this->reservation_number,
            'exchange_rate' => $this->exchange_rate,
            'shipment_date' => $this->shipment_date,
        ];

        // Add type-specific data
        if ($this->type === 'sea') {
            $updateData = array_merge($updateData, [
                'vessel_name' => $this->vessel_name,
                'voyage_number' => $this->voyage_number,
                'departure_port' => $this->departure_port,
                'arrival_port' => $this->arrival_port ?: null,
                'estimated_arrival_date' => $this->estimated_arrival_date ?: null,
                // Clear flight fields for sea manifests
                'flight_number' => null,
                'flight_destination' => null,
            ]);
        } else {
            $updateData = array_merge($updateData, [
                'flight_number' => $this->flight_number,
                'flight_destination' => $this->flight_destination,
                // Clear vessel fields for air manifests
                'vessel_name' => null,
                'voyage_number' => null,
                'departure_port' => null,
                'arrival_port' => null,
                'estimated_arrival_date' => null,
            ]);
        }

        $manifest->update($updateData);

        if ($manifest) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Manifest updated successfully.',
            ]);
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Manifest update failed.',
            ]);
        }

        return redirect('/admin/manifests');
    }

    public function render()
    {
        return view('livewire.manifests.edit-manifest');
    }
}
