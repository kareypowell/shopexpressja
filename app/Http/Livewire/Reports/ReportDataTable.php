<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ReportDataService;
use App\Services\ReportExportService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportDataTable extends Component
{
    use WithPagination;

    public string $reportType = 'sales_collections';
    public array $data = [];
    public array $filters = [];
    public string $search = '';
    public string $sortField = '';
    public string $sortDirection = 'asc';
    public int $perPage = 25;
    public array $selectedColumns = [];
    public array $availableColumns = [];
    public bool $showColumnSelector = false;
    public array $selectedRows = [];
    public bool $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => ''],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 25],
    ];

    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
        'refreshTable' => '$refresh',
    ];

    public function mount(string $reportType = 'sales_collections', array $data = [], array $filters = [])
    {
        $this->reportType = $reportType;
        $this->data = $data;
        $this->filters = $filters;
        $this->initializeColumns();
    }

    public function initializeColumns()
    {
        $this->availableColumns = $this->getAvailableColumns();
        $this->selectedColumns = array_keys($this->availableColumns);
    }

    public function getAvailableColumns(): array
    {
        switch ($this->reportType) {
            case 'sales_collections':
                return [
                    'manifest_number' => 'Manifest #',
                    'manifest_type' => 'Type',
                    'office_name' => 'Office',
                    'total_packages' => 'Packages',
                    'total_owed' => 'Total Owed',
                    'total_collected' => 'Collected',
                    'outstanding_balance' => 'Outstanding',
                    'collection_rate' => 'Collection %',
                    'created_at' => 'Date Created',
                ];
            case 'manifest_performance':
                return [
                    'manifest_number' => 'Manifest #',
                    'manifest_type' => 'Type',
                    'office_name' => 'Office',
                    'package_count' => 'Packages',
                    'total_weight' => 'Weight (lbs)',
                    'total_volume' => 'Volume (ft³)',
                    'processing_time' => 'Processing Time',
                    'efficiency_score' => 'Efficiency',
                    'status' => 'Status',
                    'created_at' => 'Date',
                ];
            case 'customer_analytics':
                return [
                    'customer_name' => 'Customer',
                    'email' => 'Email',
                    'total_packages' => 'Total Packages',
                    'account_balance' => 'Balance',
                    'total_spent' => 'Total Spent',
                    'last_activity' => 'Last Activity',
                    'status' => 'Status',
                ];
            default:
                return [];
        }
    }

    public function updateFilters(array $filters)
    {
        $this->filters = $filters;
        $this->resetPage();
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

    public function toggleColumnSelector()
    {
        $this->showColumnSelector = !$this->showColumnSelector;
    }

    public function toggleColumn(string $column)
    {
        if (in_array($column, $this->selectedColumns)) {
            $this->selectedColumns = array_diff($this->selectedColumns, [$column]);
        } else {
            $this->selectedColumns[] = $column;
        }
    }

    public function selectAllRows()
    {
        $this->selectAll = !$this->selectAll;
        if ($this->selectAll) {
            $this->selectedRows = $this->getTableData()->pluck('id')->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function toggleRowSelection($rowId)
    {
        if (in_array($rowId, $this->selectedRows)) {
            $this->selectedRows = array_diff($this->selectedRows, [$rowId]);
        } else {
            $this->selectedRows[] = $rowId;
        }
        
        $this->selectAll = count($this->selectedRows) === $this->getTableData()->count();
    }

    public function exportSelected(string $format = 'csv')
    {
        if (empty($this->selectedRows)) {
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'Please select rows to export.'
            ]);
            return;
        }

        $exportService = app(ReportExportService::class);
        $data = $this->getTableData()->whereIn('id', $this->selectedRows)->toArray();
        
        try {
            $jobId = $exportService->queueExport($format, [
                'type' => $this->reportType,
                'data' => $data,
                'columns' => $this->selectedColumns,
                'filters' => $this->filters,
            ], auth()->user());

            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'success',
                'message' => 'Export started. You will be notified when complete.'
            ]);

            $this->emit('exportStarted', $jobId);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'Export failed: ' . $e->getMessage()
            ]);
        }
    }

    public function exportAll(string $format = 'csv')
    {
        $exportService = app(ReportExportService::class);
        $data = $this->getTableData()->toArray();
        
        try {
            $jobId = $exportService->queueExport($format, [
                'type' => $this->reportType,
                'data' => $data,
                'columns' => $this->selectedColumns,
                'filters' => $this->filters,
            ], auth()->user());

            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'success',
                'message' => 'Export started. You will be notified when complete.'
            ]);

            $this->emit('exportStarted', $jobId);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'Export failed: ' . $e->getMessage()
            ]);
        }
    }

    public function showManifestDetails(int $manifestId)
    {
        // Find the manifest data from the current table data
        $manifestData = collect($this->getTableData())->firstWhere('id', $manifestId);
        
        if ($manifestData) {
            $this->emit('showManifestDetails', $manifestId, $manifestData);
        } else {
            $this->dispatchBrowserEvent('show-notification', [
                'type' => 'error',
                'message' => 'Manifest details not found.'
            ]);
        }
    }

    public function showCustomerDetails(int $customerId)
    {
        $this->emit('showCustomerDetails', $customerId);
    }

    public function getTableData()
    {
        $collection = collect($this->data);
        
        // Sanitize numeric fields
        $numericFields = [
            'total_owed', 'total_collected', 'outstanding_balance', 'account_balance', 
            'total_spent', 'collection_rate', 'total_weight', 'total_volume', 
            'efficiency_score', 'total_packages', 'package_count'
        ];
        
        $collection = $collection->map(function ($item) use ($numericFields) {
            foreach ($numericFields as $field) {
                if (isset($item[$field])) {
                    $item[$field] = is_numeric($item[$field]) ? (float)$item[$field] : 0;
                }
            }
            return $item;
        });
        
        // Apply search filter
        if ($this->search) {
            $collection = $collection->filter(function ($item) {
                $searchTerm = strtolower($this->search);
                foreach ($item as $value) {
                    if (is_string($value) && str_contains(strtolower($value), $searchTerm)) {
                        return true;
                    }
                }
                return false;
            });
        }
        
        // Apply sorting
        if ($this->sortField) {
            $collection = $collection->sortBy($this->sortField, SORT_REGULAR, $this->sortDirection === 'desc');
        }
        
        return $collection->values();
    }

    public function render()
    {
        $data = $this->getTableData();
        
        // Paginate the data
        $currentPage = $this->page;
        $perPage = $this->perPage;
        $total = $data->count();
        $items = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $paginatedData = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return view('livewire.reports.report-data-table', [
            'paginatedData' => $paginatedData,
            'totalRecords' => $total,
        ]);
    }
}