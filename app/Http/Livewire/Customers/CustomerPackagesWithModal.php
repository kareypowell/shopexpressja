<?php

namespace App\Http\Livewire\Customers;

use Livewire\Component;
use App\Models\User;
use App\Models\Package;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerPackagesWithModal extends Component
{
    use AuthorizesRequests;

    public User $customer;
    public $showModal = false;
    public $selectedPackage = null;

    protected $listeners = ['showPackageDetails'];

    public function mount(User $customer)
    {
        $this->customer = $customer;
        $this->authorize('customer.view', $customer);
    }

    public function showPackageDetails($packageId)
    {
        $this->selectedPackage = Package::with(['manifest', 'items', 'shipper', 'office'])
            ->where('id', $packageId)
            ->where('user_id', $this->customer->id)
            ->first();
            
        if ($this->selectedPackage) {
            $this->showModal = true;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedPackage = null;
    }

    public function render()
    {
        return view('livewire.customers.customer-packages-with-modal');
    }
}