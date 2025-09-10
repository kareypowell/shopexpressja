<?php

namespace App\Http\Livewire\Manifests;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use App\Models\Manifest;

class ManifestsTable extends DataTableComponent
{
    // public $refresh = 'visible';

    public array $filterNames = [
        'type' => 'Type',
        'status' => 'Status',
    ];

    public function filters(): array
    {
        return [
            'type' => Filter::make('Type')
                ->select([
                    '' => 'Any',
                    'air' => 'Air',
                    'sea' => 'Sea',
                ]),
            'status' => Filter::make('Status')
                ->select([
                    '' => 'Any',
                    '1' => 'Open',
                    '0' => 'Closed',
                ]),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Name", "name")
                ->sortable(),
            Column::make("Shipment date", "shipment_date")
                ->sortable(),
            Column::make("Reservation number", "reservation_number")
                ->sortable(),
            Column::make("Flight number", "flight_number")
                ->sortable(),
            Column::make("Flight destination", "flight_destination")
                ->sortable(),
            Column::make("Packages", "manifest.packages")
                ->sortable(),
            Column::make("Estimated Value (JMD)", "manifest.packages")
                ->sortable(),
            Column::make("Estimated Weight (LBS/KG)", "manifest.packages")
                ->sortable(),
            Column::make("Exchange rate (USD)", "exchange_rate")
                ->sortable(),
            Column::make("Type", "type")
                ->sortable(),
            Column::make("Status", "is_open")
                ->sortable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Actions", "")
        ];
    }

    public function query(): Builder
    {
        return Manifest::query()
                    ->orderBy('id', 'desc')
                    ->with('packages')
                    ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
                    ->when($this->getFilter('name'), fn($query, $name) => $query->where('name', $name))
                    ->when($this->getFilter('reservation_number'), fn($query, $reservation_number) => $query->where('reservation_number', $reservation_number))
                    ->when($this->getFilter('flight_number'), fn($query, $flight_number) => $query->where('flight_number', $flight_number))
                    ->when($this->getFilter('type'), fn($query, $type) => $query->where('type', $type))
                    ->when($this->getFilter('status'), fn($query, $status) => $query->where('is_open', (bool) $status));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.manifests-table';
    }
}
