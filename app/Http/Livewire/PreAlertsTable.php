<?php

namespace App\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PreAlert;

class PreAlertsTable extends DataTableComponent
{
    protected $model = PreAlert::class;

    public $refresh = true;

    public function columns(): array
    {
        return [
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
            Column::blank(),
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
            ->when($this->getFilter('tracking_number'), fn($query, $tracking_number) => $query->where('tracking_number', $tracking_number))
            ->when($this->getFilter('status'), fn($query, $status) => $query->where('status', $status));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.pre-alerts-table';
    }
}
