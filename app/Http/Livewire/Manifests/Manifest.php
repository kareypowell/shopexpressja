<?php

namespace App\Http\Livewire\Manifests;

use Livewire\Component;
use App\Models\Manifest as ManifestModel;
use App\Rules\ValidVesselInformation;

class Manifest extends Component
{
    public bool $isOpen = false;
    public string $mode = 'index'; // 'index' or 'create'

    public string $type = '';

    public string $name = '';

    public string $reservation_number = '';

    public string $flight_number = '';

    public string $flight_destination = '';

    // Vessel information properties for sea manifests
    public string $vessel_name = '';

    public string $voyage_number = '';

    public string $departure_port = '';

    public string $arrival_port = '';

    public string $estimated_arrival_date = '';

    public string $exchange_rate = '';

    public string $shipment_date = '';


    public function mount()
    {
        // Determine mode based on current route
        if (request()->routeIs('admin.manifests.create')) {
            $this->mode = 'create';
            $this->openModal();
        } else {
            $this->mode = 'index';
        }
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function openModal()
    {
        $this->isOpen = true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function closeModal()
    {
        $this->isOpen = false;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    private function resetInputFields()
    {
        $this->type = '';
        $this->name = '';
        $this->reservation_number = '';
        $this->flight_number = '';
        $this->flight_destination = '';
        $this->vessel_name = '';
        $this->voyage_number = '';
        $this->departure_port = '';
        $this->arrival_port = '';
        $this->estimated_arrival_date = '';
        $this->exchange_rate = '';
        $this->shipment_date = '';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function store()
    {
        // Base validation rules
        $rules = [
            'type' => ['required'],
            'name' => ['required'],
            'reservation_number' => ['required'],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'shipment_date' => ['required', 'date'],
        ];

        // Conditional validation based on manifest type
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

        // Prepare data for creation
        $manifestData = [
            'name' => $this->name,
            'shipment_date' => $this->shipment_date,
            'reservation_number' => $this->reservation_number,
            'exchange_rate' => $this->exchange_rate,
            'type' => $this->type,
            'is_open' => true
        ];

        // Add type-specific fields
        if ($this->type === 'sea') {
            $manifestData = array_merge($manifestData, [
                'vessel_name' => $this->vessel_name,
                'voyage_number' => $this->voyage_number,
                'departure_port' => $this->departure_port,
                'arrival_port' => $this->arrival_port ?: null,
                'estimated_arrival_date' => $this->estimated_arrival_date ?: null,
            ]);
        } else {
            $manifestData = array_merge($manifestData, [
                'flight_number' => $this->flight_number,
                'flight_destination' => $this->flight_destination,
            ]);
        }

        $manifest = ManifestModel::create($manifestData);

        if ($manifest) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Manifest Created Successfully.',
            ]);
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Manifest Creation Failed.',
            ]);
        }

        $this->closeModal();
        $this->resetInputFields();

        // Redirect to manifests index after creation
        return redirect()->route('admin.manifests.index');
    }

    public function updatedType()
    {
        // Clear fields when type changes to prevent validation issues
        if ($this->type === 'sea') {
            $this->flight_number = '';
            $this->flight_destination = '';
        } else {
            $this->vessel_name = '';
            $this->voyage_number = '';
            $this->departure_port = '';
            $this->arrival_port = '';
            $this->estimated_arrival_date = '';
        }
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
            'exchange_rate.min' => 'Exchange rate must be at least 1.',
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
        return view('livewire.manifests.manifest');
    }
}
