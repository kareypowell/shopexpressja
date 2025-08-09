<?php

namespace App\Http\Livewire\Customers;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CustomerAccountBalance extends Component
{
    public $customer;
    public $showTransactions = false;
    public $recentTransactions = [];

    public function mount($customerId = null)
    {
        // If no customer ID provided, use the authenticated user
        if ($customerId) {
            $this->customer = User::with('profile')->findOrFail($customerId);
        } else {
            $this->customer = Auth::user();
        }

        $this->loadRecentTransactions();
    }

    public function loadRecentTransactions()
    {
        $this->recentTransactions = $this->customer->getRecentTransactions(10);
    }

    public function toggleTransactions()
    {
        $this->showTransactions = !$this->showTransactions;
        if ($this->showTransactions && $this->recentTransactions->isEmpty()) {
            $this->loadRecentTransactions();
        }
    }

    public function render()
    {
        $accountSummary = $this->customer->getAccountBalanceSummary();
        
        return view('livewire.customers.customer-account-balance', [
            'accountSummary' => $accountSummary,
        ]);
    }
}