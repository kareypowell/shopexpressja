<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\DashboardAnalyticsService;
use App\Models\Package;
use App\Models\Manifest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShipmentAnalytics extends Component
{
    public string $dateRange = '30';
    public string $customStartDate = '';
    public string $customEndDate = '';
    public array $filters = [];
    public bool $isLoading = false;

    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
        'refreshDashboard' => 'refreshData'
    ];

    protected DashboardAnalyticsService $analyticsService;

    public function boot(DashboardAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function mount()
    {
        $this->filters = [
            'date_range' => $this->dateRange,
            'custom_start' => $this->customStartDate,
            'custom_end' => $this->customEndDate,
        ];
    }

    public function updateFilters(array $filters)
    {
        $this->filters = $filters;
        $this->dateRange = $filters['date_range'] ?? '30';
        $this->customStartDate = $filters['custom_start'] ?? '';
        $this->customEndDate = $filters['custom_end'] ?? '';
    }

    public function refreshData()
    {
        $this->isLoading = true;
        $this->analyticsService->invalidateCache('shipment_*');
        $this->isLoading = false;
    }

    /**
     * Get shipment volume trend data for area chart
     */
    public function getShipmentVolumeData(): array
    {
        $dateRange = $this->getDateRange();
        
        $volumeData = Package::whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as volume, SUM(weight) as total_weight')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'volume' => $item->volume,
                    'weight' => (float) $item->total_weight,
                ];
            })
            ->toArray();

        return $volumeData;
    }

    /**
     * Get package status distribution for stacked bar chart
     */
    public function getPackageStatusDistribution(): array
    {
        $dateRange = $this->getDateRange();
        
        $statusData = Package::whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($dayData, $date) {
                $statusCounts = $dayData->pluck('count', 'status')->toArray();
                $pendingValue = \App\Enums\PackageStatus::PENDING;
                $processingValue = \App\Enums\PackageStatus::PROCESSING;
                $shippedValue = \App\Enums\PackageStatus::SHIPPED;
                $customsValue = \App\Enums\PackageStatus::CUSTOMS;
                $readyValue = \App\Enums\PackageStatus::READY;
                $deliveredValue = \App\Enums\PackageStatus::DELIVERED;
                $delayedValue = \App\Enums\PackageStatus::DELAYED;
                
                return [
                    'date' => $date,
                    'pending' => $statusCounts[$pendingValue] ?? 0,
                    'processing' => $statusCounts[$processingValue] ?? 0,
                    'shipped' => $statusCounts[$shippedValue] ?? 0,
                    'customs' => $statusCounts[$customsValue] ?? 0,
                    'ready' => $statusCounts[$readyValue] ?? 0,
                    'delivered' => $statusCounts[$deliveredValue] ?? 0,
                    'delayed' => $statusCounts[$delayedValue] ?? 0,
                ];
            })
            ->values()
            ->toArray();

        return $statusData;
    }

    /**
     * Get processing time analysis data
     */
    public function getProcessingTimeAnalysis(): array
    {
        $dateRange = $this->getDateRange();
        
        $processingData = Package::whereBetween('created_at', $dateRange)
            ->whereIn('status', [\App\Enums\PackageStatus::READY, \App\Enums\PackageStatus::DELIVERED])
            ->select('created_at', 'updated_at', 'status')
            ->get()
            ->map(function ($package) {
                $processingDays = $package->created_at->diffInDays($package->updated_at);
                return [
                    'days' => $processingDays,
                    'status' => $package->status,
                    'date' => $package->created_at->format('Y-m-d'),
                ];
            })
            ->groupBy('date')
            ->map(function ($dayData, $date) {
                $times = $dayData->pluck('days');
                return [
                    'date' => $date,
                    'avg_processing_time' => $times->avg(),
                    'min_processing_time' => $times->min(),
                    'max_processing_time' => $times->max(),
                    'count' => $times->count(),
                ];
            })
            ->values()
            ->toArray();

        return $processingData;
    }

    /**
     * Get shipping method breakdown for pie chart
     */
    public function getShippingMethodBreakdown(): array
    {
        $dateRange = $this->getDateRange();
        
        $methodData = Package::whereBetween('packages.created_at', $dateRange)
            ->join('manifests', 'packages.manifest_id', '=', 'manifests.id')
            ->select('manifests.type', DB::raw('COUNT(*) as count'), DB::raw('SUM(packages.weight) as total_weight'))
            ->groupBy('manifests.type')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => ucfirst($item->type),
                    'count' => $item->count,
                    'weight' => (float) $item->total_weight,
                    'percentage' => 0, // Will be calculated in the view
                ];
            })
            ->toArray();

        // Calculate percentages
        $totalCount = collect($methodData)->sum('count');
        if ($totalCount > 0) {
            foreach ($methodData as &$method) {
                $method['percentage'] = round(($method['count'] / $totalCount) * 100, 1);
            }
        }

        return $methodData;
    }

    /**
     * Get delivery performance metrics
     */
    public function getDeliveryPerformanceMetrics(): array
    {
        $dateRange = $this->getDateRange();
        
        $totalPackages = Package::whereBetween('created_at', $dateRange)->count();
        $deliveredPackages = Package::whereBetween('created_at', $dateRange)
            ->whereIn('status', [\App\Enums\PackageStatus::DELIVERED, \App\Enums\PackageStatus::READY])
            ->count();
        $delayedPackages = Package::whereBetween('created_at', $dateRange)
            ->where('status', \App\Enums\PackageStatus::DELAYED)
            ->count();
        
        $onTimeDeliveryRate = $totalPackages > 0 
            ? round((($deliveredPackages - $delayedPackages) / $totalPackages) * 100, 1)
            : 0;
        
        $deliveryRate = $totalPackages > 0 
            ? round(($deliveredPackages / $totalPackages) * 100, 1)
            : 0;

        return [
            'total_packages' => $totalPackages,
            'delivered_packages' => $deliveredPackages,
            'delayed_packages' => $delayedPackages,
            'on_time_delivery_rate' => $onTimeDeliveryRate,
            'overall_delivery_rate' => $deliveryRate,
        ];
    }

    /**
     * Get average processing time by shipping method
     */
    public function getProcessingTimeByMethod(): array
    {
        $dateRange = $this->getDateRange();
        
        $methodTimes = Package::whereBetween('packages.created_at', $dateRange)
            ->join('manifests', 'packages.manifest_id', '=', 'manifests.id')
            ->whereIn('packages.status', [\App\Enums\PackageStatus::READY, \App\Enums\PackageStatus::DELIVERED])
            ->select(
                'manifests.type',
                'packages.created_at',
                'packages.updated_at'
            )
            ->get()
            ->groupBy('type')
            ->map(function ($packages, $type) {
                $processingTimes = $packages->map(function ($package) {
                    return Carbon::parse($package->created_at)->diffInDays(Carbon::parse($package->updated_at));
                });
                
                return [
                    'method' => ucfirst($type),
                    'avg_processing_time' => round($processingTimes->avg(), 1),
                    'min_processing_time' => $processingTimes->min(),
                    'max_processing_time' => $processingTimes->max(),
                    'package_count' => $processingTimes->count(),
                ];
            })
            ->values()
            ->toArray();

        return $methodTimes;
    }

    /**
     * Get capacity utilization data
     */
    public function getCapacityUtilization(): array
    {
        $dateRange = $this->getDateRange();
        
        $manifestData = Manifest::whereBetween('created_at', $dateRange)
            ->withCount('packages')
            ->with(['packages' => function ($query) {
                $query->select('manifest_id', DB::raw('SUM(weight) as total_weight'), DB::raw('SUM(cubic_feet) as total_volume'))
                    ->groupBy('manifest_id');
            }])
            ->get()
            ->map(function ($manifest) {
                $totalWeight = $manifest->packages->sum('total_weight') ?? 0;
                $totalVolume = $manifest->packages->sum('total_volume') ?? 0;
                
                return [
                    'manifest_name' => $manifest->name,
                    'type' => $manifest->type,
                    'package_count' => $manifest->packages_count,
                    'total_weight' => (float) $totalWeight,
                    'total_volume' => (float) $totalVolume,
                    'shipment_date' => $manifest->shipment_date,
                ];
            })
            ->toArray();

        return $manifestData;
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(): array
    {
        $days = $this->filters['date_range'] ?? 30;
        
        if (!empty($this->filters['custom_start']) && !empty($this->filters['custom_end'])) {
            return [
                Carbon::parse($this->filters['custom_start'])->startOfDay(),
                Carbon::parse($this->filters['custom_end'])->endOfDay(),
            ];
        }

        return [
            Carbon::now()->subDays($days)->startOfDay(),
            Carbon::now()->endOfDay(),
        ];
    }

    public function render()
    {
        return view('livewire.shipment-analytics', [
            'shipmentVolumeData' => $this->getShipmentVolumeData(),
            'packageStatusData' => $this->getPackageStatusDistribution(),
            'processingTimeData' => $this->getProcessingTimeAnalysis(),
            'shippingMethodData' => $this->getShippingMethodBreakdown(),
            'deliveryMetrics' => $this->getDeliveryPerformanceMetrics(),
            'processingTimeByMethod' => $this->getProcessingTimeByMethod(),
            'capacityData' => $this->getCapacityUtilization(),
        ]);
    }
}