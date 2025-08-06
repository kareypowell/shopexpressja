<?php

namespace App\Http\Livewire\Customers;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminCustomersTable extends DataTableComponent
{
    use AuthorizesRequests;
    
    // public $refresh = 'visible';

    public array $bulkActions = [
        'exportCustomers' => 'Export XLXS',
        'bulkDelete' => 'Delete Selected',
        'bulkRestore' => 'Restore Selected',
    ];

    public array $filterNames = [
        'parish' => 'Parish',
        'status' => 'Status',
        'registration_date' => 'Registration Date',
    ];

    public $showDeleteModal = false;
    public $customerToDelete = null;
    public $showRestoreModal = false;
    public $customerToRestore = null;
    
    // Enhanced search properties
    public $searchHighlight = '';
    public $advancedFilters = false;
    public $advancedSearchCriteria = [
        'name' => '',
        'email' => '',
        'account_number' => '',
        'tax_number' => '',
        'telephone_number' => '',
        'parish' => '',
        'address' => '',
        'registration_date_from' => '',
        'registration_date_to' => '',
        'status' => 'active',
    ];
    public $searchPerformanceMode = false;
    
    // Loading states
    public $isLoading = false;
    public $loadingMessage = '';
    public $bulkActionInProgress = false;
    
    // URL state management
    protected $queryString = [
        'advancedFilters' => ['except' => false],
        'advancedSearchCriteria' => ['except' => [
            'name' => '',
            'email' => '',
            'account_number' => '',
            'tax_number' => '',
            'telephone_number' => '',
            'parish' => '',
            'address' => '',
            'registration_date_from' => '',
            'registration_date_to' => '',
            'status' => 'active',
        ]],
        'searchPerformanceMode' => ['except' => false],
    ];

    public function filters(): array
    {
        return [
            'parish' => Filter::make('Parish')
                ->select([
                    '' => 'Any',
                    'Clarendon' => 'Clarendon',
                    'Hanover' => 'Hanover',
                    'Kingston' => 'Kingston',
                    'Manchester' => 'Manchester',
                    'Portland' => 'Portland',
                    'St. Andrew' => 'St. Andrew',
                    'St. Ann' => 'St. Ann',
                    'St. Catherine' => 'St. Catherine',
                    'St. Elizabeth' => 'St. Elizabeth',
                    'St. James' => 'St. James',
                    'St. Mary' => 'St. Mary',
                    'St. Thomas' => 'St. Thomas',
                    'Trelawny' => 'Trelawny',
                    'Westmoreland' => 'Westmoreland',
                ]),
            'status' => Filter::make('Status')
                ->select([
                    'active' => 'Active',
                    'deleted' => 'Deleted',
                    'all' => 'All',
                ]),
            'registration_date' => Filter::make('Registration Date')
                ->date([
                    'min' => now()->subYears(5)->format('Y-m-d'),
                    'max' => now()->format('Y-m-d'),
                ]),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("First name", "first_name")
                ->sortable()
                ->searchable(),
            Column::make("Last name", "last_name")
                ->sortable()
                ->searchable(),
            Column::make("Email", "email")
                ->sortable()
                ->searchable(),
            Column::make("Telephone No.", "profile.telephone_number")
                ->sortable()
                ->searchable(),
            Column::make("Account No.", "profile.account_number")
                ->sortable()
                ->searchable(),
            Column::make("TRN", "profile.tax_number")
                ->sortable()
                ->searchable(),
            Column::make("Parish", "profile.parish")
                ->sortable(),
            Column::make("Status", "deleted_at")
                ->sortable(),
            Column::make("Created On", "created_at")
                ->sortable(),
            Column::make("Actions", ""),
        ];
    }

    public function query(): Builder
    {
        // Check if user can view customers
        $this->authorize('customer.viewAny');
        
        $status = $this->getFilter('status') ?: 'active';
        
        $query = User::query()
            ->byStatus($status)
            ->forCustomerTable()
            ->orderBy('id', 'desc');

        // Enhanced search functionality
        if ($search = $this->getFilter('search')) {
            $this->searchHighlight = $search;
            
            // Use performance mode for large datasets
            if ($this->searchPerformanceMode) {
                $query->search($search);
            } else {
                $query->search($search);
            }
        }

        // Advanced search functionality
        if ($this->advancedFilters && $this->hasAdvancedSearchCriteria()) {
            $query->advancedSearch($this->advancedSearchCriteria);
            $this->searchHighlight = $this->getAdvancedSearchHighlight();
        }

        return $query
            ->when($this->getFilter('parish'), fn($query, $parish) =>
            $query->whereHas('profile', fn($q) => $q->where('parish', $parish)))
            ->when($this->getFilter('registration_date'), fn($query, $date) => 
            $query->whereDate('created_at', $date));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.admin-customers-table';
    }

    /**
     * Navigate to customer profile view
     */
    public function viewCustomer($customerId)
    {
        $customer = User::findOrFail($customerId);
        $this->authorize('customer.view', $customer);
        
        return redirect()->route('admin.customers.show', $customerId);
    }

    /**
     * Navigate to customer edit form
     */
    public function editCustomer($customerId)
    {
        $customer = User::findOrFail($customerId);
        $this->authorize('customer.update', $customer);
        
        return redirect()->route('admin.customers.edit', $customerId);
    }

    /**
     * Navigate to customer creation form
     */
    public function createCustomer()
    {
        $this->authorize('customer.create');
        
        return redirect()->route('admin.customers.create');
    }

    /**
     * Toggle advanced filters display
     */
    public function toggleAdvancedFilters()
    {
        $this->advancedFilters = !$this->advancedFilters;
    }

    /**
     * Clear all filters and search
     */
    public function clearAllFilters()
    {
        $this->resetFilters();
        $this->searchHighlight = '';
    }

    /**
     * Highlight search terms in text
     */
    public function highlightSearchTerm($text, $term = null)
    {
        if (!$term) {
            $term = $this->searchHighlight;
        }
        
        if (!$term || !$text) {
            return $text;
        }

        // Handle multiple search terms
        $searchTerms = explode(' ', trim($term));
        $highlightedText = $text;
        
        foreach ($searchTerms as $searchTerm) {
            if (!empty($searchTerm)) {
                $highlightedText = preg_replace(
                    '/(' . preg_quote($searchTerm, '/') . ')/i', 
                    '<mark class="bg-yellow-200 px-1 rounded">$1</mark>', 
                    $highlightedText
                );
            }
        }
        
        return $highlightedText;
    }

    /**
     * Get search statistics for performance monitoring
     */
    public function getSearchStats(): array
    {
        $query = $this->query();
        $totalResults = $query->count();
        
        return [
            'total_results' => $totalResults,
            'search_term' => $this->searchHighlight,
            'advanced_filters_active' => $this->advancedFilters && $this->hasAdvancedSearchCriteria(),
            'performance_mode' => $this->searchPerformanceMode,
        ];
    }

    /**
     * Get search highlight term for templates
     */
    public function getSearchHighlightProperty()
    {
        return $this->searchHighlight;
    }

    /**
     * Check if any advanced search criteria are set
     */
    public function hasAdvancedSearchCriteria(): bool
    {
        foreach ($this->advancedSearchCriteria as $key => $value) {
            if ($key === 'status') continue; // Status is always set
            if (!empty($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get highlight term from advanced search criteria
     */
    public function getAdvancedSearchHighlight(): string
    {
        $highlights = [];
        
        foreach ($this->advancedSearchCriteria as $field => $value) {
            if (!empty($value) && $field !== 'status' && $field !== 'parish') {
                $highlights[] = $value;
            }
        }
        
        return implode(' ', $highlights);
    }

    /**
     * Apply advanced search
     */
    public function applyAdvancedSearch()
    {
        $this->resetPage();
        $this->emit('refreshComponent');
    }

    /**
     * Clear advanced search criteria
     */
    public function clearAdvancedSearch()
    {
        $this->advancedSearchCriteria = [
            'name' => '',
            'email' => '',
            'account_number' => '',
            'tax_number' => '',
            'telephone_number' => '',
            'parish' => '',
            'address' => '',
            'registration_date_from' => '',
            'registration_date_to' => '',
            'status' => 'active',
        ];
        $this->searchHighlight = '';
        $this->resetPage();
        $this->emit('refreshComponent');
    }

    /**
     * Toggle search performance mode
     */
    public function toggleSearchPerformanceMode()
    {
        $this->searchPerformanceMode = !$this->searchPerformanceMode;
        $this->emit('refreshComponent');
    }

    /**
     * Save current filter state to session
     */
    public function saveFilterState()
    {
        session()->put('admin_customers_filters', [
            'advancedFilters' => $this->advancedFilters,
            'advancedSearchCriteria' => $this->advancedSearchCriteria,
            'searchPerformanceMode' => $this->searchPerformanceMode,
        ]);
    }

    /**
     * Load filter state from session
     */
    public function loadFilterState()
    {
        $savedFilters = session()->get('admin_customers_filters', []);
        
        if (!empty($savedFilters)) {
            $this->advancedFilters = $savedFilters['advancedFilters'] ?? false;
            $this->advancedSearchCriteria = array_merge(
                $this->advancedSearchCriteria,
                $savedFilters['advancedSearchCriteria'] ?? []
            );
            $this->searchPerformanceMode = $savedFilters['searchPerformanceMode'] ?? false;
        }
    }

    /**
     * Clear saved filter state
     */
    public function clearSavedFilterState()
    {
        session()->forget('admin_customers_filters');
    }

    /**
     * Mount component with URL state and session persistence
     */
    public function mount()
    {
        // Load from session if no URL parameters are present
        if (!request()->hasAny(['advancedFilters', 'advancedSearchCriteria', 'searchPerformanceMode'])) {
            $this->loadFilterState();
        }
    }

    /**
     * Updated hook to save state when filters change
     */
    public function updated($propertyName)
    {
        // Save filter state when advanced search criteria change
        if (str_starts_with($propertyName, 'advancedSearchCriteria') || 
            $propertyName === 'advancedFilters' || 
            $propertyName === 'searchPerformanceMode') {
            $this->saveFilterState();
        }
    }

    /**
     * Get filter summary for display
     */
    public function getFilterSummary(): array
    {
        $activeFilters = [];
        
        foreach ($this->advancedSearchCriteria as $key => $value) {
            if (!empty($value) && $key !== 'status') {
                $label = ucwords(str_replace('_', ' ', $key));
                $activeFilters[] = "{$label}: {$value}";
            }
        }
        
        if ($this->advancedSearchCriteria['status'] !== 'active') {
            $activeFilters[] = "Status: " . ucfirst($this->advancedSearchCriteria['status']);
        }
        
        return $activeFilters;
    }

    /**
     * Export current search results
     */
    public function exportSearchResults()
    {
        $this->authorize('customer.export');
        
        $query = $this->query();
        $customers = $query->get();
        
        // This would typically generate a CSV or Excel file
        // For now, we'll just show a message with the count
        $this->dispatchBrowserEvent('show-alert', [
            'type' => 'info',
            'message' => "Export would include {$customers->count()} customers based on current filters."
        ]);
    }



    public function confirmDelete($customerId)
    {
        try {
            $this->reset(['showDeleteModal', 'customerToDelete']);
            
            $this->customerToDelete = User::withTrashed()->find($customerId);
            
            if (!$this->customerToDelete) {
                session()->flash('error', 'Customer not found.');
                return;
            }
            
            $this->authorize('customer.delete', $this->customerToDelete);
            
            $this->showDeleteModal = true;
            
            // Force component refresh
            $this->dispatchBrowserEvent('modal-opened', ['modal' => 'delete']);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error opening delete confirmation: ' . $e->getMessage());
        }
    }

    public function deleteCustomer()
    {
        try {
            if ($this->customerToDelete) {
                $this->authorize('customer.delete', $this->customerToDelete);
                
                if ($this->customerToDelete->canBeDeleted()) {
                    $this->customerToDelete->softDeleteCustomer();
                    session()->flash('message', 'Customer deleted successfully.');
                } else {
                    session()->flash('error', 'Customer cannot be deleted.');
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting customer: ' . $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->customerToDelete = null;
    }

    public function confirmRestore($customerId)
    {
        try {
            $this->customerToRestore = User::withTrashed()->find($customerId);
            
            if (!$this->customerToRestore) {
                session()->flash('error', 'Customer not found.');
                return;
            }
            
            $this->authorize('customer.restore', $this->customerToRestore);
            
            $this->showRestoreModal = true;
            
            // Force component refresh
            $this->dispatchBrowserEvent('modal-opened', ['modal' => 'restore']);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error opening restore confirmation: ' . $e->getMessage());
        }
    }

    public function restoreCustomer()
    {
        try {
            if ($this->customerToRestore) {
                $this->authorize('customer.restore', $this->customerToRestore);
                
                if ($this->customerToRestore->canBeRestored()) {
                    $this->customerToRestore->restoreCustomer();
                    session()->flash('message', 'Customer restored successfully.');
                } else {
                    session()->flash('error', 'Customer cannot be restored.');
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error restoring customer: ' . $e->getMessage());
        }

        $this->showRestoreModal = false;
        $this->customerToRestore = null;
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->customerToDelete = null;
    }

    public function cancelRestore()
    {
        $this->showRestoreModal = false;
        $this->customerToRestore = null;
    }

    public function bulkDelete()
    {
        $this->authorize('customer.bulkOperations');
        
        $this->bulkActionInProgress = true;
        $this->loadingMessage = 'Deleting selected customers...';
        
        try {
            // Get selected items using the correct property for Laravel Livewire Tables
            $selectedIds = $this->selectedKeys ?? [];
            
            if (empty($selectedIds)) {
                session()->flash('error', 'No customers selected for deletion.');
                return;
            }
            
            $customers = User::withTrashed()->whereIn('id', $selectedIds)->get();
            $deletedCount = 0;
            $errors = [];

            foreach ($customers as $customer) {
                try {
                    if (auth()->user()->can('customer.delete', $customer) && $customer->canBeDeleted()) {
                        $customer->softDeleteCustomer();
                        $deletedCount++;
                    } else {
                        $errors[] = "Cannot delete {$customer->full_name}: Insufficient permissions or customer cannot be deleted.";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error deleting {$customer->full_name}: " . $e->getMessage();
                }
            }

            if ($deletedCount > 0) {
                session()->flash('message', "Successfully deleted {$deletedCount} customer(s).");
            }

            if (!empty($errors)) {
                session()->flash('error', implode('<br>', $errors));
            }

            $this->clearSelected();
        } finally {
            $this->bulkActionInProgress = false;
            $this->loadingMessage = '';
        }
    }

    public function bulkRestore()
    {
        $this->authorize('customer.bulkOperations');
        
        $this->bulkActionInProgress = true;
        $this->loadingMessage = 'Restoring selected customers...';
        
        try {
            // Get selected items using the correct property for Laravel Livewire Tables
            $selectedIds = $this->selectedKeys ?? [];
            
            if (empty($selectedIds)) {
                session()->flash('error', 'No customers selected for restoration.');
                return;
            }
            
            $customers = User::withTrashed()->whereIn('id', $selectedIds)->get();
            $restoredCount = 0;
            $errors = [];

            foreach ($customers as $customer) {
                try {
                    if (auth()->user()->can('customer.restore', $customer) && $customer->canBeRestored()) {
                        $customer->restoreCustomer();
                        $restoredCount++;
                    } else {
                        $errors[] = "Cannot restore {$customer->full_name}: Insufficient permissions or customer cannot be restored.";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error restoring {$customer->full_name}: " . $e->getMessage();
                }
            }

            if ($restoredCount > 0) {
                session()->flash('message', "Successfully restored {$restoredCount} customer(s).");
            }

            if (!empty($errors)) {
                session()->flash('error', implode('<br>', $errors));
            }
        } finally {
            $this->bulkActionInProgress = false;
            $this->loadingMessage = '';
        }

        $this->clearSelected();
    }

    public function exportCustomers()
    {
        $this->authorize('customer.export');
        
        $this->bulkActionInProgress = true;
        $this->loadingMessage = 'Preparing export...';
        
        try {
            // This method can be implemented later for data export functionality
            $this->dispatchBrowserEvent('show-alert', [
                'type' => 'info',
                'message' => 'Export functionality will be implemented in a future update.'
            ]);
        } finally {
            $this->bulkActionInProgress = false;
            $this->loadingMessage = '';
        }
        
        $this->clearSelected();
    }

    /**
     * Clear selected items
     */
    public function clearSelected()
    {
        // Clear selected items using the correct property for Laravel Livewire Tables
        $this->selectedKeys = [];
    }
}
