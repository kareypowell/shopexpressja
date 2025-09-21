<?php

namespace App\Http\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditExportService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AuditLogManagement extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search = '';
    public $eventType = '';
    public $action = '';
    public $userId = '';
    public $auditableType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $ipAddress = '';
    public $perPage = 25;
    
    // Advanced search options
    public $searchInOldValues = false;
    public $searchInNewValues = false;
    public $searchInAdditionalData = false;
    public $searchInUrl = false;
    public $searchInUserAgent = false;
    
    // Sorting
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Quick filters
    public $quickFilter = '';
    
    // Filter presets
    public $filterPreset = '';
    
    // UI state
    public $showFilters = false;
    
    // Export options
    public $exportFormat = 'csv';
    public $exportTemplate = '';
    public $showExportModal = false;
    
    // Download link
    public $downloadLink = '';
    public $downloadFilename = '';
    public $downloadType = '';
    public $showDownloadLink = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'eventType' => ['except' => ''],
        'action' => ['except' => ''],
        'userId' => ['except' => ''],
        'auditableType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'ipAddress' => ['except' => ''],
        'searchInOldValues' => ['except' => false],
        'searchInNewValues' => ['except' => false],
        'searchInAdditionalData' => ['except' => false],
        'searchInUrl' => ['except' => false],
        'searchInUserAgent' => ['except' => false],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'quickFilter' => ['except' => ''],
        'filterPreset' => ['except' => ''],
        'exportFormat' => ['except' => 'csv'],
        'downloadLink' => ['except' => ''],
        'downloadFilename' => ['except' => ''],
        'downloadType' => ['except' => ''],
        'showDownloadLink' => ['except' => false],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        // Check authorization
        $this->authorize('viewAny', AuditLog::class);
        
        // Set default date range to last 30 days if not already set
        if (empty($this->dateTo)) {
            $this->dateTo = Carbon::now()->format('Y-m-d');
        }
        if (empty($this->dateFrom)) {
            $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        }
        
        // Show filters panel if any filters are active
        $this->showFilters = $this->hasActiveFilters();
    }
    
    protected function hasActiveFilters()
    {
        return !empty($this->eventType) || !empty($this->action) || !empty($this->userId) || 
               !empty($this->auditableType) || !empty($this->ipAddress) ||
               $this->searchInOldValues || $this->searchInNewValues || $this->searchInAdditionalData ||
               $this->searchInUrl || $this->searchInUserAgent;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingEventType()
    {
        $this->resetPage();
    }

    public function updatingAction()
    {
        $this->resetPage();
    }

    public function updatingUserId()
    {
        $this->resetPage();
    }

    public function updatingAuditableType()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingIpAddress()
    {
        $this->resetPage();
    }

    public function updatingQuickFilter()
    {
        $this->resetPage();
    }

    public function updatingFilterPreset()
    {
        $this->applyFilterPreset();
        $this->resetPage();
    }

    public function updatingSearchInOldValues()
    {
        $this->resetPage();
    }

    public function updatingSearchInNewValues()
    {
        $this->resetPage();
    }

    public function updatingSearchInAdditionalData()
    {
        $this->resetPage();
    }

    public function updatingSearchInUrl()
    {
        $this->resetPage();
    }

    public function updatingSearchInUserAgent()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->eventType = '';
        $this->action = '';
        $this->userId = '';
        $this->auditableType = '';
        $this->ipAddress = '';
        $this->searchInOldValues = false;
        $this->searchInNewValues = false;
        $this->searchInAdditionalData = false;
        $this->searchInUrl = false;
        $this->searchInUserAgent = false;
        $this->quickFilter = '';
        $this->filterPreset = '';
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function applyQuickFilter($filter)
    {
        $this->quickFilter = $filter;
        
        switch ($filter) {
            case 'today':
                $this->dateFrom = Carbon::now()->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->dateFrom = Carbon::yesterday()->format('Y-m-d');
                $this->dateTo = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'last_7_days':
                $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'last_30_days':
                $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'this_month':
                $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'last_month':
                $this->dateFrom = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dateTo = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
        }
        
        $this->resetPage();
    }

    public function applyFilterPreset()
    {
        switch ($this->filterPreset) {
            case 'security_events':
                $this->eventType = 'security_event';
                $this->action = '';
                break;
            case 'authentication':
                $this->eventType = 'authentication';
                $this->action = '';
                break;
            case 'failed_logins':
                $this->eventType = 'authentication';
                $this->action = 'failed_login';
                break;
            case 'model_changes':
                $this->eventType = '';
                $this->action = '';
                // Filter for model events
                break;
            case 'financial_transactions':
                $this->eventType = 'financial_transaction';
                $this->action = '';
                break;
            case 'business_actions':
                $this->eventType = 'business_action';
                $this->action = '';
                break;
            case 'admin_actions':
                // Filter for admin user actions
                $adminUsers = User::whereHas('role', function($q) {
                    $q->whereIn('name', ['admin', 'superadmin']);
                })->pluck('id');
                if ($adminUsers->isNotEmpty()) {
                    $this->userId = $adminUsers->first();
                }
                break;
        }
    }

    public function getAuditLogsProperty()
    {
        return $this->getFilteredAuditLogs(true);
    }

    public function getEventTypesProperty()
    {
        return AuditLog::select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type')
            ->filter()
            ->values();
    }

    public function getActionsProperty()
    {
        return AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->filter()
            ->values();
    }

    public function getAuditableTypesProperty()
    {
        return AuditLog::select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->filter()
            ->values();
    }

    public function getUsersProperty()
    {
        return User::select('id', 'first_name', 'last_name', 'email')
            ->whereIn('id', AuditLog::select('user_id')->distinct()->whereNotNull('user_id'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function getFilterPresetsProperty()
    {
        return [
            'security_events' => 'Security Events',
            'authentication' => 'Authentication Events',
            'failed_logins' => 'Failed Login Attempts',
            'model_changes' => 'Model Changes',
            'financial_transactions' => 'Financial Transactions',
            'business_actions' => 'Business Actions',
            'admin_actions' => 'Admin Actions',
        ];
    }

    public function getQuickFiltersProperty()
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
        ];
    }

    public function showExportModal()
    {
        $this->showExportModal = true;
    }

    public function hideExportModal()
    {
        $this->showExportModal = false;
    }

    public function showDownloadLink($data)
    {
        $this->downloadLink = $data['url'];
        $this->downloadFilename = $data['filename'];
        $this->downloadType = $data['type'];
        $this->showDownloadLink = true;
    }

    public function hideDownloadLink()
    {
        $this->downloadLink = '';
        $this->downloadFilename = '';
        $this->downloadType = '';
        $this->showDownloadLink = false;
    }

    protected $listeners = [
        'showDownloadLink' => 'showDownloadLink',
    ];

    public function exportAuditLogs()
    {
        try {
            // Check authorization
            $this->authorize('export', AuditLog::class);

            $exportService = new AuditExportService();
            
            // Get all filtered audit logs (without pagination)
            $auditLogs = $this->getFilteredAuditLogs(false);
            
            // Prepare filters for context
            $filters = [
                'search' => $this->search,
                'event_type' => $this->eventType,
                'action' => $this->action,
                'user_id' => $this->userId,
                'auditable_type' => $this->auditableType,
                'ip_address' => $this->ipAddress,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ];

            if ($this->exportFormat === 'csv') {
                $content = $exportService->exportToCsv($auditLogs, $filters);
                $filename = 'audit_logs_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
                
                // Save CSV to storage
                Storage::disk('public')->put('exports/' . $filename, $content);
                $downloadUrl = route('admin.audit-logs.download', ['filename' => $filename]);
                
                $this->emit('showDownloadLink', [
                    'url' => $downloadUrl,
                    'filename' => $filename,
                    'type' => 'CSV Export'
                ]);
                
            } elseif ($this->exportFormat === 'pdf') {
                $options = [
                    'title' => 'Audit Log Report - ' . Carbon::now()->format('F j, Y'),
                ];
                
                $filePath = $exportService->exportToPdf($auditLogs, $filters, $options);
                $downloadUrl = route('admin.audit-logs.download', ['filename' => basename($filePath)]);
                
                $this->emit('showDownloadLink', [
                    'url' => $downloadUrl,
                    'filename' => basename($filePath),
                    'type' => 'PDF Report'
                ]);
            }

            $this->hideExportModal();
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Export generated successfully! Download link provided below.'
            ]);

        } catch (\Exception $e) {
            $this->addError('export', 'Failed to export audit logs: ' . $e->getMessage());
        }
    }

    public function generateComplianceReport()
    {
        try {
            // Check authorization
            $this->authorize('generateComplianceReport', AuditLog::class);

            $exportService = new AuditExportService();
            
            // Prepare filters for context
            $filters = [
                'search' => $this->search,
                'event_type' => $this->eventType,
                'action' => $this->action,
                'user_id' => $this->userId,
                'auditable_type' => $this->auditableType,
                'ip_address' => $this->ipAddress,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ];

            $reportData = $exportService->generateComplianceReport($filters);
            
            $options = [
                'title' => 'Compliance Audit Report - ' . Carbon::now()->format('F j, Y'),
            ];
            
            $filePath = $exportService->exportToPdf($reportData['audit_logs'], $filters, $options);
            $downloadUrl = route('admin.audit-logs.download', ['filename' => basename($filePath)]);
            
            $this->emit('showDownloadLink', [
                'url' => $downloadUrl,
                'filename' => basename($filePath),
                'type' => 'Compliance Report'
            ]);

            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Compliance report generated successfully! Download link provided below.'
            ]);

        } catch (\Exception $e) {
            $this->addError('export', 'Failed to generate compliance report: ' . $e->getMessage());
        }
    }

    protected function getFilteredAuditLogs($paginate = true)
    {
        $query = AuditLog::with(['user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    // Basic field search
                    $q->where('action', 'like', '%' . $this->search . '%')
                      ->orWhere('event_type', 'like', '%' . $this->search . '%')
                      ->orWhere('auditable_type', 'like', '%' . $this->search . '%')
                      ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                      ->orWhere('auditable_id', 'like', '%' . $this->search . '%');
                    
                    // User search
                    $q->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                    
                    // JSON field searches (if enabled)
                    if ($this->searchInOldValues) {
                        $q->orWhereRaw("JSON_SEARCH(old_values, 'all', ?) IS NOT NULL", ['%' . $this->search . '%']);
                    }
                    
                    if ($this->searchInNewValues) {
                        $q->orWhereRaw("JSON_SEARCH(new_values, 'all', ?) IS NOT NULL", ['%' . $this->search . '%']);
                    }
                    
                    if ($this->searchInAdditionalData) {
                        $q->orWhereRaw("JSON_SEARCH(additional_data, 'all', ?) IS NOT NULL", ['%' . $this->search . '%']);
                    }
                    
                    if ($this->searchInUrl) {
                        $q->orWhere('url', 'like', '%' . $this->search . '%');
                    }
                    
                    if ($this->searchInUserAgent) {
                        $q->orWhere('user_agent', 'like', '%' . $this->search . '%');
                    }
                });
            })
            ->when($this->eventType, function ($query) {
                $query->where('event_type', $this->eventType);
            })
            ->when($this->action, function ($query) {
                $query->where('action', $this->action);
            })
            ->when($this->userId, function ($query) {
                $query->where('user_id', $this->userId);
            })
            ->when($this->auditableType, function ($query) {
                $query->where('auditable_type', $this->auditableType);
            })
            ->when($this->ipAddress, function ($query) {
                $query->where('ip_address', 'like', '%' . $this->ipAddress . '%');
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('audit_logs.created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('audit_logs.created_at', '<=', $this->dateTo);
            });

        // Apply sorting
        $sortableFields = ['created_at', 'event_type', 'action', 'user_id', 'auditable_type', 'ip_address'];
        if (in_array($this->sortField, $sortableFields)) {
            if ($this->sortField === 'user_id') {
                $query->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
                      ->orderBy('users.first_name', $this->sortDirection)
                      ->orderBy('users.last_name', $this->sortDirection)
                      ->select('audit_logs.*');
            } else {
                $query->orderBy('audit_logs.' . $this->sortField, $this->sortDirection);
            }
        } else {
            $query->orderBy('audit_logs.created_at', 'desc');
        }

        return $paginate ? $query->paginate($this->perPage) : $query->get();
    }

    public function render()
    {
        return view('livewire.admin.audit-log-management', [
            'auditLogs' => $this->auditLogs,
            'eventTypes' => $this->eventTypes,
            'actions' => $this->actions,
            'auditableTypes' => $this->auditableTypes,
            'users' => $this->users,
            'filterPresets' => $this->filterPresets,
            'quickFilters' => $this->quickFilters,
        ]);
    }
}