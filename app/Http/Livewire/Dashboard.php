<?php

namespace App\Http\Livewire;

use App\Models\Package;
use App\Models\ConsolidatedPackage;
use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;

class Dashboard extends Component
{
    public int $inComingAir = 0;
    public int $inComingSea = 0;
    public int $availableAir = 0;
    public int $availableSea = 0;
    public float $accountBalance = 0;
    public float $creditBalance = 0;
    public float $totalAvailableBalance = 0;
    public float $pendingPackageCharges = 0;
    public float $totalAmountNeeded = 0;
    public int $delayedPackages = 0;

    // Package filtering
    public $packageFilter = 'all';
    public $showFilterDropdown = false;

    public function mount()
    {
        // Individual packages
        $this->inComingAir = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'air');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['processing', 'shipped', 'customs'])
                                    ->count();

        $this->inComingSea = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'sea');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['processing', 'shipped', 'customs'])
                                    ->count();

        $this->availableAir = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'air');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['ready'])
                                    ->count();

        $this->availableSea = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'sea');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['ready'])
                                    ->count();

        // Add consolidated packages to the counts
        $consolidatedInTransit = ConsolidatedPackage::where('customer_id', auth()->id())
            ->active()
            ->whereIn('status', ['processing', 'shipped', 'customs'])
            ->count();

        $consolidatedReady = ConsolidatedPackage::where('customer_id', auth()->id())
            ->active()
            ->where('status', 'ready')
            ->count();

        // For consolidated packages, we'll add them to both air and sea counts for now
        // In a more sophisticated implementation, you might want to categorize them based on their contents
        $this->inComingAir += $consolidatedInTransit;
        $this->availableAir += $consolidatedReady;

        // Get user account balance information
        $user = auth()->user();
        $this->accountBalance = $user->account_balance ?? 0.0;
        $this->creditBalance = $user->credit_balance ?? 0.0;
        $this->totalAvailableBalance = $user->total_available_balance ?? 0.0;
        $this->pendingPackageCharges = $user->pending_package_charges ?? 0.0;
        $this->totalAmountNeeded = $user->total_amount_needed ?? 0.0;

        $this->delayedPackages = Package::where('user_id', auth()->id())
                                        ->where('status', 'delayed')->count() +
                                ConsolidatedPackage::where('customer_id', auth()->id())
                                        ->active()
                                        ->where('status', 'delayed')->count();
    }

    public function showPackageDetails($packageId)
    {
        // Emit event to show package details modal
        $this->emit('showPackageDetails', $packageId);
    }

    public function showConsolidatedPackageDetails($consolidatedPackageId)
    {
        // Emit event to show consolidated package details modal
        $this->emit('showConsolidatedPackageDetails', $consolidatedPackageId);
    }

    public function setPackageFilter($filter)
    {
        $this->packageFilter = $filter;
        $this->showFilterDropdown = false;
    }

    public function toggleFilterDropdown()
    {
        $this->showFilterDropdown = !$this->showFilterDropdown;
    }

    public function getFilteredPackagesProperty()
    {
        // Get individual packages (excluding those that are part of consolidated packages)
        $individualQuery = auth()->user()->packages()
            ->with(['shipper', 'office', 'manifest'])
            ->whereNull('consolidated_package_id') // Only get packages that are NOT consolidated
            ->latest();

        switch ($this->packageFilter) {
            case 'air':
                $individualQuery->whereHas('manifest', function (Builder $q) {
                    $q->where('type', 'air');
                });
                break;
            case 'sea':
                $individualQuery->whereHas('manifest', function (Builder $q) {
                    $q->where('type', 'sea');
                });
                break;
            case 'ready':
                $individualQuery->where('status', 'ready');
                break;
            case 'in-transit':
                $individualQuery->whereIn('status', ['processing', 'shipped', 'customs']);
                break;
            case 'delivered':
                $individualQuery->where('status', 'delivered');
                break;
            default:
                // 'all' - no additional filtering
                break;
        }

        $individualPackages = $individualQuery->get();

        // Get consolidated packages and convert them to a format compatible with the view
        $consolidatedPackages = ConsolidatedPackage::where('customer_id', auth()->id())
            ->active()
            ->with(['packages.manifest', 'packages.shipper', 'packages.office'])
            ->latest()
            ->get()
            ->filter(function ($consolidatedPackage) {
                // Only include consolidated packages that have packages
                return $consolidatedPackage->packages->count() > 0;
            })
            ->map(function ($consolidatedPackage) {
                // Create a virtual package object that represents the consolidated package
                $virtualPackage = new \stdClass();
                $virtualPackage->id = 'consolidated_' . $consolidatedPackage->id;
                $virtualPackage->tracking_number = $consolidatedPackage->consolidated_tracking_number;
                $virtualPackage->description = "Consolidated Package ({$consolidatedPackage->packages->count()} packages)";
                $virtualPackage->status = $consolidatedPackage->status;
                $virtualPackage->status_value = $consolidatedPackage->status;
                $virtualPackage->status_label = ucfirst(str_replace('_', ' ', $consolidatedPackage->status));
                $virtualPackage->status_badge_class = $this->getConsolidatedStatusBadgeClass($consolidatedPackage->status);
                $virtualPackage->weight = $consolidatedPackage->total_weight ?? 0;
                $virtualPackage->cubic_feet = $consolidatedPackage->packages->sum('cubic_feet') ?? 0;
                $virtualPackage->total_cost = $consolidatedPackage->total_cost ?? 0;
                $virtualPackage->created_at = $consolidatedPackage->consolidated_at ?? $consolidatedPackage->created_at;
                $virtualPackage->is_consolidated = true;
                $virtualPackage->consolidated_package_id = $consolidatedPackage->id;
                
                // Use the first package's manifest and shipper info for display
                $firstPackage = $consolidatedPackage->packages->first();
                $virtualPackage->manifest = $firstPackage ? $firstPackage->manifest : null;
                $virtualPackage->shipper = $firstPackage ? $firstPackage->shipper : null;
                
                return $virtualPackage;
            });

        // Apply filtering to consolidated packages
        if ($this->packageFilter !== 'all') {
            $consolidatedPackages = $consolidatedPackages->filter(function ($package) {
                switch ($this->packageFilter) {
                    case 'air':
                        return $package->manifest && $package->manifest->type === 'air';
                    case 'sea':
                        return $package->manifest && $package->manifest->type === 'sea';
                    case 'ready':
                        return $package->status === 'ready';
                    case 'in-transit':
                        return in_array($package->status, ['processing', 'shipped', 'customs']);
                    case 'delivered':
                        return $package->status === 'delivered';
                    default:
                        return true;
                }
            });
        }

        // Combine and sort by created_at
        $allPackages = collect($individualPackages)->concat($consolidatedPackages)
            ->sortByDesc(function ($package) {
                return $package->created_at;
            })
            ->take(5);

        return $allPackages;
    }

    /**
     * Get badge class for consolidated package status
     */
    private function getConsolidatedStatusBadgeClass($status)
    {
        switch ($status) {
            case 'ready':
                return 'success';
            case 'delivered':
                return 'success';
            case 'processing':
            case 'shipped':
            case 'customs':
                return 'primary';
            case 'delayed':
                return 'warning';
            case 'cancelled':
                return 'danger';
            default:
                return 'default';
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
