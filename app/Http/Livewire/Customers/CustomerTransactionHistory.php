<?php

namespace App\Http\Livewire\Customers;

use App\Models\User;
use App\Models\CustomerTransaction;
use App\Services\TransactionReviewService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CustomerTransactionHistory extends Component
{
    public $customer;
    public $showTransactions = false;
    public $recentTransactions = [];
    public $showReviewModal = false;
    public $selectedTransaction = null;
    public $reviewReason = '';

    protected $rules = [
        'reviewReason' => 'required|string|min:10|max:500',
    ];

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

    public function openReviewModal($transactionId)
    {
        $this->selectedTransaction = CustomerTransaction::find($transactionId);
        $this->reviewReason = '';
        $this->showReviewModal = true;
    }

    public function closeReviewModal()
    {
        $this->selectedTransaction = null;
        $this->reviewReason = '';
        $this->showReviewModal = false;
    }

    public function submitReviewRequest()
    {
        $this->validate([
            'reviewReason' => 'required|string|min:10|max:500',
        ]);

        if ($this->selectedTransaction) {
            $reviewService = app(TransactionReviewService::class);
            
            if ($reviewService->flagTransactionForReview($this->selectedTransaction, $this->reviewReason)) {
                session()->flash('message', 'Your review request has been submitted. An administrator will review your concern and contact you if needed.');
                $this->closeReviewModal();
                $this->loadRecentTransactions(); // Refresh transactions
            } else {
                session()->flash('error', 'Failed to submit review request. Please try again or contact support.');
            }
        }
    }

    public function refreshData()
    {
        $this->loadRecentTransactions();
        session()->flash('message', 'Account data refreshed successfully.');
    }

    public function render()
    {
        $accountSummary = $this->customer->getAccountBalanceSummary();
        
        return view('livewire.customers.customer-transaction-history', [
            'accountSummary' => $accountSummary,
        ]);
    }
}