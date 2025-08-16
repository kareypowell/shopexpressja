<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ConsolidatedPackage;
use App\Models\ConsolidationHistory;
use App\Services\PackageConsolidationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConsolidationHistory extends Component
{
    use WithPagination, AuthorizesRequests;

    public $consolidatedPackage;
    public $showModal = false;
    public $filterAction = '';
    public $filterDays = '';
    public $exportFormat = 'json';
    public $showExportModal = false;

    protected $paginationTheme = 'bootstrap';

    public function mount(ConsolidatedPackage $consolidatedPackage = null)
    {
        $this->consolidatedPackage = $consolidatedPackage;
    }

    public function showHistory(ConsolidatedPackage $consolidatedPackage)
    {
        $this->authorize('view', $consolidatedPackage);
        $this->consolidatedPackage = $consolidatedPackage;
        $this->showModal = true;
        $this->resetPage();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->consolidatedPackage = null;
        $this->resetFilters();
    }

    public function resetFilters()
    {
        $this->filterAction = '';
        $this->filterDays = '';
        $this->resetPage();
    }

    public function showExportModal()
    {
        $this->showExportModal = true;
    }

    public function closeExportModal()
    {
        $this->showExportModal = false;
    }

    public function exportAuditTrail()
    {
        if (!$this->consolidatedPackage) {
            $this->addError('export', 'No consolidated package selected for export.');
            return;
        }

        $this->authorize('view', $this->consolidatedPackage);

        try {
            $consolidationService = app(PackageConsolidationService::class);
            $exportData = $consolidationService->exportConsolidationAuditTrail(
                $this->consolidatedPackage,
                $this->exportFormat
            );

            $filename = 'consolidation_audit_' . 
                       $this->consolidatedPackage->consolidated_tracking_number . 
                       '_' . now()->format('Y-m-d_H-i-s');

            switch ($this->exportFormat) {
                case 'json':
                    return response()->streamDownload(function () use ($exportData) {
                        echo $exportData;
                    }, $filename . '.json', [
                        'Content-Type' => 'application/json',
                    ]);

                case 'csv':
                    return response()->streamDownload(function () use ($exportData) {
                        echo $exportData;
                    }, $filename . '.csv', [
                        'Content-Type' => 'text/csv',
                    ]);

                default:
                    $this->addError('export', 'Invalid export format selected.');
                    return;
            }

        } catch (\Exception $e) {
            $this->addError('export', 'Failed to export audit trail: ' . $e->getMessage());
        }
    }

    public function getHistoryProperty()
    {
        if (!$this->consolidatedPackage) {
            return collect();
        }

        $query = $this->consolidatedPackage->history()
            ->with('performedBy')
            ->orderBy('performed_at', 'desc');

        // Apply filters
        if ($this->filterAction) {
            $query->byAction($this->filterAction);
        }

        if ($this->filterDays) {
            $query->recent((int) $this->filterDays);
        }

        return $query->paginate(10);
    }

    public function getHistorySummaryProperty()
    {
        if (!$this->consolidatedPackage) {
            return [];
        }

        $consolidationService = app(PackageConsolidationService::class);
        return $consolidationService->getConsolidationHistorySummary($this->consolidatedPackage);
    }

    public function getAvailableActionsProperty()
    {
        return [
            '' => 'All Actions',
            'consolidated' => 'Consolidated',
            'unconsolidated' => 'Unconsolidated',
            'status_changed' => 'Status Changed',
        ];
    }

    public function getAvailableDaysProperty()
    {
        return [
            '' => 'All Time',
            '7' => 'Last 7 Days',
            '30' => 'Last 30 Days',
            '90' => 'Last 90 Days',
        ];
    }

    public function render()
    {
        return view('livewire.consolidation-history', [
            'history' => $this->history,
            'historySummary' => $this->historySummary,
            'availableActions' => $this->availableActions,
            'availableDays' => $this->availableDays,
        ]);
    }
}