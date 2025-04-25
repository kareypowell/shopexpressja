<?php

namespace App\Http\Livewire\Manifests\Packages;

use Livewire\Component;
use App\Models\User;
use App\Models\Shipper;
use App\Models\Office;
use App\Models\Package;

class EditManifestPackage extends Component
{
    public int $user_id = 0;
    public int $manifest_id = 0;
    public int $shipper_id = 0;
    public int $office_id = 0;
    public int $package_id = 0;
    public string $warehouse_receipt_no = '';
    public string $tracking_number = '';
    public string $description = '';
    public string $weight = '';
    public $value = '';
    public string $status = '';
    public string $estimated_value = '';
    public $customerList = [];
    public $shipperList = [];
    public $officeList = [];


    public function mount()
    {
        $this->customerList = User::where('role_id', 3)
            ->where('email_verified_at', '!=', '')
            ->orderBy('last_name', 'asc')->get();

        $this->officeList = Office::orderBy('name', 'asc')->get();

        $this->shipperList = Shipper::orderBy('name', 'asc')->get();

        $this->manifest_id = request()->route('manifest_id');

        $this->package_id = request()->route('package_id');

        // Load the package data
        $package = Package::where('id', $this->package_id)->where('manifest_id', $this->manifest_id)->first();
        if ($package) {
            $this->user_id = $package->user_id;
            $this->shipper_id = $package->shipper_id;
            $this->office_id = $package->office_id;
            $this->warehouse_receipt_no = $package->warehouse_receipt_no;
            $this->tracking_number = $package->tracking_number;
            $this->description = $package->description;
            $this->weight = $package->weight;
            $this->value = $package->value;
            $this->status = $package->status;
            $this->estimated_value = $package->estimated_value;
        }
    }

    public function update()
    {
        $this->validate([
            'user_id' => 'required|integer',
            'shipper_id' => 'required|integer',
            'office_id' => 'required|integer',
            'warehouse_receipt_no' => 'required|string|max:255',
            'tracking_number' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'weight' => 'required|numeric',
            'value' => 'nullable|numeric',
            'status' => 'required|string|max:255',
            'estimated_value' => 'nullable|numeric',
        ]);

        // Update the package in the database
        $package = Package::where('id', $this->package_id)->where('manifest_id', $this->manifest_id)->first();
        if ($package) {
            $package->update([
                'user_id' => $this->user_id,
                'shipper_id' => $this->shipper_id,
                'office_id' => $this->office_id,
                'warehouse_receipt_no' => $this->warehouse_receipt_no,
                'tracking_number' => $this->tracking_number,
                'description' => $this->description,
                'weight' => $this->weight,
                'value' => $this->value,
                'status' => $this->status,
                'estimated_value' => $this->estimated_value,
            ]);
        }

        // session()->flash('message', __('Package updated successfully.'));

        return redirect(route('manifests.packages', ['manifest_id' => $this->manifest_id]))
            ->with('message', __('Package updated successfully.'));
    }

    public function render()
    {
        return view('livewire.manifests.packages.edit-manifest-package');
    }
}
