<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Package;
use App\Models\User;
use App\Services\PackageDistributionService;
use App\Enums\PackageStatus;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PackageDistribution extends Component
{
    public $selectedPackages = [];
    public $amountCollected = 0;
    public $customerId;
    public $customer;
    public $packages;
    public $showConfirmation = false;
    public $distributionSummary = [];
    public $totalCost = 0;
    public $paymentStatus = 'unpaid';
    public $successMessage = '';
    public $errorMessage = '';
    public $isProcessing = false;
    public $applyCreditBalance = false;
    public $creditApplied = 0;

    protected $rules = [
        'amountCollected' => 'required|numeric|min:0',
        'selectedPackages' => 'required|array|min:1',
        'selectedPackages.*' => 'exists:packages,id',
    ];

    protected $messages = [
        'selectedPackages.required' => 'Please select at least one package for distribution.',
        'selectedPackages.min' => 'Please select at least one package for distribution.',
        'amountCollected.required' => 'Please enter the amount collected.',
        'amountCollected.numeric' => 'Amount collected must be a valid number.',
        'amountCollected.min' => 'Amount collected cannot be negative.',
    ];

    public function mount($manifest = null, $customerId = null)
    {
        $this->customerId = $customerId;
        
        // Check if we have packages from the workflow session
        $distributionPackages = session('distribution_packages');
        if ($distributionPackages) {
            // Load packages from session (from workflow)
            $this->loadPackagesFromSession($distributionPackages);
        } elseif ($customerId) {
            $this->customer = User::with('profile')->find($customerId);
            $this->loadReadyPackages();
        }
    }

    public function loadReadyPackages()
    {
        if (!$this->customerId) {
            return;
        }

        $this->packages = Package::where('user_id', $this->customerId)
            ->where('status', PackageStatus::READY)
            ->with(['manifest', 'office'])
            ->get();
    }

    public function loadPackagesFromSession($packageIds)
    {
        $this->packages = Package::whereIn('id', $packageIds)
            ->where('status', PackageStatus::READY)
            ->with(['manifest', 'office', 'user'])
            ->get();

        // Pre-select all packages from session
        $this->selectedPackages = $packageIds;
        
        // If all packages belong to the same customer, set that customer
        $customerIds = $this->packages->pluck('user_id')->unique();
        if ($customerIds->count() === 1) {
            $this->customerId = $customerIds->first();
            $this->customer = User::with('profile')->find($this->customerId);
        } else {
            // Multiple customers - we'll handle this in the confirmation
            $this->customerId = null;
            $this->customer = null;
        }

        // Calculate initial totals
        $this->calculateTotals();
        $this->updatePaymentStatus();

        // Clear the session data
        session()->forget('distribution_packages');
    }

    public function updatedSelectedPackages()
    {
        $this->calculateTotals();
        $this->updatePaymentStatus();
    }

    public function updatedAmountCollected()
    {
        $this->updatePaymentStatus();
    }

    public function updatedApplyCreditBalance()
    {
        $this->calculateCreditApplication();
        $this->updatePaymentStatus();
    }

    public function calculateTotals()
    {
        if (empty($this->selectedPackages)) {
            $this->totalCost = 0;
            return;
        }

        $selectedPackageModels = $this->packages->whereIn('id', $this->selectedPackages);
        $this->totalCost = $selectedPackageModels->sum('total_cost');
    }

    public function calculateCreditApplication()
    {
        if (!$this->customer || !$this->applyCreditBalance) {
            $this->creditApplied = 0;
            return;
        }

        $this->creditApplied = min($this->totalCost, $this->customer->credit_balance);
    }

    public function updatePaymentStatus()
    {
        if ($this->totalCost == 0) {
            $this->paymentStatus = 'unpaid';
            return;
        }

        $totalReceived = $this->amountCollected + $this->creditApplied;

        if ($totalReceived >= $this->totalCost) {
            $this->paymentStatus = 'paid';
        } elseif ($totalReceived > 0) {
            $this->paymentStatus = 'partial';
        } else {
            $this->paymentStatus = 'unpaid';
        }
    }

    public function showDistributionConfirmation()
    {
        $this->validate();

        // Check if selected packages belong to multiple customers
        $selectedPackageModels = $this->packages->whereIn('id', $this->selectedPackages);
        $selectedCustomerIds = $selectedPackageModels->pluck('user_id')->unique();
        
        if ($selectedCustomerIds->count() > 1) {
            $this->addError('selectedPackages', 'Cannot distribute packages from multiple customers in a single transaction. Please select packages from one customer at a time.');
            return;
        }

        $this->calculateTotals();
        $this->updatePaymentStatus();

        // Prepare distribution summary
        $selectedPackageModels = $this->packages->whereIn('id', $this->selectedPackages);
        
        // Check if all selected packages belong to the same customer
        $selectedCustomerIds = $selectedPackageModels->pluck('user_id')->unique();
        if ($selectedCustomerIds->count() === 1 && !$this->customer) {
            $this->customerId = $selectedCustomerIds->first();
            $this->customer = User::with('profile')->find($this->customerId);
        }
        
        $this->distributionSummary = [
            'packages' => $selectedPackageModels->map(function ($package) {
                return [
                    'id' => $package->id,
                    'tracking_number' => $package->tracking_number,
                    'description' => $package->description,
                    'freight_price' => $package->freight_price ?? 0,
                    'clearance_fee' => $package->clearance_fee ?? 0,
                    'storage_fee' => $package->storage_fee ?? 0,
                    'delivery_fee' => $package->delivery_fee ?? 0,
                    'total_cost' => $package->total_cost,
                ];
            })->toArray(),
            'total_cost' => $this->totalCost,
            'amount_collected' => $this->amountCollected,
            'payment_status' => $this->paymentStatus,
            'outstanding_balance' => max(0, $this->totalCost - $this->amountCollected),
            'customer' => $this->customer ? [
                'name' => $this->customer->full_name ?? $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->profile->telephone_number ?? 'N/A',
                'account_number' => $this->customer->profile->account_number ?? 'N/A',
                'tax_number' => $this->customer->profile->tax_number ?? null,
                'address' => $this->getCustomerAddress(),
            ] : [
                'name' => 'Multiple Customers',
                'email' => 'N/A',
                'phone' => 'N/A',
                'account_number' => 'N/A',
                'tax_number' => null,
                'address' => 'N/A',
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
            
            $result = $distributionService->distributePackages(
                $this->selectedPackages,
                $this->amountCollected,
                Auth::user(),
                $this->applyCreditBalance
            );

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->resetForm();
                $this->loadReadyPackages(); // Refresh the package list
                
                // Emit event to notify other components
                $this->emit('packageDistributed', [
                    'distribution_id' => $result['distribution']->id,
                    'customer_id' => $this->customerId,
                    'package_count' => count($this->selectedPackages),
                ]);
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while processing the distribution. Please try again.';
            \Log::error('Package distribution error in Livewire component', [
                'error' => $e->getMessage(),
                'customer_id' => $this->customerId,
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
        $this->totalCost = 0;
        $this->paymentStatus = 'unpaid';
        $this->distributionSummary = [];
        $this->showConfirmation = false;
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

    private function getCustomerAddress()
    {
        if (!$this->customer || !$this->customer->profile) {
            return 'N/A';
        }

        $profile = $this->customer->profile;
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
        return view('livewire.manifests.package-distribution');
    }
}