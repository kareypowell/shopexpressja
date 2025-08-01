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
            ->with('profile')
            ->orderBy('id', 'desc');

        // Enhanced search functionality
        if ($search = $this->getFilter('search')) {
            $this->searchHighlight = $search;
            $query->search($search);
        }

        return $query
            ->when($this->getFilter('first_name'), fn($query, $first_name) => $query->where('first_name', $first_name))
            ->when($this->getFilter('last_name'), fn($query, $last_name) => $query->where('last_name', $last_name))
            ->when($this->getFilter('email'), fn($query, $email) => $query->where('email', $email))
            ->when($this->getFilter('account_number'), fn($query, $account_number) =>
            $query->whereHas('profile', fn($q) => $q->where('account_number', $account_number)))
            ->when($this->getFilter('tax_number'), fn($query, $tax_number) =>
            $query->whereHas('profile', fn($q) => $q->where('tax_number', $tax_number)))
            ->when($this->getFilter('telephone_number'), fn($query, $telephone_number) =>
            $query->whereHas('profile', fn($q) => $q->where('telephone_number', $telephone_number)))
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
        
        return redirect()->route('admin.customers.profile', $customerId);
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

        return preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark class="bg-yellow-200">$1</mark>', $text);
    }

    /**
     * Get search highlight term for templates
     */
    public function getSearchHighlightProperty()
    {
        return $this->searchHighlight;
    }



    public function confirmDelete($customerId)
    {
        $this->reset(['showDeleteModal', 'customerToDelete']);
        
        $this->customerToDelete = User::withTrashed()->find($customerId);
        $this->authorize('customer.delete', $this->customerToDelete);
        
        $this->showDeleteModal = true;
        
        $this->emit('refreshComponent');
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
        $this->customerToRestore = User::withTrashed()->find($customerId);
        $this->authorize('customer.restore', $this->customerToRestore);
        
        $this->showRestoreModal = true;
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
        
        $customers = User::withTrashed()->whereIn('id', $this->getSelectedItems())->get();
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
    }

    public function bulkRestore()
    {
        $this->authorize('customer.bulkOperations');
        
        $customers = User::withTrashed()->whereIn('id', $this->getSelectedItems())->get();
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

        $this->clearSelected();
    }

    public function exportCustomers()
    {
        $this->authorize('customer.export');
        
        // This method can be implemented later for data export functionality
        $this->dispatchBrowserEvent('show-alert', [
            'type' => 'info',
            'message' => 'Export functionality will be implemented in a future update.'
        ]);
        
        $this->clearSelected();
    }
}
