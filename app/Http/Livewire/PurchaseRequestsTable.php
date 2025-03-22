<?php

namespace App\Http\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PurchaseRequest;

class PurchaseRequestsTable extends DataTableComponent
{
    public $refresh = 'visible';

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
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Item Name", "item_name")
                ->sortable()
                ->searchable(),
            Column::make("URL", "item_url")
                ->sortable(),
            Column::make("Quantity", "quantity")
                ->sortable(),
            Column::make("Unit Price", "unit_price")
                ->sortable()
                ->addClass('hidden md:table-cell'),
            Column::make("Shipping Fee", "shipping_fee")
                ->sortable(),
            Column::make("Tax", "tax")
                ->sortable(),
            Column::make("Total Price", "total_price")
                ->sortable(),
            Column::make("Status", "status")
                ->sortable(),
            Column::make("Remarks", "remarks"),
            Column::make("Actions"),
        ];
    }

    public function query(): Builder
    {
        return PurchaseRequest::query()
            ->orderBy('id', 'desc')
            ->where('user_id', auth()->id())
            ->when($this->getFilter('search'), fn($query, $search) => $query->search($search))
            ->when($this->getFilter('item_name'), fn($query, $item_name) => $query->where('item_name', $item_name))
            ->when($this->getFilter('status'), fn($query, $status) => $query->where('status', $status));
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.purchase-requests-table';
    }
}
