<?php

namespace App\Http\Livewire\Admin;

use App\Models\Address;
use Livewire\Component;

class AddressForm extends Component
{
    public $address;
    public $street_address = '';
    public $city = '';
    public $state = '';
    public $zip_code = '';
    public $country = '';
    public $is_primary = false;
    public $isEditing = false;

    protected $rules = [
        'street_address' => 'required|string|max:255',
        'city' => 'required|string|max:100',
        'state' => 'required|string|max:100',
        'zip_code' => 'required|string|max:20',
        'country' => 'required|string|max:100',
        'is_primary' => 'boolean',
    ];

    protected $messages = [
        'street_address.required' => 'Street address is required.',
        'street_address.max' => 'Street address cannot exceed 255 characters.',
        'city.required' => 'City is required.',
        'city.max' => 'City cannot exceed 100 characters.',
        'state.required' => 'State is required.',
        'state.max' => 'State cannot exceed 100 characters.',
        'zip_code.required' => 'ZIP code is required.',
        'zip_code.max' => 'ZIP code cannot exceed 20 characters.',
        'country.required' => 'Country is required.',
        'country.max' => 'Country cannot exceed 100 characters.',
    ];

    public function mount($address = null)
    {
        if ($address) {
            $this->address = $address;
            $this->isEditing = true;
            $this->street_address = $address->street_address;
            $this->city = $address->city;
            $this->state = $address->state;
            $this->zip_code = $address->zip_code;
            $this->country = $address->country;
            $this->is_primary = $address->is_primary;
        } else {
            $this->address = new Address();
            $this->isEditing = false;
        }
    }

    public function save()
    {
        $this->validate();

        try {
            if ($this->isEditing) {
                $this->address->update([
                    'street_address' => $this->street_address,
                    'city' => $this->city,
                    'state' => $this->state,
                    'zip_code' => $this->zip_code,
                    'country' => $this->country,
                    'is_primary' => $this->is_primary,
                ]);

                session()->flash('success', 'Address updated successfully.');
            } else {
                Address::create([
                    'street_address' => $this->street_address,
                    'city' => $this->city,
                    'state' => $this->state,
                    'zip_code' => $this->zip_code,
                    'country' => $this->country,
                    'is_primary' => $this->is_primary,
                ]);

                session()->flash('success', 'Address created successfully.');
            }

            return redirect()->route('admin.addresses.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save address. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.admin.address-form');
    }
}