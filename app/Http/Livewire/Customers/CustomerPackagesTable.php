<?php

namespace App\Http\Livewire\Customers;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use App\Models\Package;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerPackagesTable extends DataTableComponent
{
    // use AuthorizesRequests;
    // public $refresh = 'visible';

    // public function mount()
    // {
    //     // Check authorization when component is mounted
    //     $this->authorize('viewAny', User::class);
    // }

    // public array $bulkActions = [
    //     'exportSelected' => 'Export',
    // ];

    public array $filterNames = [
        'status' => 'Status',
    ];

    public function filters(): array
    {
        return [
            'status' => Filter::make('Status')
            ->select([
                '' => 'Any',
                'pending' => 'Pending',
                'processing' => 'Processing',
                'shipped' => 'Shipped',
                'delayed' => 'Delayed',
                'ready_for_pickup' => 'Ready for Pickup',
            ]),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Tracking No.", "tracking_number")
                ->sortable()
                ->searchable(),
            Column::make("Description", "description"),
            Column::make("Weight (lbs)", "weight")
                ->sortable()
                ->searchable(),
            Column::make("Container Type", "container_type")
                ->sortable()
                ->searchable(),
            Column::make("Dimensions", ""),
            Column::make("Cubic Feet", "cubic_feet")
                ->sortable()
                ->searchable(),
            Column::make("Shipper", "shipper.name")
                ->sortable()
                ->searchable(),
            Column::make("Status", "status")
                ->sortable()
                ->searchable(),
            Column::make("Actions", ""),
        ];
    }



    public function query(): Builder
    {
        return Package::query()
            ->with(['manifest', 'items'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
            ->when($this->getFilter('tracking_number'), fn($query, $tracking_number) => $query->where('tracking_number', $tracking_number))
            ->when($this->getFilter('status'), fn($query, $status) => $query->where('status', $status));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.customer-pkgs-table';
    }
}
