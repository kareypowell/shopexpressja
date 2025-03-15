<?php

namespace App\Http\Livewire;

use App\Models\PreAlert as PreAlertModel;
use App\Models\Shipper;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PreAlert extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;
    public int $shipper_id = 0;
    public string $tracking_number = '';
    public string $description = '';
    public float $value = 0.0;
    public $file_path = null;
    public string $file_url = '';
    public $shipperList = [];

    public function mount()
    {
        $this->shipperList = Shipper::orderBy('name', 'asc')->get();
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
        $this->shipper_id = 0;
        $this->tracking_number = '';
        $this->description = '';
        $this->value = 0.0;
        $this->file_path = '';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function store()
    {
        $this->validate([
            'shipper_id' => 'required',
            'tracking_number' => 'required',
            'description' => 'required',
            'value' => 'required|numeric|min:1',
            'file_path' => 'required|max:5120',
        ]);

        if ($this->file_path) {
            // Store the file in the public disk under the 'pre-alerts' directory
            $path = $this->file_path->store('pre-alerts', 'public');

            // Get the public URL
            $this->file_url = config('app.url') . Storage::url($path);
        }

        $pre_alert = PreAlertModel::create([
            'user_id' => auth()->id(),
            'shipper_id' => $this->shipper_id,
            'tracking_number' => $this->tracking_number,
            'description' => $this->description,
            'value' => $this->value,
            'file_path' => $this->file_url,
        ]);

        if ($pre_alert) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Pre-Alert Created Successfully.',
            ]);
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Pre-Alert Creation Failed.',
            ]);
        }

        $this->closeModal();
        $this->resetInputFields();

        // return redirect('/pre-alerts');
    }

    public function render()
    {
        return view('livewire.pre-alerts.pre-alert');
    }
}
