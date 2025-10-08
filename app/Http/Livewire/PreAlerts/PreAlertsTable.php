<?php

namespace App\Http\Livewire\PreAlerts;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PreAlert;

class PreAlertsTable extends DataTableComponent
{
    // public $refresh = 'visible';

    public function columns(): array
    {
        return [
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
            Column::make("Status", "packagePreAlert.status")
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
            ->with('shipper')
            ->with('packagePreAlert')
            ->where('user_id', auth()->id())
            ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
            ->when($this->getFilter('tracking_number'), fn($query, $tracking_number) => $query->where('tracking_number', $tracking_number));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.pre-alerts-table';
    }
}
