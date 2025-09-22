<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerSearch extends Component
{
    public $search = '';
    public $selectedCustomerId = null;
    public $selectedCustomer = null;
    public $showResults = false;
    public $customers = [];

    protected $listeners = [
        'clearCustomerSelection' => 'clearSelection',
    ];

    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            $this->searchCustomers();
            $this->showResults = true;
        } else {
            $this->customers = [];
            $this->showResults = false;
        }
    }

    public function searchCustomers()
    {
        $this->customers = User::where(function($query) {
                $query->where('name', 'LIKE', "%{$this->search}%")
                      ->orWhere('email', 'LIKE', "%{$this->search}%");
            })
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('packages')
                      ->whereRaw('packages.user_id = users.id');
            })
            ->select(['id', 'first_name', 'last_name', 'email', 'account_balance', 'created_at'])
            ->orderBy('first_name')
            ->limit(10)
            ->get();
    }

    public function selectCustomer($customerId)
    {
        $this->selectedCustomerId = $customerId;
        $this->selectedCustomer = User::find($customerId);
        $this->search = $this->selectedCustomer->first_name . ' ' . $this->selectedCustomer->last_name;
        $this->showResults = false;
        
        // Emit event to parent component
        $this->emit('customerSelected', $customerId);
    }

    public function clearSelection()
    {
        $this->selectedCustomerId = null;
        $this->selectedCustomer = null;
        $this->search = '';
        $this->customers = [];
        $this->showResults = false;
        
        $this->emit('customerCleared');
    }

    public function hideResults()
    {
        // Delay hiding to allow for click events
        $this->showResults = false;
    }

    public function render()
    {
        return view('livewire.reports.customer-search');
    }
}