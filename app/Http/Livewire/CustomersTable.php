<?php

namespace App\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use App\Models\User;

class CustomersTable extends DataTableComponent
{
    // public $refresh = 'visible';

    public array $bulkActions = [
        'exportCustomers' => 'Export XLXS',
    ];

    public array $filterNames = [
        'parish' => 'Parish',
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
            Column::make("Created On", "created_at")
                ->sortable(),
            Column::make("Actions", ""),
        ];
    }

    public function query(): Builder
    {
        return User::query()
            ->where('role_id', 3)
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
        return 'livewire-tables.rows.customers-table';
    }
}
