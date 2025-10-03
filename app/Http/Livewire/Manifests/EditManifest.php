<?php

namespace App\Http\Livewire\Manifests;

use Livewire\Component;
use App\Models\Manifest;
use App\Rules\ValidVesselInformation;

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

    public function mount($manifest = null)
    {
        // Handle both direct ID and model binding
        if ($manifest instanceof Manifest) {
            $this->manifest_id = $manifest->id;
            $manifest_model = $manifest;
        } else {
            $this->manifest_id = $manifest;
            $manifest_model = $manifest ? Manifest::find($manifest) : null;
        }

        // Initialize properties with empty strings to avoid null issues
        $this->flight_number = '';
        $this->flight_destination = '';
        $this->vessel_name = '';
        $this->voyage_number = '';
        $this->departure_port = '';
        $this->arrival_port = '';
        $this->estimated_arrival_date = '';

        // Load the manifest data
        if ($manifest_model) {
            $this->type = $manifest_model->type;
            $this->name = $manifest_model->name;
            $this->reservation_number = $manifest_model->reservation_number;
            $this->exchange_rate = $manifest_model->exchange_rate;
            $this->shipment_date = $manifest_model->shipment_date;

            // Load type-specific information
            if ($manifest_model->isSeaManifest()) {
                $this->vessel_name = $manifest_model->vessel_name ?? '';
                $this->voyage_number = $manifest_model->voyage_number ?? '';
                $this->departure_port = $manifest_model->departure_port ?? '';
                $this->arrival_port = $manifest_model->arrival_port ?? '';
                $this->estimated_arrival_date = $manifest_model->estimated_arrival_date ?? '';
            } else {
                $this->flight_number = $manifest_model->flight_number ?? '';
                $this->flight_destination = $manifest_model->flight_destination ?? '';
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
                'vessel_name' => ['required', 'string', 'max:255', 'min:2', new ValidVesselInformation($this->type, 'vessel_name')],
                'voyage_number' => ['required', 'string', 'max:255', 'min:1', new ValidVesselInformation($this->type, 'voyage_number')],
                'departure_port' => ['required', 'string', 'max:255', 'min:2', new ValidVesselInformation($this->type, 'departure_port')],
                'arrival_port' => ['nullable', 'string', 'max:255', 'min:2'],
                'estimated_arrival_date' => ['nullable', 'date', 'after:shipment_date'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'flight_number' => ['required', 'string', 'max:255', 'min:1'],
                'flight_destination' => ['required', 'string', 'max:255', 'min:2'],
            ]);
        }

        $this->validate($rules, $this->getValidationMessages());

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

    /**
     * Get custom validation messages
     */
    protected function getValidationMessages(): array
    {
        return [
            'type.required' => 'Please select a manifest type (Air or Sea).',
            'name.required' => 'Manifest name is required.',
            'name.min' => 'Manifest name must be at least 2 characters.',
            'reservation_number.required' => 'Reservation number is required.',
            'exchange_rate.required' => 'Exchange rate is required.',
            'exchange_rate.numeric' => 'Exchange rate must be a valid number.',
            'shipment_date.required' => 'Shipment date is required.',
            'shipment_date.date' => 'Please enter a valid shipment date.',
            
            // Sea manifest specific messages
            'vessel_name.required' => 'Vessel name is required for sea manifests.',
            'vessel_name.min' => 'Vessel name must be at least 2 characters.',
            'vessel_name.max' => 'Vessel name cannot exceed 255 characters.',
            'voyage_number.required' => 'Voyage number is required for sea manifests.',
            'voyage_number.min' => 'Voyage number must be at least 1 character.',
            'voyage_number.max' => 'Voyage number cannot exceed 255 characters.',
            'departure_port.required' => 'Departure port is required for sea manifests.',
            'departure_port.min' => 'Departure port must be at least 2 characters.',
            'departure_port.max' => 'Departure port cannot exceed 255 characters.',
            'arrival_port.min' => 'Arrival port must be at least 2 characters.',
            'arrival_port.max' => 'Arrival port cannot exceed 255 characters.',
            'estimated_arrival_date.date' => 'Please enter a valid estimated arrival date.',
            'estimated_arrival_date.after' => 'Estimated arrival date must be after the shipment date.',
            
            // Air manifest specific messages
            'flight_number.required' => 'Flight number is required for air manifests.',
            'flight_number.min' => 'Flight number must be at least 1 character.',
            'flight_number.max' => 'Flight number cannot exceed 255 characters.',
            'flight_destination.required' => 'Flight destination is required for air manifests.',
            'flight_destination.min' => 'Flight destination must be at least 2 characters.',
            'flight_destination.max' => 'Flight destination cannot exceed 255 characters.',
        ];
    }

    public function render()
    {
        return view('livewire.manifests.edit-manifest');
    }
}
