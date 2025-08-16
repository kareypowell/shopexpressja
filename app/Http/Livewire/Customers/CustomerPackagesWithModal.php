<?php

namespace App\Http\Livewire\Customers;

use Livewire\Component;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerPackagesWithModal extends Component
{
    use AuthorizesRequests;

    public User $customer;
    public $showModal = false;
    public $selectedPackage = null;
    public $selectedConsolidatedPackage = null;
    public $isConsolidatedPackage = false;
    public $showIndividualPackages = false;

    protected $listeners = ['showPackageDetails', 'showConsolidatedPackageDetails'];

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
            $this->isConsolidatedPackage = false;
            $this->selectedConsolidatedPackage = null;
            $this->showModal = true;
        }
    }

    public function showConsolidatedPackageDetails($consolidatedPackageId)
    {
        $this->selectedConsolidatedPackage = \App\Models\ConsolidatedPackage::with([
            'packages.manifest', 
            'packages.items', 
            'packages.shipper', 
            'packages.office',
            'createdBy'
        ])
            ->where('id', $consolidatedPackageId)
            ->where('customer_id', $this->customer->id)
            ->first();
            
        if ($this->selectedConsolidatedPackage) {
            $this->isConsolidatedPackage = true;
            $this->selectedPackage = null;
            $this->showModal = true;
        }
    }

    public function toggleIndividualPackages()
    {
        $this->showIndividualPackages = !$this->showIndividualPackages;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedPackage = null;
        $this->selectedConsolidatedPackage = null;
        $this->isConsolidatedPackage = false;
        $this->showIndividualPackages = false;
    }

    public function render()
    {
        return view('livewire.customers.customer-packages-with-modal');
    }
}