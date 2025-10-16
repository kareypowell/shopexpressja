<?php

namespace App\Http\Livewire;

use App\Models\CustomerTransaction;
use App\Models\User;
use App\Models\Manifest;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class TransactionManagement extends Component
{
    use WithPagination;

    public $selectedCustomerId = '';
    public $selectedManifestId = '';
    public $transactionType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $search = '';
    public $showFilters = false;
    public $perPage = 15;

    // Transaction creation
    public $showCreateModal = false;
    public $newTransactionType = 'payment';
    public $newTransactionAmount = '';
    public $newTransactionDescription = '';
    public $newTransactionManifestId = '';
    public $newTransactionCustomerId = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCustomerId' => ['except' => ''],
        'selectedManifestId' => ['except' => ''],
        'transactionType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    protected $rules = [
        'newTransactionType' => 'required|in:payment,charge,credit,debit,write_off,adjustment',
        'newTransactionAmount' => 'required|numeric|min:0.01',
        'newTransactionDescription' => 'required|string|max:255',
        'newTransactionCustomerId' => 'required|exists:users,id',
        'newTransactionManifestId' => 'nullable|exists:manifests,id',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedCustomerId()
    {
        $this->resetPage();
    }

    public function updatedSelectedManifestId()
    {
        $this->resetPage();
    }

    public function updatedTransactionType()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->selectedCustomerId = '';
        $this->selectedManifestId = '';
        $this->transactionType = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->search = '';
        $this->resetPage();
    }

    public function showCreateTransaction()
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function hideCreateTransaction()
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function createTransaction()
    {
        $this->validate();

        try {
            $customer = User::findOrFail($this->newTransactionCustomerId);
            $manifest = $this->newTransactionManifestId ? Manifest::findOrFail($this->newTransactionManifestId) : null;

            $description = $this->newTransactionDescription;
            if ($manifest) {
                $description .= " (Manifest: {$manifest->name})";
            }

            // Create transaction based on type
            switch ($this->newTransactionType) {
                case 'payment':
                    if ($manifest) {
                        $transaction = $customer->recordPaymentForManifest(
                            $this->newTransactionAmount,
                            $description,
                            $manifest,
                            Auth::id()
                        );
                    } else {
                        $transaction = $customer->recordPayment(
                            $this->newTransactionAmount,
                            $description,
                            Auth::id()
                        );
                    }
                    break;

                case 'charge':
                    if ($manifest) {
                        $transaction = $customer->recordChargeForManifest(
                            $this->newTransactionAmount,
                            $description,
                            $manifest,
                            Auth::id()
                        );
                    } else {
                        $transaction = $customer->recordCharge(
                            $this->newTransactionAmount,
                            $description,
                            Auth::id()
                        );
                    }
                    break;

                case 'credit':
                    $transaction = $customer->addCredit(
                        $this->newTransactionAmount,
                        $description,
                        Auth::id(),
                        $manifest ? 'App\\Models\\Manifest' : null,
                        $manifest ? $manifest->id : null
                    );
                    break;

                case 'write_off':
                    $transaction = $customer->recordWriteOff(
                        $this->newTransactionAmount,
                        $description,
                        Auth::id(),
                        $manifest ? 'App\\Models\\Manifest' : null,
                        $manifest ? $manifest->id : null
                    );
                    break;

                default:
                    throw new \Exception('Invalid transaction type');
            }

            $this->hideCreateTransaction();
            $this->emit('transactionCreated', $transaction->id);
            session()->flash('success', 'Transaction created successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }

    private function resetCreateForm()
    {
        $this->newTransactionType = 'payment';
        $this->newTransactionAmount = '';
        $this->newTransactionDescription = '';
        $this->newTransactionManifestId = '';
        $this->newTransactionCustomerId = '';
    }

    public function getTransactionsProperty()
    {
        $query = CustomerTransaction::with(['user', 'createdBy', 'manifest'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($this->selectedCustomerId) {
            $query->where('user_id', $this->selectedCustomerId);
        }

        if ($this->selectedManifestId) {
            $query->forManifest($this->selectedManifestId);
        }

        if ($this->transactionType) {
            $query->where('type', $this->transactionType);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('amount', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                               ->orWhere('last_name', 'like', '%' . $this->search . '%')
                               ->orWhere('email', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('manifest', function ($manifestQuery) {
                      $manifestQuery->where('name', 'like', '%' . $this->search . '%')
                                   ->orWhere('reservation_number', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->paginate($this->perPage);
    }

    public function getCustomersProperty()
    {
        return User::customers()
            ->with('profile')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function getManifestsProperty()
    {
        return Manifest::orderBy('name')->get();
    }

    public function getTransactionTypesProperty()
    {
        return [
            'payment' => 'Payment',
            'charge' => 'Charge',
            'credit' => 'Credit',
            'debit' => 'Debit',
            'write_off' => 'Write Off',
            'adjustment' => 'Adjustment',
        ];
    }

    public function render()
    {
        return view('livewire.transaction-management', [
            'transactions' => $this->transactions,
            'customers' => $this->customers,
            'manifests' => $this->manifests,
            'transactionTypes' => $this->transactionTypes,
        ]);
    }
}