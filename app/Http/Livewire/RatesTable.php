<?php

namespace App\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Rate;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class RatesTable extends DataTableComponent
{
    public array $filterNames = [
        'type' => 'Channel',
    ];

    public function filters(): array
    {
        return [
            'type' => Filter::make('Channel')
                ->select([
                    '' => 'Any',
                    'air' => 'Air',
                    'sea' => 'Sea',
                ]),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Weight (lbs)", "weight")
                ->sortable()
                ->searchable(),
            Column::make("Rate (USD)", "rate")
                ->sortable()
                ->searchable(),
            Column::make("Processing Fee (USD)", "processing_fee")
                ->sortable()
                ->searchable(),
            Column::make("Channel", "type")
                ->sortable()
                ->searchable(),
            // Column::make("Created at", "created_at")
            //     ->sortable(),
            // Column::make("Updated at", "updated_at")
            //     ->sortable(),
        ];
    }

    public function query(): Builder
    {
        return Rate::query()
            ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
            ->when($this->getFilter('weight'), fn($query, $weight) => $query->where('weight', $weight))
            ->when($this->getFilter('rate'), fn($query, $rate) => $query->where('rate', $rate))
            ->when($this->getFilter('type'), fn($query, $type) => $query->where('type', $type));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.rates-table';
    }
}
