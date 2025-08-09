<?php

namespace App\Http\Livewire\Manifests\Packages;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use App\Models\Package;
use App\Models\PackagePreAlert;
use App\Enums\PackageStatus;
use App\Services\PackageStatusService;
use Illuminate\Support\Facades\Log;

class ManifestPackagesTable extends DataTableComponent
{
    // public $refresh = 'visible';

    public $manifest_id;

    public function mount()
    {
        $this->manifest_id = request()->route('manifest_id');
    }

    public array $bulkActions = [
        'setStatusToProcessing' => 'Change to Processing',
        'setStatusToShipped' => 'Change to Shipped',
        'setStatusToCustoms' => 'Change to Customs',
        'setStatusToReady' => 'Change to Ready',
        // 'setStatusToDelivered' => 'Change to Delivered', // Removed - only available through distribution process
        'setStatusToDelayed' => 'Change to Delayed',
    ];

    public array $filterNames = [
        'status' => 'Status',
    ];

    public function filters(): array
    {
        $statusOptions = collect(PackageStatus::cases())->mapWithKeys(function ($status) {
            return [$status->value => $status->getLabel()];
        })->toArray();

        return [
            'status' => Filter::make('Status')
                ->select(array_merge(['' => 'Any'], $statusOptions)),
        ];
    }

    public function setStatusToProcessing()
    {
        $this->setStatus(PackageStatus::PROCESSING);
    }

    public function setStatusToShipped()
    {
        $this->setStatus(PackageStatus::SHIPPED);
    }

    public function setStatusToCustoms()
    {
        $this->setStatus(PackageStatus::CUSTOMS);
    }

    public function setStatusToReady()
    {
        $this->setStatus(PackageStatus::READY);
    }

    // setStatusToDelivered method removed - packages can only be delivered through distribution process

    public function setStatusToDelayed()
    {
        $this->setStatus(PackageStatus::DELAYED);
    }

    public function setStatus(PackageStatus $status)
    {
        $packageStatusService = app(PackageStatusService::class);
        $successCount = 0;
        $errorCount = 0;

        collect($this->selectedKeys)->each(function ($id) use ($status, $packageStatusService, &$successCount, &$errorCount) {
            try {
                $package = Package::find($id);
                if ($package) {
                    $result = $packageStatusService->updateStatus(
                        $package,
                        $status,
                        auth()->user(),
                        'Bulk status update from manifest packages table'
                    );

                    if ($result) {
                        $successCount++;
                        
                        // Update PackagePreAlert status as well for backward compatibility
                        PackagePreAlert::where('package_id', $id)->update(['status' => $status->value]);

                        // Note: Email notifications are now handled automatically by PackageStatusService
                    } else {
                        $errorCount++;
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to update package status in bulk action', [
                    'package_id' => $id,
                    'status' => $status->value,
                    'error' => $e->getMessage()
                ]);
            }
        });

        // Show results
        if ($successCount > 0) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'success',
                'message' => "Status updated to {$status->getLabel()} for {$successCount} package(s) successfully. Email notifications sent to customers.",
            ]);
        }

        if ($errorCount > 0) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => "Failed to update {$errorCount} package(s).",
            ]);
        }
    }

    public function columns(): array
    {
        return [
            Column::make("Customer", "user.full_name")
                // ->searchable()
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
            Column::make("Container Type", "container_type")
                ->sortable()
                ->searchable(),
            Column::make("Dimensions", ""),
            Column::make("Cubic Feet", "cubic_feet")
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
            ->with(['manifest', 'items'])
            ->where('manifest_id', $this->manifest_id)
            ->orderBy('created_at', 'desc');
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.manifest-pkgs-table';
    }
}
