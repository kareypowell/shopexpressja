<?php

namespace App\Http\Livewire\PreAlerts;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PreAlert;

class AdminPreAlertsTable extends DataTableComponent
{
    // public $refresh = 'visible';

    public function columns(): array
    {
        return [
            Column::make("Customer")
                ->sortable(fn($query, $direction) => $query->orderBy('users.first_name', $direction)),
            Column::make("Account Number")
                ->sortable(),
            Column::make("Shipper", "shipper.name")
                ->sortable(),
            Column::make("Tracking Number", "tracking_number")
                ->searchable()
                ->sortable(),
            Column::make("Description", "description")
                ->searchable()
                ->sortable(),
            Column::make("Value", "value")
                ->searchable()
                ->sortable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Actions"),
        ];
    }

    public function query(): Builder
    {
        return PreAlert::query()
            ->select('pre_alerts.*', 'users.first_name', 'users.last_name', 'profiles.account_number')
            ->join('users', 'pre_alerts.user_id', '=', 'users.id')
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->with('shipper')
            ->orderBy('pre_alerts.id', 'desc')
            ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
            ->when($this->getFilter('tracking_number'), fn($query, $tracking_number) => $query->where('pre_alerts.tracking_number', $tracking_number));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.admin-pre-alerts-table';
    }
}
