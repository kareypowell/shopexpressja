<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Package;
use App\Models\CustomerTransaction;
use App\Services\ReportDataService;
use App\Services\ReportExportService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerReportDetail extends Component
{
    use WithPagination;

    public $customerId;
    public $customer;
    public $activeTab = 'overview';
    public $dateFrom;
    public $dateTo;
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;

    protected $queryString = [
        'activeTab' => ['except' => 'overview'],
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 25],
    ];

    protected $listeners = [
        'refreshCustomerData' => '$refresh',
    ];

    public function mount($customerId = null)
    {
        $this->customerId = $customerId;
        $this->dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        if ($customerId) {
            $this->customer = User::find($customerId);
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function setActiveTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function exportCustomerReport(string $format = 'pdf')
    {
        if (!$this->customer) {
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'No customer selected for export.'
            ]);
            return;
        }

        try {
            $exportService = app(ReportExportService::class);
            $reportData = $this->getCustomerReportData();
            
            $jobId = $exportService->queueExport($format, [
                'type' => 'customer_detail',
                'customer_id' => $this->customerId,
                'data' => $reportData,
                'date_range' => [
                    'from' => $this->dateFrom,
                    'to' => $this->dateTo
                ]
            ], auth()->user());

            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'success',
                'message' => 'Customer report export started. You will be notified when complete.'
            ]);

            $this->emit('exportStarted', $jobId);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'Export failed: ' . $e->getMessage()
            ]);
        }
    }

    public function getCustomerOverview()
    {
        if (!$this->customer) {
            return [];
        }

        $dateFrom = Carbon::parse($this->dateFrom);
        $dateTo = Carbon::parse($this->dateTo);

        // Package statistics
        $packageStats = Package::where('user_id', $this->customerId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('
                COUNT(*) as total_packages,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_packages,
                SUM(CASE WHEN status IN ("shipped", "customs") THEN 1 ELSE 0 END) as in_transit_packages,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_packages,
                SUM(COALESCE(freight_price, 0) + COALESCE(clearance_fee, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)) as total_charges,
                SUM(COALESCE(weight, 0)) as total_weight,
                AVG(COALESCE(freight_price, 0) + COALESCE(clearance_fee, 0) + COALESCE(storage_fee, 0) + COALESCE(delivery_fee, 0)) as average_package_cost
            ')
            ->first();

        // Payment statistics
        $paymentStats = CustomerTransaction::where('user_id', $this->customerId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('
                SUM(CASE WHEN type = "payment" THEN amount ELSE 0 END) as total_payments,
                SUM(CASE WHEN type = "charge" THEN amount ELSE 0 END) as total_charges_recorded,
                COUNT(CASE WHEN type = "payment" THEN 1 END) as payment_count,
                AVG(CASE WHEN type = "payment" THEN amount END) as average_payment
            ')
            ->first();

        // Recent activity
        $recentPackages = Package::where('user_id', $this->customerId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'tracking_number', 'status', 'created_at']);

        return [
            'package_stats' => $packageStats,
            'payment_stats' => $paymentStats,
            'recent_packages' => $recentPackages,
            'account_balance' => $this->customer->account_balance,
            'customer_since' => $this->customer->created_at,
        ];
    }

    public function getPackageHistory()
    {
        if (!$this->customer) {
            return collect([]);
        }

        $query = Package::where('user_id', $this->customerId)
            ->with(['manifest', 'office'])
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom),
                Carbon::parse($this->dateTo)
            ]);

        // Apply search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('tracking_number', 'LIKE', "%{$this->search}%")
                  ->orWhere('description', 'LIKE', "%{$this->search}%")
                  ->orWhere('status', 'LIKE', "%{$this->search}%");
            });
        }

        // Apply sorting
        $validSortFields = ['created_at', 'tracking_number', 'status', 'freight_price', 'weight'];
        if (in_array($this->sortField, $validSortFields)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($this->perPage);
    }

    public function getTransactionHistory()
    {
        if (!$this->customer) {
            return collect([]);
        }

        $query = CustomerTransaction::where('user_id', $this->customerId)
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom),
                Carbon::parse($this->dateTo)
            ]);

        // Apply search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('description', 'LIKE', "%{$this->search}%")
                  ->orWhere('type', 'LIKE', "%{$this->search}%")
                  ->orWhere('reference_id', 'LIKE', "%{$this->search}%");
            });
        }

        // Apply sorting
        $validSortFields = ['created_at', 'amount', 'type', 'description'];
        if (in_array($this->sortField, $validSortFields)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($this->perPage);
    }

    public function getAccountBalanceHistory()
    {
        if (!$this->customer) {
            return [];
        }

        // Get balance changes over time
        $balanceHistory = CustomerTransaction::where('user_id', $this->customerId)
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom),
                Carbon::parse($this->dateTo)
            ])
            ->orderBy('created_at')
            ->get(['amount', 'type', 'created_at', 'description']);

        $runningBalance = $this->customer->account_balance;
        $history = [];

        // Calculate running balance backwards from current
        $totalChange = $balanceHistory->sum(function($transaction) {
            return $transaction->type === 'payment' ? $transaction->amount : -$transaction->amount;
        });
        
        $startingBalance = $runningBalance - $totalChange;
        $currentBalance = $startingBalance;

        foreach ($balanceHistory as $transaction) {
            $change = $transaction->type === 'payment' ? $transaction->amount : -$transaction->amount;
            $currentBalance += $change;
            
            $history[] = [
                'date' => $transaction->created_at->format('Y-m-d'),
                'description' => $transaction->description,
                'change' => $change,
                'balance' => $currentBalance,
                'type' => $transaction->type
            ];
        }

        return $history;
    }

    protected function getCustomerReportData()
    {
        return [
            'customer' => $this->customer,
            'overview' => $this->getCustomerOverview(),
            'packages' => $this->getPackageHistory()->items(),
            'transactions' => $this->getTransactionHistory()->items(),
            'balance_history' => $this->getAccountBalanceHistory(),
            'date_range' => [
                'from' => $this->dateFrom,
                'to' => $this->dateTo
            ]
        ];
    }

    public function render()
    {
        $data = [];
        
        if ($this->customer) {
            switch ($this->activeTab) {
                case 'overview':
                    $data['overview'] = $this->getCustomerOverview();
                    break;
                case 'packages':
                    $data['packages'] = $this->getPackageHistory();
                    break;
                case 'transactions':
                    $data['transactions'] = $this->getTransactionHistory();
                    break;
                case 'balance':
                    $data['balanceHistory'] = $this->getAccountBalanceHistory();
                    break;
            }
        }

        return view('livewire.reports.customer-report-detail', $data);
    }
}