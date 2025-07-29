<?php

namespace App\Http\Livewire\Manifests;

use Livewire\Component;
use App\Models\Manifest as ManifestModel;

class Manifest extends Component
{
    public bool $isOpen = false;

    public string $type;

    public string $name;

    public string $reservation_number;

    public string $flight_number;

    public string $flight_destination;

    // Vessel information properties for sea manifests
    public string $vessel_name;

    public string $voyage_number;

    public string $departure_port;

    public string $arrival_port;

    public string $estimated_arrival_date;

    public string $exchange_rate;

    public string $shipment_date;


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
                'vessel_name' => ['required', 'string', 'max:255'],
                'voyage_number' => ['required', 'string', 'max:255'],
                'departure_port' => ['required', 'string', 'max:255'],
                'arrival_port' => ['nullable', 'string', 'max:255'],
                'estimated_arrival_date' => ['nullable', 'date', 'after:shipment_date'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'flight_number' => ['required'],
                'flight_destination' => ['required'],
            ]);
        }

        $this->validate($rules);

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

        // return redirect('/manifests');
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

    public function render()
    {
        return view('livewire.manifests.manifest');
    }
}
