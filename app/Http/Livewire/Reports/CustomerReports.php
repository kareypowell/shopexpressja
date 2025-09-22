<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;

class CustomerReports extends Component
{
    public $selectedCustomerId = null;

    protected $listeners = [
        'customerSelected' => 'handleCustomerSelected',
        'customerCleared' => 'handleCustomerCleared',
    ];

    public function handleCustomerSelected($customerId)
    {
        $this->selectedCustomerId = $customerId;
    }

    public function handleCustomerCleared()
    {
        $this->selectedCustomerId = null;
    }

    public function render()
    {
        return view('livewire.reports.customer-reports');
    }
}