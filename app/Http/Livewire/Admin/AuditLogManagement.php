<?php

namespace App\Http\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class AuditLogManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $eventType = '';
    public $action = '';
    public $userId = '';
    public $auditableType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'eventType' => ['except' => ''],
        'action' => ['except' => ''],
        'userId' => ['except' => ''],
        'auditableType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        // Set default date range to last 30 days
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
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

    public function clearFilters()
    {
        $this->search = '';
        $this->eventType = '';
        $this->action = '';
        $this->userId = '';
        $this->auditableType = '';
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->resetPage();
    }

    public function getAuditLogsProperty()
    {
        $query = AuditLog::with(['user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%' . $this->search . '%')
                      ->orWhere('event_type', 'like', '%' . $this->search . '%')
                      ->orWhere('auditable_type', 'like', '%' . $this->search . '%')
                      ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function ($userQuery) {
                          $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                                   ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                   ->orWhere('email', 'like', '%' . $this->search . '%');
                      });
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
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate($this->perPage);
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

    public function render()
    {
        return view('livewire.admin.audit-log-management', [
            'auditLogs' => $this->auditLogs,
            'eventTypes' => $this->eventTypes,
            'actions' => $this->actions,
            'auditableTypes' => $this->auditableTypes,
            'users' => $this->users,
        ]);
    }
}