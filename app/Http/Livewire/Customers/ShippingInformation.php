<?php

namespace App\Http\Livewire\Customers;

use App\Models\Address;
use Livewire\Component;

class ShippingInformation extends Component
{
    public $address;

    // pull the default address from the addresses table in the mount method
    public function mount()
    {
        $this->address = Address::where('is_primary', true)->first();
    }

    public function render()
    {
        return view('livewire.shipping-information.shipping-information');
    }
}
