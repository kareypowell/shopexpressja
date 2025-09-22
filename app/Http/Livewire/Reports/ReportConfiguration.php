<?php

namespace App\Http\Livewire\Reports;

use App\Models\SavedReportFilter;
use App\Models\ReportTemplate;
use App\Services\ReportConfigurationService;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;

class ReportConfiguration extends Component
{
    public $reportType = 'sales';
    public $userFilters = [];
    public $sharedFilters = [];
    public $templates = [];
    
    // Filter management
    public $showFilterModal = false;
    public $editingFilter = null;
    public $filterName = '';
    public $filterConfig = [];
    public $isShared = false;
    public $sharedWithRoles = [];
    
    // Template management
    public $showTemplateModal = false;
    public $selectedTemplate = null;

    protected ReportConfigurationService $configService;

    public function boot(ReportConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    public function mount()
    {
        Gate::authorize('viewReports');
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.reports.report-configuration');
    }

    public function updatedReportType()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->userFilters = $this->configService->getUserSavedFilters(auth()->user(), $this->reportType);
        $this->sharedFilters = $this->configService->getSharedFilters($this->reportType, auth()->user());
        
        $this->templates = ReportTemplate::where('type', $this->reportType)
                                       ->where('is_active', true)
                                       ->orderBy('name')
                                       ->get()
                                       ->toArray();
    }

    public function createFilter()
    {
        Gate::authorize('manageSavedFilters');
        
        $this->resetFilterForm();
        $this->showFilterModal = true;
    }

    public function editFilter($filterId)
    {
        Gate::authorize('manageSavedFilters');
        
        $filter = SavedReportFilter::findOrFail($filterId);
        
        // Check if user owns this filter
        if ($filter->user_id !== auth()->id()) {
            $this->addError('filter', 'You can only edit your own filters.');
            return;
        }

        $this->editingFilter = $filter;
        $this->filterName = $filter->name;
        $this->filterConfig = $filter->filter_config;
        $this->isShared = $filter->is_shared;
        $this->sharedWithRoles = $filter->shared_with_roles ?? [];
        $this->showFilterModal = true;
    }

    public function saveFilter()
    {
        $this->validate([
            'filterName' => 'required|string|max:255',
            'filterConfig' => 'required|array',
        ]);

        try {
            $data = [
                'name' => $this->filterName,
                'report_type' => $this->reportType,
                'filter_config' => $this->filterConfig,
                'is_shared' => $this->isShared,
                'shared_with_roles' => $this->sharedWithRoles
            ];

            if ($this->editingFilter) {
                $this->configService->updateSavedFilter($this->editingFilter, $data);
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'success',
                    'message' => 'Filter updated successfully!'
                ]);
            } else {
                $this->configService->createSavedFilter(auth()->user(), $data);
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'success',
                    'message' => 'Filter created successfully!'
                ]);
            }

            $this->closeFilterModal();
            $this->loadData();

        } catch (\Exception $e) {
            $this->addError('filter', 'Failed to save filter: ' . $e->getMessage());
        }
    }

    public function deleteFilter($filterId)
    {
        Gate::authorize('manageSavedFilters');
        
        try {
            $filter = SavedReportFilter::findOrFail($filterId);
            
            // Check if user owns this filter
            if ($filter->user_id !== auth()->id()) {
                $this->addError('filter', 'You can only delete your own filters.');
                return;
            }

            $this->configService->deleteSavedFilter($filter);
            $this->loadData();
            
            $this->dispatchBrowserEvent('notify', [
                'type' => 'success',
                'message' => 'Filter deleted successfully!'
            ]);

        } catch (\Exception $e) {
            $this->addError('filter', 'Failed to delete filter: ' . $e->getMessage());
        }
    }

    public function duplicateFilter($filterId)
    {
        Gate::authorize('manageSavedFilters');
        
        try {
            $filter = SavedReportFilter::findOrFail($filterId);
            
            // Check if user can access this filter
            if ($filter->user_id !== auth()->id() && !$filter->is_shared) {
                $this->addError('filter', 'Filter not found or access denied.');
                return;
            }

            $this->configService->duplicateFilter($filter, auth()->user());
            $this->loadData();
            
            $this->dispatchBrowserEvent('notify', [
                'type' => 'success',
                'message' => 'Filter duplicated successfully!'
            ]);

        } catch (\Exception $e) {
            $this->addError('filter', 'Failed to duplicate filter: ' . $e->getMessage());
        }
    }

    public function applyTemplate($templateId)
    {
        try {
            $template = ReportTemplate::findOrFail($templateId);
            $defaultFilters = $this->configService->applyTemplateDefaults($template);
            
            // Emit event to parent component to apply these filters
            $this->emit('filtersApplied', $defaultFilters);
            
            $this->dispatchBrowserEvent('notify', [
                'type' => 'success',
                'message' => "Template '{$template->name}' applied successfully!"
            ]);

        } catch (\Exception $e) {
            $this->addError('template', 'Failed to apply template: ' . $e->getMessage());
        }
    }

    public function closeFilterModal()
    {
        $this->showFilterModal = false;
        $this->resetFilterForm();
    }

    public function closeTemplateModal()
    {
        $this->showTemplateModal = false;
        $this->selectedTemplate = null;
    }

    protected function resetFilterForm()
    {
        $this->editingFilter = null;
        $this->filterName = '';
        $this->filterConfig = [];
        $this->isShared = false;
        $this->sharedWithRoles = [];
        $this->resetErrorBag();
    }

    public function addFilterConfig($key, $value)
    {
        $this->filterConfig[$key] = $value;
    }

    public function removeFilterConfig($key)
    {
        unset($this->filterConfig[$key]);
    }

    public function toggleSharedRole($role)
    {
        if (in_array($role, $this->sharedWithRoles)) {
            $this->sharedWithRoles = array_diff($this->sharedWithRoles, [$role]);
        } else {
            $this->sharedWithRoles[] = $role;
        }
    }
}