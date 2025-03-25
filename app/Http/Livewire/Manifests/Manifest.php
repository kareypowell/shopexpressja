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
        $this->validate([
            'type' => ['required'],
            'name' => ['required'],
            'reservation_number' => ['required'],
            'flight_number' => ['required'],
            'flight_destination' => ['required'],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'shipment_date' => ['required', 'date'],
        ]);

        $manifest = ManifestModel::create([
            'name' => $this->name,
            'shipment_date' => $this->shipment_date,
            'reservation_number' => $this->reservation_number,
            'flight_number' => $this->flight_number,
            'flight_destination' => $this->flight_destination,
            'exchange_rate' => $this->exchange_rate,
            'type' => $this->type,
            'is_open' => true
        ]);

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

    public function render()
    {
        return view('livewire.manifests.manifest');
    }
}
