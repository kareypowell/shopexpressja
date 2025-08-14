<?php

namespace App\Http\Livewire\Admin;

use App\Models\User;
use App\Models\CustomerTransaction;
use App\Mail\BalanceAddedNotification;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CustomerBalanceManager extends Component
{
    public $customer;
    public $showModal = false;
    public $transactionType = 'account_balance'; // account_balance or credit_balance
    public $amount = '';
    public $description = '';
    public $sendEmailNotification = true;

    protected $rules = [
        'transactionType' => 'required|in:account_balance,credit_balance',
        'amount' => 'required|numeric|min:0.01|max:999999.99',
        'description' => 'required|string|min:5|max:255',
        'sendEmailNotification' => 'boolean',
    ];

    protected $messages = [
        'amount.required' => 'Please enter an amount.',
        'amount.numeric' => 'Amount must be a valid number.',
        'amount.min' => 'Amount must be at least $0.01.',
        'amount.max' => 'Amount cannot exceed $999,999.99.',
        'description.required' => 'Please provide a description for this transaction.',
        'description.min' => 'Description must be at least 5 characters.',
        'description.max' => 'Description cannot exceed 255 characters.',
    ];

    public function mount(User $customer)
    {
        $this->customer = $customer->load(['profile', 'transactions' => function($query) {
            $query->latest()->limit(5);
        }]);

        // Ensure user has admin privileges
        if (!Auth::user()->hasRole(['superadmin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function resetForm()
    {
        $this->transactionType = 'account_balance';
        $this->amount = '';
        $this->description = '';
        $this->sendEmailNotification = true;
        $this->resetErrorBag();
    }

    public function addBalance()
    {
        $this->validate();

        try {
            $amount = (float) $this->amount;
            $adminUser = Auth::user();

            // Create the transaction based on type
            if ($this->transactionType === 'account_balance') {
                $transaction = $this->customer->addCredit(
                    $amount,
                    $this->description,
                    $adminUser->id,
                    'admin_adjustment',
                    null,
                    [
                        'admin_name' => $adminUser->full_name,
                        'admin_email' => $adminUser->email,
                        'adjustment_type' => 'account_balance_addition',
                    ]
                );
            } else {
                $transaction = $this->customer->addOverpaymentCredit(
                    $amount,
                    $this->description,
                    $adminUser->id,
                    'admin_adjustment',
                    null,
                    [
                        'admin_name' => $adminUser->full_name,
                        'admin_email' => $adminUser->email,
                        'adjustment_type' => 'credit_balance_addition',
                    ]
                );
            }

            // Send email notification if requested
            if ($this->sendEmailNotification) {
                $this->sendCustomerNotification($transaction);
            }

            // Log the admin action
            Log::info('Admin added balance to customer account', [
                'admin_id' => $adminUser->id,
                'admin_name' => $adminUser->full_name,
                'customer_id' => $this->customer->id,
                'customer_name' => $this->customer->full_name,
                'transaction_type' => $this->transactionType,
                'amount' => $amount,
                'description' => $this->description,
                'transaction_id' => $transaction->id,
            ]);

            // Refresh customer data
            $this->customer->refresh();
            $this->customer->load(['transactions' => function($query) {
                $query->latest()->limit(5);
            }]);

            session()->flash('success', 'Balance added successfully! The customer has been notified via email.');
            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('Failed to add balance to customer account', [
                'admin_id' => Auth::id(),
                'customer_id' => $this->customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Failed to add balance. Please try again or contact support.');
        }
    }

    protected function sendCustomerNotification($transaction)
    {
        try {
            Mail::to($this->customer->email)->send(new BalanceAddedNotification($transaction));
            
            Log::info('Balance addition notification sent to customer', [
                'customer_id' => $this->customer->id,
                'customer_email' => $this->customer->email,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send balance addition notification', [
                'customer_id' => $this->customer->id,
                'customer_email' => $this->customer->email,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            // Don't fail the entire operation if email fails
            session()->flash('warning', 'Balance added successfully, but email notification failed to send.');
        }
    }

    public function render()
    {
        return view('livewire.admin.customer-balance-manager')
            ->layout('layouts.app', [
                'title' => 'Balance Management - ' . $this->customer->full_name
            ]);
    }
}