<?php

namespace App\Http\Livewire\Manifests\Packages;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Package;

class ManifestPackagesTable extends DataTableComponent
{
    public $refresh = 'visible';

    public $manifest_id;

    public function mount()
    {
        $this->manifest_id = request()->route('manifest_id');;
    }

    public function columns(): array
    {
        return [
            Column::make("Tracking Number", "tracking_number")
                ->sortable()
                ->searchable(),
            Column::make("Description", "description")
                ->sortable()
                ->searchable(),
            Column::make("Weight (lbs)", "weight")
                ->sortable()
                ->searchable(),
            Column::make("Status", "status")
                ->sortable()
                ->searchable(),
            Column::make("Shipper (carrier)", "shipper.name")
                ->sortable()
                ->searchable(),
            Column::make("Estimated Value (USD)", "estimated_value")
                ->sortable()
                ->searchable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Updated at", "updated_at")
                ->sortable(),
        ];
    }

    public function query(): Builder
    {
        return Package::query()
            ->where('manifest_id', $this->manifest_id)
            ->orderBy('created_at', 'desc');
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.manifest-pkgs-table';
    }
}
