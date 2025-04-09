<?php

namespace App\Http\Livewire\PreAlerts;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PreAlert;

class AdminPreAlertsTable extends DataTableComponent
{
    public $refresh = 'visible';

    public function columns(): array
    {
        return [
            Column::make("Customer", "user.full_name")
                ->searchable()
                ->sortable(),
            Column::make("Shipper", "shipper.name")
                ->searchable()
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
            Column::make("Status", "packagePreAlert.status")
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
            ->orderBy('id', 'desc')
            ->with('user')
            ->with('shipper')
            ->with('packagePreAlert')
            ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
            ->when($this->getFilter('tracking_number'), fn($query, $tracking_number) => $query->where('tracking_number', $tracking_number));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.admin-pre-alerts-table';
    }
}
