<?php

namespace App\Http\Livewire\Manifests;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Services\PackageStatusService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PackageWorkflow extends Component
{
    use WithPagination;

    public $selectedPackages = [];
    public $selectAll = false;
    public $bulkStatus = '';
    public $showConfirmModal = false;
    public $confirmingStatus = null;
    public $confirmingPackages = [];
    public $statusFilter = '';
    public $search = '';
    public $notes = '';
    public $manifestId = null;

    protected $packageStatusService;

    protected $listeners = [
        'refreshPackages' => '$refresh',
        'packageStatusUpdated' => 'handleStatusUpdate'
    ];

    public function boot(PackageStatusService $packageStatusService)
    {
        $this->packageStatusService = $packageStatusService;
    }

    public function mount($manifestId = null)
    {
        $this->manifestId = $manifestId;
    }

    public function render()
    {
        $packages = $this->getPackages();
        $statusOptions = $this->getStatusOptions();
        $statusStatistics = $this->packageStatusService->getStatusStatistics();

        return view('livewire.manifests.package-workflow', [
            'packages' => $packages,
            'statusOptions' => $statusOptions,
            'statusStatistics' => $statusStatistics,
        ]);
    }

    public function getPackages()
    {
        $query = Package::with(['user', 'manifest', 'office', 'shipper']);

        // Filter by manifest if specified
        if ($this->manifestId) {
            $query->where('manifest_id', $this->manifestId);
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Apply search
        if ($this->search) {
            $query->search($this->search);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function getStatusOptions()
    {
        $options = [];
        foreach (PackageStatus::cases() as $status) {
            $options[$status->value] = $status->getLabel();
        }
        return $options;
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedPackages = $this->getPackages()->pluck('id')->toArray();
        } else {
            $this->selectedPackages = [];
        }
    }

    public function updatedSelectedPackages()
    {
        $totalPackages = $this->getPackages()->count();
        $this->selectAll = count($this->selectedPackages) === $totalPackages;
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
        $this->selectedPackages = [];
        $this->selectAll = false;
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->selectedPackages = [];
        $this->selectAll = false;
    }

    public function confirmBulkStatusUpdate()
    {
        $this->validate([
            'bulkStatus' => 'required|string',
            'selectedPackages' => 'required|array|min:1',
        ], [
            'bulkStatus.required' => 'Please select a status to update to.',
            'selectedPackages.required' => 'Please select at least one package.',
            'selectedPackages.min' => 'Please select at least one package.',
        ]);

        try {
            $newStatus = PackageStatus::from($this->bulkStatus);
            $packages = Package::whereIn('id', $this->selectedPackages)->get();
            
            // Validate transitions for all selected packages
            $invalidTransitions = [];
            foreach ($packages as $package) {
                if (!$package->canTransitionTo($newStatus)) {
                    $invalidTransitions[] = [
                        'tracking_number' => $package->tracking_number,
                        'current_status' => $package->status->getLabel(),
                        'attempted_status' => $newStatus->getLabel(),
                    ];
                }
            }

            if (!empty($invalidTransitions)) {
                $this->addError('bulkStatus', 'Some packages cannot transition to the selected status. Please check individual package statuses.');
                return;
            }

            $this->confirmingStatus = $newStatus;
            $this->confirmingPackages = $packages->toArray();
            $this->showConfirmModal = true;

        } catch (\ValueError $e) {
            $this->addError('bulkStatus', 'Invalid status selected.');
        }
    }

    public function executeBulkStatusUpdate()
    {
        if (!$this->confirmingStatus || empty($this->confirmingPackages)) {
            return;
        }

        $packageIds = collect($this->confirmingPackages)->pluck('id')->toArray();
        $results = $this->packageStatusService->bulkUpdateStatus(
            $packageIds,
            $this->confirmingStatus,
            Auth::user(),
            $this->notes ?: null
        );

        $this->showConfirmModal = false;
        $this->selectedPackages = [];
        $this->selectAll = false;
        $this->bulkStatus = '';
        $this->notes = '';
        $this->confirmingStatus = null;
        $this->confirmingPackages = [];

        if (count($results['success']) > 0) {
            session()->flash('success', 
                count($results['success']) . ' package(s) updated successfully.'
            );
        }

        if (count($results['failed']) > 0) {
            session()->flash('error', 
                count($results['failed']) . ' package(s) failed to update. Please check the logs.'
            );
        }

        $this->emit('packageStatusUpdated');
    }

    public function cancelBulkUpdate()
    {
        $this->showConfirmModal = false;
        $this->confirmingStatus = null;
        $this->confirmingPackages = [];
        $this->notes = '';
    }

    public function updateSinglePackageStatus($packageId, $newStatusValue)
    {
        try {
            $package = Package::findOrFail($packageId);
            $newStatus = PackageStatus::from($newStatusValue);

            if (!$package->canTransitionTo($newStatus)) {
                session()->flash('error', 
                    "Cannot transition package {$package->tracking_number} from {$package->status->getLabel()} to {$newStatus->getLabel()}."
                );
                return;
            }

            if ($this->packageStatusService->updateStatus($package, $newStatus, Auth::user())) {
                session()->flash('success', 
                    "Package {$package->tracking_number} status updated to {$newStatus->getLabel()}."
                );
                $this->emit('packageStatusUpdated');
            } else {
                session()->flash('error', 
                    "Failed to update package {$package->tracking_number} status."
                );
            }

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while updating the package status.');
        }
    }

    public function getValidTransitionsForPackage($packageId)
    {
        $package = Package::find($packageId);
        if (!$package) {
            return [];
        }

        $transitions = [];
        foreach ($package->getValidStatusTransitions() as $status) {
            $transitions[$status->value] = $status->getLabel();
        }

        return $transitions;
    }

    public function handleStatusUpdate()
    {
        // Refresh the component when status is updated
        $this->render();
    }

    public function clearFilters()
    {
        $this->statusFilter = '';
        $this->search = '';
        $this->selectedPackages = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function getSelectedPackagesProperty()
    {
        return Package::whereIn('id', $this->selectedPackages)->get();
    }

    public function canDistributeSelected()
    {
        if (empty($this->selectedPackages)) {
            return false;
        }

        $packages = Package::whereIn('id', $this->selectedPackages)->get();
        return $packages->every(function ($package) {
            return $package->canBeDistributed();
        });
    }

    public function initiateDistribution()
    {
        if (!$this->canDistributeSelected()) {
            session()->flash('error', 'Only packages with "Ready for Pickup" status can be distributed.');
            return;
        }

        // Emit event to parent component or redirect to distribution page
        $this->emit('initiatePackageDistribution', $this->selectedPackages);
    }
}