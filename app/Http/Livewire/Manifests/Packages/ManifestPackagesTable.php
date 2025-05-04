<?php

namespace App\Http\Livewire\Manifests\Packages;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use App\Models\Package;
use App\Models\PackagePreAlert;

class ManifestPackagesTable extends DataTableComponent
{
    public $refresh = 'visible';

    public $manifest_id;

    public function mount()
    {
        $this->manifest_id = request()->route('manifest_id');
    }

    public array $bulkActions = [
        'setStatusToProcessing' => 'Change to Processing',
        'setStatusToShipped' => 'Change to Shipped',
        'setStatusToDelayed' => 'Change to Delayed',
        'setStatusToReady' => 'Change to Ready',
    ];

    public array $filterNames = [
        'status' => 'Status',
    ];
    public function filters(): array
    {
        return [
            'status' => Filter::make('Status')
                ->select([
                    '' => 'Any',
                    'Processing' => 'Processing',
                    'Shipped' => 'Shipped',
                    'Delayed' => 'Delayed',
                    'Ready' => 'Ready',
                ]),
        ];
    }
    public function setStatusToProcessing()
    {
        $this->setStatus('processing');
    }
    public function setStatusToShipped()
    {
        $this->setStatus('shipped');
    }
    public function setStatusToDelayed()
    {
        $this->setStatus('delayed');
    }
    public function setStatusToReady()
    {
        $this->setStatus('ready');
    }

    public function setStatus($status)
    {
        collect($this->selectedKeys)->each(function ($id) use ($status) {
            Package::where('id', $id)->update(['status' => $status]);
            PackagePreAlert::where('package_id', $id)->update(['status' => $status]);
        });

        // $this->clearSelected();
        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => "Status updated to {$status} successfully.",
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Customer", "user.full_name")
                ->searchable()
                ->sortable(),
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
            Column::make("Freight Price (JMD)", "freight_price")
                ->sortable()
                ->searchable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            // Column::make("Updated at", "updated_at")
            //     ->sortable(),
            Column::make("Actions", ""),
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
