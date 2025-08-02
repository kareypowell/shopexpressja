<?php

namespace App\Http\Livewire\Customers;

use App\Http\Livewire\Concerns\HasBreadcrumbs;
use Livewire\Component;

class AdminCustomer extends Component
{
    use HasBreadcrumbs;

    public function mount()
    {
        $this->setCustomerIndexBreadcrumbs();
    }

    public function render()
    {
        return view('livewire.customers.customer');
    }
}
