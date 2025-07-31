<?php

namespace App\Http\Livewire\Customers;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use App\Models\User;

class AdminCustomersTable extends DataTableComponent
{
    // public $refresh = 'visible';

    public array $bulkActions = [
        'exportCustomers' => 'Export XLXS',
        'bulkDelete' => 'Delete Selected',
        'bulkRestore' => 'Restore Selected',
    ];

    public array $filterNames = [
        'parish' => 'Parish',
        'status' => 'Status',
    ];

    public $showDeleteModal = false;
    public $customerToDelete = null;
    public $showRestoreModal = false;
    public $customerToRestore = null;

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
        $status = $this->getFilter('status') ?: 'active';
        
        return User::query()
            ->byStatus($status)
            ->with('profile')
            ->orderBy('id', 'desc')
            ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
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
            $query->whereHas('profile', fn($q) => $q->where('parish', $parish)));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.admin-customers-table';
    }



    public function confirmDelete($customerId)
    {
        $this->customerToDelete = User::withTrashed()->find($customerId);
        $this->showDeleteModal = true;
    }

    public function deleteCustomer()
    {
        try {
            if ($this->customerToDelete && $this->customerToDelete->canBeDeleted()) {
                $this->customerToDelete->softDeleteCustomer();
                session()->flash('message', 'Customer deleted successfully.');
            } else {
                session()->flash('error', 'Customer cannot be deleted.');
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
        $this->showRestoreModal = true;
    }

    public function restoreCustomer()
    {
        try {
            if ($this->customerToRestore && $this->customerToRestore->canBeRestored()) {
                $this->customerToRestore->restoreCustomer();
                session()->flash('message', 'Customer restored successfully.');
            } else {
                session()->flash('error', 'Customer cannot be restored.');
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
        $customers = User::withTrashed()->whereIn('id', $this->getSelectedItems())->get();
        $deletedCount = 0;
        $errors = [];

        foreach ($customers as $customer) {
            try {
                if ($customer->canBeDeleted()) {
                    $customer->softDeleteCustomer();
                    $deletedCount++;
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
        $customers = User::withTrashed()->whereIn('id', $this->getSelectedItems())->get();
        $restoredCount = 0;
        $errors = [];

        foreach ($customers as $customer) {
            try {
                if ($customer->canBeRestored()) {
                    $customer->restoreCustomer();
                    $restoredCount++;
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
}
