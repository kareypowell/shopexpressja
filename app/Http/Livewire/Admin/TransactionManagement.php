<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CustomerTransaction;
use App\Models\User;
use App\Models\PackageDistribution;
use App\Services\TransactionReviewService;
use Carbon\Carbon;

class TransactionManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $filterCustomer = '';
    public $selectedTransaction = null;
    public $showTransactionModal = false;
    public $showDisputeModal = false;
    public $disputeReason = '';
    public $showReviewModal = false;
    public $adminResponse = '';
    public $filterReviewStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterDateFrom' => ['except' => ''],
        'filterDateTo' => ['except' => ''],
        'filterCustomer' => ['except' => ''],
        'filterReviewStatus' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterCustomer()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterCustomer = '';
        $this->filterReviewStatus = '';
        $this->resetPage();
    }

    public function viewTransaction($transactionId)
    {
        $this->selectedTransaction = CustomerTransaction::with(['user', 'createdBy'])->find($transactionId);
        $this->showTransactionModal = true;
    }

    public function closeTransactionModal()
    {
        $this->selectedTransaction = null;
        $this->showTransactionModal = false;
    }

    public function openDisputeModal($transactionId)
    {
        $this->selectedTransaction = CustomerTransaction::find($transactionId);
        $this->disputeReason = '';
        $this->showDisputeModal = true;
    }

    public function closeDisputeModal()
    {
        $this->selectedTransaction = null;
        $this->disputeReason = '';
        $this->showDisputeModal = false;
    }

    public function disputeTransaction()
    {
        $this->validate([
            'disputeReason' => 'required|string|min:10|max:500',
        ]);

        if ($this->selectedTransaction && $this->selectedTransaction->reference_type === 'package_distribution') {
            $distribution = PackageDistribution::find($this->selectedTransaction->reference_id);
            if ($distribution) {
                $distribution->dispute($this->disputeReason);
                
                session()->flash('message', 'Transaction has been marked as disputed.');
                $this->closeDisputeModal();
            }
        }
    }

    public function openReviewModal($transactionId)
    {
        $this->selectedTransaction = CustomerTransaction::find($transactionId);
        $this->adminResponse = '';
        $this->showReviewModal = true;
    }

    public function closeReviewModal()
    {
        $this->selectedTransaction = null;
        $this->adminResponse = '';
        $this->showReviewModal = false;
    }

    public function resolveReview()
    {
        $this->validate([
            'adminResponse' => 'required|string|min:10|max:1000',
        ]);

        if ($this->selectedTransaction) {
            $reviewService = app(TransactionReviewService::class);
            
            if ($reviewService->resolveTransactionReview(
                $this->selectedTransaction, 
                $this->adminResponse, 
                auth()->id()
            )) {
                session()->flash('message', 'Transaction review has been resolved.');
                $this->closeReviewModal();
            } else {
                session()->flash('error', 'Failed to resolve transaction review.');
            }
        }
    }

    public function getTransactionsProperty()
    {
        $query = CustomerTransaction::with(['user', 'createdBy'])
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('reference_id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                               ->orWhere('last_name', 'like', '%' . $this->search . '%')
                               ->orWhere('email', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply type filter
        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        // Apply customer filter
        if ($this->filterCustomer) {
            $query->where('user_id', $this->filterCustomer);
        }

        // Apply date filters
        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        // Apply review status filter
        if ($this->filterReviewStatus === 'flagged') {
            $query->where('flagged_for_review', true)->where('review_resolved', false);
        } elseif ($this->filterReviewStatus === 'resolved') {
            $query->where('flagged_for_review', true)->where('review_resolved', true);
        } elseif ($this->filterReviewStatus === 'not_flagged') {
            $query->where('flagged_for_review', false);
        }

        return $query->paginate(25);
    }

    public function getCustomersProperty()
    {
        return User::where('role_id', 3)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function getTransactionTypesProperty()
    {
        return [
            'payment' => 'Payment',
            'charge' => 'Charge',
            'credit' => 'Credit',
            'debit' => 'Debit',
            'write_off' => 'Write-off',
            'adjustment' => 'Adjustment',
        ];
    }

    public function getReviewStatusOptionsProperty()
    {
        return [
            'flagged' => 'Flagged for Review',
            'resolved' => 'Review Resolved',
            'not_flagged' => 'Not Flagged',
        ];
    }

    public function render()
    {
        return view('livewire.admin.transaction-management', [
            'transactions' => $this->transactions,
            'customers' => $this->customers,
            'transactionTypes' => $this->transactionTypes,
            'reviewStatusOptions' => $this->reviewStatusOptions,
        ])->layout('layouts.app');
    }
}