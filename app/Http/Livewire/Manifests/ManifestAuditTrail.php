<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Manifest;
use App\Models\ManifestAudit;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ManifestAuditTrail extends Component
{
    use WithPagination;

    public Manifest $manifest;
    public string $search = '';
    public string $actionFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $userFilter = '';
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'userFilter' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        // Set default date range to last 30 days if not specified
        if (empty($this->dateFrom)) {
            $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        }
        if (empty($this->dateTo)) {
            $this->dateTo = Carbon::now()->format('Y-m-d');
        }
    }

    public function render()
    {
        $audits = $this->getFilteredAudits();
        $actions = $this->getAvailableActions();
        $users = $this->getAvailableUsers();

        return view('livewire.manifests.manifest-audit-trail', [
            'audits' => $audits,
            'actions' => $actions,
            'users' => $users,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActionFilter()
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

    public function updatingUserFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->actionFilter = '';
        $this->userFilter = '';
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->resetPage();
    }

    public function exportAuditTrail()
    {
        // Check authorization
        if (!auth()->user()->can('viewAudit', $this->manifest)) {
            $this->addError('export', 'You do not have permission to export audit data.');
            return;
        }

        // Get all filtered audits (without pagination)
        $audits = $this->getFilteredAudits(false);

        // Generate CSV content
        $csvContent = $this->generateCsvContent($audits);

        // Trigger download
        $filename = "manifest_{$this->manifest->id}_audit_trail_" . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        
        $this->emit('downloadFile', [
            'content' => $csvContent,
            'filename' => $filename,
            'mimeType' => 'text/csv'
        ]);

        $this->dispatchBrowserEvent('toastr:success', [
            'message' => 'Audit trail exported successfully.'
        ]);
    }

    private function getFilteredAudits($paginate = true)
    {
        $query = ManifestAudit::where('manifest_id', $this->manifest->id)
            ->with('user')
            ->orderBy('performed_at', 'desc');

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                               ->orWhere('last_name', 'like', '%' . $this->search . '%')
                               ->orWhere('email', 'like', '%' . $this->search . '%')
                               ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $this->search . '%']);
                  });
            });
        }

        // Apply action filter
        if (!empty($this->actionFilter)) {
            $query->where('action', $this->actionFilter);
        }

        // Apply user filter
        if (!empty($this->userFilter)) {
            $query->where('user_id', $this->userFilter);
        }

        // Apply date range filter
        if (!empty($this->dateFrom)) {
            $query->whereDate('performed_at', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('performed_at', '<=', $this->dateTo);
        }

        return $paginate ? $query->paginate($this->perPage) : $query->get();
    }

    private function getAvailableActions()
    {
        return ManifestAudit::where('manifest_id', $this->manifest->id)
            ->distinct()
            ->pluck('action')
            ->map(function ($action) {
                return [
                    'value' => $action,
                    'label' => $this->getActionLabel($action)
                ];
            })
            ->sortBy('label');
    }

    private function getAvailableUsers()
    {
        return ManifestAudit::where('manifest_id', $this->manifest->id)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter() // Remove null users
            ->unique('id')
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'label' => $user->name . ' (' . $user->email . ')'
                ];
            })
            ->sortBy('label');
    }

    private function getActionLabel($action)
    {
        return match($action) {
            'closed' => 'Closed',
            'unlocked' => 'Unlocked',
            'auto_complete' => 'Auto-closed (All Delivered)',
            default => ucfirst($action)
        };
    }

    private function generateCsvContent($audits)
    {
        $headers = [
            'Date/Time',
            'Action',
            'User',
            'User Email',
            'Reason',
            'Manifest ID'
        ];

        $rows = [];
        $rows[] = $headers;

        foreach ($audits as $audit) {
            $rows[] = [
                $audit->performed_at->format('Y-m-d H:i:s'),
                $this->getActionLabel($audit->action),
                $audit->user->name ?? 'System',
                $audit->user->email ?? 'N/A',
                $audit->reason,
                $audit->manifest_id
            ];
        }

        // Convert to CSV format
        $csvContent = '';
        foreach ($rows as $row) {
            $escapedRow = array_map(function($field) {
                return str_replace('"', '""', $field);
            }, $row);
            $csvContent .= '"' . implode('","', $escapedRow) . '"' . "\n";
        }

        return $csvContent;
    }
}