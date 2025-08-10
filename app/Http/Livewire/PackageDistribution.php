<?php

namespace App\Http\Livewire;

use App\Models\Package;
use App\Models\User;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class PackageDistribution extends Component
{
    use WithPagination;

    public $selectedCustomerId = '';
    public $customerSearch = '';
    public $showCustomerDropdown = false;
    public $selectedCustomerDisplay = '';
    public $selectedPackages = [];
    public $amountCollected = 0;
    public $applyCreditBalance = false;
    public $writeOffAmount = 0;
    public $writeOffReason = '';
    public $distributionNotes = '';
    public $showAdvancedOptions = false;
    public $showConfirmation = false;
    public $distributionSummary = [];
    public $totalCost = 0;
    public $paymentStatus = 'unpaid';
    public $successMessage = '';
    public $errorMessage = '';
    public $isProcessing = false;
    public $search = '';
    public $statusFilter = '';
    public $feeAdjustments = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCustomerId' => ['except' => ''],
    ];

    protected $rules = [
        'amountCollected' => 'required|numeric|min:0',
        'selectedPackages' => 'required|array|min:1',
        'selectedPackages.*' => 'exists:packages,id',
        'selectedCustomerId' => 'required|exists:users,id',
        'writeOffAmount' => 'nullable|numeric|min:0',
        'writeOffReason' => 'nullable|string|max:500',
        'distributionNotes' => 'nullable|string|max:1000',
    ];

    public function rules()
    {
        $rules = [
            'amountCollected' => 'required|numeric|min:0',
            'selectedPackages' => 'required|array|min:1',
            'selectedPackages.*' => 'exists:packages,id',
            'selectedCustomerId' => 'required|exists:users,id',
            'writeOffAmount' => 'nullable|numeric|min:0',
            'writeOffReason' => 'nullable|string|max:500',
            'distributionNotes' => 'nullable|string|max:1000',
        ];
        
        // Only require write-off reason if write-off amount is greater than 0
        $writeOffAmount = (float) ($this->writeOffAmount ?: 0);
        if ($writeOffAmount > 0) {
            $rules['writeOffReason'] = 'required|string|max:500';
        }
        
        return $rules;
    }

    protected $messages = [
        'selectedPackages.required' => 'Please select at least one package for distribution.',
        'selectedPackages.min' => 'Please select at least one package for distribution.',
        'amountCollected.required' => 'Please enter the amount collected from the customer.',
        'amountCollected.numeric' => 'Amount collected must be a valid number.',
        'amountCollected.min' => 'Amount collected cannot be negative.',
        'selectedCustomerId.required' => 'Please select a customer.',
        'writeOffReason.required_with' => 'Please provide a reason when applying a write-off amount.',
    ];

    public function mount()
    {
        // Check if we have packages from the workflow session
        $distributionPackages = session('distribution_packages');
        if ($distributionPackages) {
            $this->loadPackagesFromSession($distributionPackages);
        }
    }

    public function updatedSelectedCustomerId()
    {
        $this->selectedPackages = [];
        $this->resetPage();
        $this->resetForm();
    }

    public function updatedCustomerSearch()
    {
        if (strlen($this->customerSearch) >= 2) {
            $this->showCustomerDropdown = true;
        } else {
            $this->showCustomerDropdown = false;
        }
    }

    public function showAllCustomers()
    {
        $this->showCustomerDropdown = true;
    }

    public function hideCustomerDropdown()
    {
        $this->showCustomerDropdown = false;
    }

    public function selectCustomer($customerId)
    {
        $customer = User::with('profile')->find($customerId);
        if ($customer) {
            $this->selectedCustomerId = $customerId;
            $this->customerSearch = $customer->full_name ?? $customer->name;
            $this->selectedCustomerDisplay = ($customer->full_name ?? $customer->name) . 
                ($customer->profile && $customer->profile->account_number ? ' (' . $customer->profile->account_number . ')' : '');
            $this->showCustomerDropdown = false;
            $this->resetForm();
        }
    }

    public function clearCustomerSelection()
    {
        $this->selectedCustomerId = '';
        $this->customerSearch = '';
        $this->selectedCustomerDisplay = '';
        $this->showCustomerDropdown = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedPackages()
    {
        $this->calculateTotals();
        $this->updatePaymentStatus();
    }

    public function updatedAmountCollected()
    {
        // Ensure amountCollected is always numeric
        $this->amountCollected = (float) ($this->amountCollected ?: 0);
        $this->updatePaymentStatus();
    }

    public function updatedWriteOffAmount()
    {
        // Ensure writeOffAmount is always numeric
        $this->writeOffAmount = (float) ($this->writeOffAmount ?: 0);
        
        // Clear write-off reason if amount is 0
        if ($this->writeOffAmount == 0) {
            $this->writeOffReason = '';
        }
        
        $this->updatePaymentStatus();
    }

    public function updatedApplyCreditBalance()
    {
        $this->updatePaymentStatus();
    }

    public function loadPackagesFromSession($packageIds)
    {
        $packages = Package::whereIn('id', $packageIds)
            ->where('status', PackageStatus::READY)
            ->with(['user.profile'])
            ->get();

        if ($packages->isNotEmpty()) {
            // Check if all packages belong to the same customer
            $customerIds = $packages->pluck('user_id')->unique();
            if ($customerIds->count() === 1) {
                $this->selectedCustomerId = $customerIds->first();
                $this->selectedPackages = $packageIds;
                $this->calculateTotals();
                $this->updatePaymentStatus();
            }
        }

        // Clear the session data
        session()->forget('distribution_packages');
    }

    public function getCustomersProperty()
    {
        return User::whereHas('packages', function ($query) {
            $query->where('status', PackageStatus::READY);
        })
        ->with('profile')
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->get();
    }

    public function getFilteredCustomersProperty()
    {
        $query = User::whereHas('packages', function ($query) {
            $query->where('status', PackageStatus::READY);
        })->with('profile');

        if (!empty($this->customerSearch)) {
            $searchTerm = $this->customerSearch;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%'])
                  ->orWhereHas('profile', function ($profileQuery) use ($searchTerm) {
                      $profileQuery->where('account_number', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        return $query->orderBy('first_name')
                    ->orderBy('last_name')
                    ->limit(10)
                    ->get();
    }

    public function getPackagesProperty()
    {
        if (!$this->selectedCustomerId) {
            return collect();
        }

        $query = Package::where('user_id', $this->selectedCustomerId)
            ->where('status', PackageStatus::READY)
            ->with(['manifest', 'office', 'user.profile']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('tracking_number', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getSelectedCustomerProperty()
    {
        if (!$this->selectedCustomerId) {
            return null;
        }

        return User::with('profile')->find($this->selectedCustomerId);
    }

    public function calculateTotals()
    {
        if (empty($this->selectedPackages) || !$this->selectedCustomerId) {
            $this->totalCost = 0;
            return;
        }

        $packages = Package::whereIn('id', $this->selectedPackages)
            ->where('user_id', $this->selectedCustomerId)
            ->get();

        $this->totalCost = $packages->sum('total_cost');
    }

    public function updatePaymentStatus()
    {
        if ($this->totalCost == 0) {
            $this->paymentStatus = 'unpaid';
            return;
        }

        // Ensure numeric values
        $amountCollected = (float) ($this->amountCollected ?: 0);
        $writeOffAmount = (float) ($this->writeOffAmount ?: 0);
        $totalCost = (float) ($this->totalCost ?: 0);

        // Calculate net total after write-off
        $netTotal = $totalCost - $writeOffAmount;
        
        // Calculate total received (cash + available balance applied)
        $balanceApplied = 0;
        if ($this->applyCreditBalance && $this->getCustomerTotalAvailableBalanceProperty() > 0) {
            $balanceApplied = min($this->getCustomerTotalAvailableBalanceProperty(), max(0, $netTotal - $amountCollected));
        }
        
        $totalReceived = $amountCollected + $balanceApplied;

        if ($totalReceived >= $netTotal) {
            $this->paymentStatus = 'paid';
        } elseif ($totalReceived > 0) {
            $this->paymentStatus = 'partial';
        } else {
            $this->paymentStatus = 'unpaid';
        }
    }

    public function showDistributionConfirmation()
    {
        // Custom validation with better error handling
        $this->validateDistribution();

        $this->calculateTotals();
        $this->updatePaymentStatus();

        $packages = Package::whereIn('id', $this->selectedPackages)
            ->where('user_id', $this->selectedCustomerId)
            ->get();

        $customer = $this->selectedCustomer;

        // Calculate advanced payment details
        $amountCollected = (float) ($this->amountCollected ?: 0);
        $writeOffAmount = (float) ($this->writeOffAmount ?: 0);
        $totalCost = (float) ($this->totalCost ?: 0);
        
        $netTotal = $totalCost - $writeOffAmount;
        $balanceApplied = 0;
        if ($this->applyCreditBalance && $this->getCustomerTotalAvailableBalanceProperty() > 0) {
            $balanceApplied = min($this->getCustomerTotalAvailableBalanceProperty(), max(0, $netTotal - $amountCollected));
        }
        $totalReceived = $amountCollected + $balanceApplied;
        $outstandingBalance = max(0, $netTotal - $totalReceived);

        $this->distributionSummary = [
            'packages' => $packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'tracking_number' => $package->tracking_number,
                    'description' => $package->description,
                    'freight_price' => $package->freight_price ?? 0,
                    'customs_duty' => $package->customs_duty ?? 0,
                    'storage_fee' => $package->storage_fee ?? 0,
                    'delivery_fee' => $package->delivery_fee ?? 0,
                    'total_cost' => $package->total_cost,
                ];
            })->toArray(),
            'total_cost' => $totalCost,
            'write_off_amount' => $writeOffAmount,
            'write_off_reason' => $this->writeOffReason,
            'net_total' => $netTotal,
            'amount_collected' => $amountCollected,
            'balance_applied' => $balanceApplied,
            'apply_credit_balance' => $this->applyCreditBalance,
            'total_received' => $totalReceived,
            'payment_status' => $this->paymentStatus,
            'outstanding_balance' => $outstandingBalance,
            'distribution_notes' => $this->distributionNotes,
            'customer' => [
                'name' => $customer->full_name ?? $customer->name,
                'email' => $customer->email,
                'phone' => $customer->profile->telephone_number ?? 'N/A',
                'account_number' => $customer->profile->account_number ?? 'N/A',
                'tax_number' => $customer->profile->tax_number ?? null,
                'address' => $this->getCustomerAddress($customer),
                'account_balance' => $this->getCustomerAccountBalanceProperty(),
                'credit_balance' => $this->getCustomerCreditBalanceProperty(),
                'total_available_balance' => $this->getCustomerTotalAvailableBalanceProperty(),
            ],
        ];

        $this->showConfirmation = true;
    }

    public function cancelDistribution()
    {
        $this->showConfirmation = false;
        $this->distributionSummary = [];
    }

    public function processDistribution()
    {
        if ($this->isProcessing) {
            return;
        }

        $this->isProcessing = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $distributionService = app(PackageDistributionService::class);
            
            // Prepare distribution options
            $options = [];
            
            if ($this->writeOffAmount > 0) {
                $options['writeOff'] = $this->writeOffAmount;
                $options['writeOffReason'] = $this->writeOffReason;
            }
            
            if ($this->distributionNotes) {
                $options['notes'] = $this->distributionNotes;
            }
            
            if (!empty($this->feeAdjustments)) {
                $options['feeAdjustments'] = $this->feeAdjustments;
            }
            
            $result = $distributionService->distributePackages(
                $this->selectedPackages,
                $this->amountCollected,
                Auth::user(),
                $this->applyCreditBalance,
                $options
            );

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->resetForm();
                
                // Emit event to notify other components
                $this->emit('packageDistributed', [
                    'distribution_id' => $result['distribution']->id,
                    'customer_id' => $this->selectedCustomerId,
                    'package_count' => count($this->selectedPackages),
                ]);
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while processing the distribution. Please try again.';
            \Log::error('Package distribution error in Livewire component', [
                'error' => $e->getMessage(),
                'customer_id' => $this->selectedCustomerId,
                'selected_packages' => $this->selectedPackages,
                'amount_collected' => $this->amountCollected,
            ]);
        } finally {
            $this->isProcessing = false;
            $this->showConfirmation = false;
        }
    }

    public function resetForm()
    {
        $this->selectedPackages = [];
        $this->amountCollected = 0;
        $this->applyCreditBalance = false;
        $this->writeOffAmount = 0;
        $this->writeOffReason = '';
        $this->distributionNotes = '';
        $this->showAdvancedOptions = false;
        $this->feeAdjustments = [];
        $this->totalCost = 0;
        $this->paymentStatus = 'unpaid';
        $this->distributionSummary = [];
        $this->showConfirmation = false;
    }

    public function toggleAdvancedOptions()
    {
        $this->showAdvancedOptions = !$this->showAdvancedOptions;
    }

    public function getCustomerCreditBalanceProperty()
    {
        if ($this->selectedCustomer) {
            return $this->selectedCustomer->credit_balance;
        }
        return 0;
    }

    public function getCustomerAccountBalanceProperty()
    {
        if ($this->selectedCustomer) {
            return $this->selectedCustomer->account_balance;
        }
        return 0;
    }

    public function getCustomerTotalAvailableBalanceProperty()
    {
        if ($this->selectedCustomer) {
            return $this->selectedCustomer->total_available_balance;
        }
        return 0;
    }

    protected function validateDistribution()
    {
        // Use the dynamic validation rules
        $this->validate($this->rules());
    }

    public function clearSelection()
    {
        $this->clearCustomerSelection();
    }

    public function getPaymentStatusColor()
    {
        switch ($this->paymentStatus) {
            case 'paid':
                return 'text-green-600';
            case 'partial':
                return 'text-yellow-600';
            case 'unpaid':
                return 'text-red-600';
            default:
                return 'text-gray-600';
        }
    }

    public function getPaymentStatusLabel()
    {
        switch ($this->paymentStatus) {
            case 'paid':
                return 'Fully Paid';
            case 'partial':
                return 'Partially Paid';
            case 'unpaid':
                return 'Unpaid';
            default:
                return 'Unknown';
        }
    }

    private function getCustomerAddress($customer)
    {
        if (!$customer || !$customer->profile) {
            return 'N/A';
        }

        $profile = $customer->profile;
        $addressParts = array_filter([
            $profile->street_address,
            $profile->city_town,
            $profile->parish,
            $profile->country
        ]);

        return !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
    }

    public function render()
    {
        return view('livewire.package-distribution', [
            'customers' => $this->customers,
            'filteredCustomers' => $this->filteredCustomers,
            'packages' => $this->packages,
            'selectedCustomer' => $this->selectedCustomer,
        ]);
    }
}