<?php

namespace App\Services;

use App\Models\Manifest;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManifestAnalyticsService
{
    /**
     * Calculate processing times and efficiency metrics for manifests
     */
    public function calculateProcessingTimes(array $filters = []): array
    {
        $query = Manifest::query()
            ->select([
                'id', 'name', 'type', 'shipment_date', 'created_at', 
                'estimated_arrival_date', 'is_open'
            ])
            ->with(['packages' => function ($query) {
                $query->select([
                    'id', 'manifest_id', 'status', 'created_at', 'updated_at'
                ])->with('statusHistory');
            }]);

        $this->applyFilters($query, $filters);

        $manifests = $query->get();
        
        $processingMetrics = [];
        $totalProcessingTime = 0;
        $completedManifests = 0;

        foreach ($manifests as $manifest) {
            $metrics = $this->calculateManifestProcessingMetrics($manifest);
            $processingMetrics[] = $metrics;
            
            if ($metrics['is_completed']) {
                $totalProcessingTime += $metrics['total_processing_days'];
                $completedManifests++;
            }
        }

        return [
            'manifests' => $processingMetrics,
            'summary' => [
                'total_manifests' => count($processingMetrics),
                'completed_manifests' => $completedManifests,
                'average_processing_days' => $completedManifests > 0 
                    ? round($totalProcessingTime / $completedManifests, 2) 
                    : 0,
                'efficiency_rate' => count($processingMetrics) > 0 
                    ? round(($completedManifests / count($processingMetrics)) * 100, 2) 
                    : 0
            ]
        ];
    }

    /**
     * Analyze volume and weight trends by manifest type
     */
    public function analyzeVolumePatterns(array $filters = []): array
    {
        $query = Manifest::query()
            ->select([
                'id', 'name', 'type', 'shipment_date', 'created_at'
            ]);

        $this->applyFilters($query, $filters);

        $manifests = $query->get();
        
        $volumeData = [
            'air' => [],
            'sea' => [],
            'trends' => []
        ];

        foreach ($manifests as $manifest) {
            $manifestType = $manifest->getType();
            $totalWeight = $manifest->getTotalWeight();
            $totalVolume = $manifest->getTotalVolume();
            $packageCount = $manifest->packages()->count();

            $metrics = [
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'date' => $manifest->shipment_date ?: $manifest->created_at->toDateString(),
                'package_count' => $packageCount,
                'total_weight' => $totalWeight,
                'total_volume' => $totalVolume,
                'avg_weight_per_package' => $packageCount > 0 ? round($totalWeight / $packageCount, 2) : 0,
                'avg_volume_per_package' => $packageCount > 0 ? round($totalVolume / $packageCount, 3) : 0,
                'weight_to_volume_ratio' => $totalVolume > 0 ? round($totalWeight / $totalVolume, 2) : 0
            ];

            $volumeData[$manifestType][] = $metrics;
            
            // Add to trends data for time series analysis
            $monthKey = Carbon::parse($metrics['date'])->format('Y-m');
            if (!isset($volumeData['trends'][$monthKey])) {
                $volumeData['trends'][$monthKey] = [
                    'air' => ['weight' => 0, 'volume' => 0, 'packages' => 0, 'manifests' => 0],
                    'sea' => ['weight' => 0, 'volume' => 0, 'packages' => 0, 'manifests' => 0]
                ];
            }
            
            $volumeData['trends'][$monthKey][$manifestType]['weight'] += $totalWeight;
            $volumeData['trends'][$monthKey][$manifestType]['volume'] += $totalVolume;
            $volumeData['trends'][$monthKey][$manifestType]['packages'] += $packageCount;
            $volumeData['trends'][$monthKey][$manifestType]['manifests']++;
        }

        return [
            'by_type' => [
                'air' => $this->calculateVolumeStatistics($volumeData['air']),
                'sea' => $this->calculateVolumeStatistics($volumeData['sea'])
            ],
            'trends' => $volumeData['trends'],
            'comparison' => $this->compareManifestTypes($volumeData['air'], $volumeData['sea'])
        ];
    }

    /**
     * Get efficiency metrics and performance benchmarking
     */
    public function getEfficiencyMetrics(array $filters = []): array
    {
        $query = Manifest::query()
            ->select([
                'id', 'name', 'type', 'shipment_date', 'created_at', 'is_open'
            ]);

        $this->applyFilters($query, $filters);

        $manifests = $query->get();
        
        $efficiencyMetrics = [];
        $typeMetrics = ['air' => [], 'sea' => []];

        foreach ($manifests as $manifest) {
            $metrics = $this->calculateManifestEfficiency($manifest);
            $efficiencyMetrics[] = $metrics;
            $typeMetrics[$manifest->getType()][] = $metrics;
        }

        return [
            'overall' => $this->calculateOverallEfficiency($efficiencyMetrics),
            'by_type' => [
                'air' => $this->calculateOverallEfficiency($typeMetrics['air']),
                'sea' => $this->calculateOverallEfficiency($typeMetrics['sea'])
            ],
            'manifests' => $efficiencyMetrics,
            'benchmarks' => $this->calculateBenchmarks($efficiencyMetrics)
        ];
    }

    /**
     * Compare manifest performance and benchmarking
     */
    public function compareManifestTypes(array $airData, array $seaData): array
    {
        $airStats = $this->calculateVolumeStatistics($airData);
        $seaStats = $this->calculateVolumeStatistics($seaData);

        return [
            'air_vs_sea' => [
                'package_count' => [
                    'air_avg' => $airStats['avg_packages_per_manifest'],
                    'sea_avg' => $seaStats['avg_packages_per_manifest'],
                    'difference_pct' => $this->calculatePercentageDifference(
                        $airStats['avg_packages_per_manifest'],
                        $seaStats['avg_packages_per_manifest']
                    )
                ],
                'weight_efficiency' => [
                    'air_avg' => $airStats['avg_weight_per_manifest'],
                    'sea_avg' => $seaStats['avg_weight_per_manifest'],
                    'difference_pct' => $this->calculatePercentageDifference(
                        $airStats['avg_weight_per_manifest'],
                        $seaStats['avg_weight_per_manifest']
                    )
                ],
                'volume_efficiency' => [
                    'air_avg' => $airStats['avg_volume_per_manifest'],
                    'sea_avg' => $seaStats['avg_volume_per_manifest'],
                    'difference_pct' => $this->calculatePercentageDifference(
                        $airStats['avg_volume_per_manifest'],
                        $seaStats['avg_volume_per_manifest']
                    )
                ]
            ],
            'recommendations' => $this->generateEfficiencyRecommendations($airStats, $seaStats)
        ];
    }

    /**
     * Calculate processing metrics for a single manifest
     */
    private function calculateManifestProcessingMetrics(Manifest $manifest): array
    {
        $packages = $manifest->packages;
        $totalPackages = $packages->count();
        
        if ($totalPackages === 0) {
            return [
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'type' => $manifest->getType(),
                'total_packages' => 0,
                'is_completed' => !$manifest->is_open,
                'total_processing_days' => 0,
                'avg_package_processing_days' => 0,
                'completion_rate' => 0
            ];
        }

        $deliveredPackages = $packages->filter(function ($package) {
            return $package->status->value === 'delivered';
        })->count();

        $totalProcessingDays = 0;
        $packagesWithProcessingTime = 0;

        foreach ($packages as $package) {
            $processingDays = $this->calculatePackageProcessingDays($package);
            if ($processingDays > 0) {
                $totalProcessingDays += $processingDays;
                $packagesWithProcessingTime++;
            }
        }

        $manifestProcessingDays = 0;
        if (!$manifest->is_open && $manifest->shipment_date) {
            $manifestProcessingDays = Carbon::parse($manifest->created_at)
                ->diffInDays(Carbon::parse($manifest->shipment_date));
        }

        return [
            'manifest_id' => $manifest->id,
            'manifest_name' => $manifest->name,
            'type' => $manifest->getType(),
            'total_packages' => $totalPackages,
            'delivered_packages' => $deliveredPackages,
            'is_completed' => !$manifest->is_open,
            'total_processing_days' => $manifestProcessingDays,
            'avg_package_processing_days' => $packagesWithProcessingTime > 0 
                ? round($totalProcessingDays / $packagesWithProcessingTime, 2) 
                : 0,
            'completion_rate' => round(($deliveredPackages / $totalPackages) * 100, 2)
        ];
    }

    /**
     * Calculate processing days for a single package
     */
    private function calculatePackageProcessingDays(Package $package): int
    {
        if ($package->status->value !== 'delivered') {
            return 0;
        }

        // Try to get delivery date from status history
        $deliveryDate = null;
        if ($package->statusHistory) {
            $deliveryHistory = $package->statusHistory
                ->where('status', 'delivered')
                ->first();
            
            if ($deliveryHistory) {
                $deliveryDate = $deliveryHistory->created_at;
            }
        }

        // Fallback to package updated_at if no status history
        if (!$deliveryDate) {
            $deliveryDate = $package->updated_at;
        }

        return Carbon::parse($package->created_at)->diffInDays($deliveryDate);
    }

    /**
     * Calculate efficiency metrics for a single manifest
     */
    private function calculateManifestEfficiency(Manifest $manifest): array
    {
        $packages = $manifest->packages()->get();
        $totalPackages = $packages->count();
        
        if ($totalPackages === 0) {
            return [
                'manifest_id' => $manifest->id,
                'manifest_name' => $manifest->name,
                'type' => $manifest->getType(),
                'efficiency_score' => 0,
                'utilization_rate' => 0,
                'data_completeness' => 0
            ];
        }

        // Calculate data completeness
        $packagesWithWeight = $packages->filter(fn($p) => $p->hasWeightData())->count();
        $packagesWithVolume = $packages->filter(fn($p) => $p->hasVolumeData())->count();
        $dataCompleteness = (($packagesWithWeight + $packagesWithVolume) / ($totalPackages * 2)) * 100;

        // Calculate utilization rate (packages vs capacity estimate)
        $totalWeight = $manifest->getTotalWeight();
        $totalVolume = $manifest->getTotalVolume();
        
        // Estimate capacity utilization (simplified calculation)
        $weightUtilization = min(($totalWeight / 1000) * 100, 100); // Assume 1000 lbs capacity
        $volumeUtilization = min(($totalVolume / 100) * 100, 100); // Assume 100 cubic feet capacity
        $utilizationRate = ($weightUtilization + $volumeUtilization) / 2;

        // Calculate overall efficiency score
        $completionRate = $this->calculateManifestCompletionRate($manifest);
        $efficiencyScore = ($completionRate + $dataCompleteness + $utilizationRate) / 3;

        return [
            'manifest_id' => $manifest->id,
            'manifest_name' => $manifest->name,
            'type' => $manifest->getType(),
            'total_packages' => $totalPackages,
            'efficiency_score' => round($efficiencyScore, 2),
            'completion_rate' => round($completionRate, 2),
            'utilization_rate' => round($utilizationRate, 2),
            'data_completeness' => round($dataCompleteness, 2),
            'total_weight' => $totalWeight,
            'total_volume' => $totalVolume
        ];
    }

    /**
     * Calculate completion rate for a manifest
     */
    private function calculateManifestCompletionRate(Manifest $manifest): float
    {
        $totalPackages = $manifest->packages()->count();
        
        if ($totalPackages === 0) {
            return 100; // Empty manifest is considered complete
        }

        $deliveredPackages = $manifest->packages()
            ->where('status', 'delivered')
            ->count();

        return ($deliveredPackages / $totalPackages) * 100;
    }

    /**
     * Calculate volume statistics for a dataset
     */
    private function calculateVolumeStatistics(array $data): array
    {
        if (empty($data)) {
            return [
                'total_manifests' => 0,
                'total_packages' => 0,
                'total_weight' => 0,
                'total_volume' => 0,
                'avg_packages_per_manifest' => 0,
                'avg_weight_per_manifest' => 0,
                'avg_volume_per_manifest' => 0,
                'avg_weight_per_package' => 0,
                'avg_volume_per_package' => 0
            ];
        }

        $totalManifests = count($data);
        $totalPackages = array_sum(array_column($data, 'package_count'));
        $totalWeight = array_sum(array_column($data, 'total_weight'));
        $totalVolume = array_sum(array_column($data, 'total_volume'));

        return [
            'total_manifests' => $totalManifests,
            'total_packages' => $totalPackages,
            'total_weight' => round($totalWeight, 2),
            'total_volume' => round($totalVolume, 3),
            'avg_packages_per_manifest' => round($totalPackages / $totalManifests, 2),
            'avg_weight_per_manifest' => round($totalWeight / $totalManifests, 2),
            'avg_volume_per_manifest' => round($totalVolume / $totalManifests, 3),
            'avg_weight_per_package' => $totalPackages > 0 ? round($totalWeight / $totalPackages, 2) : 0,
            'avg_volume_per_package' => $totalPackages > 0 ? round($totalVolume / $totalPackages, 3) : 0
        ];
    }

    /**
     * Calculate overall efficiency from multiple manifest metrics
     */
    private function calculateOverallEfficiency(array $metrics): array
    {
        if (empty($metrics)) {
            return [
                'avg_efficiency_score' => 0,
                'avg_completion_rate' => 0,
                'avg_utilization_rate' => 0,
                'avg_data_completeness' => 0,
                'total_manifests' => 0
            ];
        }

        $totalManifests = count($metrics);
        $totalEfficiency = array_sum(array_column($metrics, 'efficiency_score'));
        $totalCompletion = array_sum(array_column($metrics, 'completion_rate'));
        $totalUtilization = array_sum(array_column($metrics, 'utilization_rate'));
        $totalDataCompleteness = array_sum(array_column($metrics, 'data_completeness'));

        return [
            'avg_efficiency_score' => round($totalEfficiency / $totalManifests, 2),
            'avg_completion_rate' => round($totalCompletion / $totalManifests, 2),
            'avg_utilization_rate' => round($totalUtilization / $totalManifests, 2),
            'avg_data_completeness' => round($totalDataCompleteness / $totalManifests, 2),
            'total_manifests' => $totalManifests
        ];
    }

    /**
     * Calculate performance benchmarks
     */
    private function calculateBenchmarks(array $metrics): array
    {
        if (empty($metrics)) {
            return [
                'top_performers' => [],
                'improvement_needed' => [],
                'benchmarks' => []
            ];
        }

        // Sort by efficiency score
        usort($metrics, fn($a, $b) => $b['efficiency_score'] <=> $a['efficiency_score']);

        $topPerformers = array_slice($metrics, 0, 5);
        $bottomPerformers = array_slice($metrics, -5);

        $efficiencyScores = array_column($metrics, 'efficiency_score');
        
        return [
            'top_performers' => $topPerformers,
            'improvement_needed' => array_reverse($bottomPerformers),
            'benchmarks' => [
                'excellence_threshold' => round(array_sum(array_slice($efficiencyScores, 0, ceil(count($efficiencyScores) * 0.1))) / ceil(count($efficiencyScores) * 0.1), 2),
                'average_performance' => round(array_sum($efficiencyScores) / count($efficiencyScores), 2),
                'improvement_threshold' => round(array_sum(array_slice($efficiencyScores, -ceil(count($efficiencyScores) * 0.1))) / ceil(count($efficiencyScores) * 0.1), 2)
            ]
        ];
    }

    /**
     * Generate efficiency recommendations
     */
    private function generateEfficiencyRecommendations(array $airStats, array $seaStats): array
    {
        $recommendations = [];

        // Compare package counts
        if ($airStats['avg_packages_per_manifest'] < $seaStats['avg_packages_per_manifest'] * 0.5) {
            $recommendations[] = [
                'type' => 'consolidation',
                'message' => 'Air manifests have significantly fewer packages. Consider consolidating smaller air shipments.',
                'priority' => 'medium'
            ];
        }

        // Compare weight efficiency
        if ($airStats['avg_weight_per_package'] > $seaStats['avg_weight_per_package'] * 1.5) {
            $recommendations[] = [
                'type' => 'routing',
                'message' => 'Heavy packages are being sent via air. Consider routing heavy items through sea freight.',
                'priority' => 'high'
            ];
        }

        // Volume efficiency
        if ($seaStats['avg_volume_per_manifest'] < $airStats['avg_volume_per_manifest']) {
            $recommendations[] = [
                'type' => 'capacity',
                'message' => 'Sea manifests are underutilizing volume capacity. Consider increasing package consolidation.',
                'priority' => 'medium'
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate percentage difference between two values
     */
    private function calculatePercentageDifference(float $value1, float $value2): float
    {
        if ($value2 == 0) {
            return $value1 > 0 ? 100 : 0;
        }

        return round((($value1 - $value2) / $value2) * 100, 2);
    }

    /**
     * Get processing efficiency data for charts
     */
    public function getProcessingEfficiency(array $filters = []): array
    {
        $query = Manifest::query()
            ->select(['id', 'name', 'type', 'shipment_date', 'created_at', 'is_open']);

        $this->applyFilters($query, $filters);

        $manifests = $query->get();
        
        $metrics = [
            ['label' => 'Processing Speed', 'score' => 0],
            ['label' => 'Data Completeness', 'score' => 0],
            ['label' => 'Capacity Utilization', 'score' => 0],
            ['label' => 'Delivery Rate', 'score' => 0],
            ['label' => 'Quality Score', 'score' => 0]
        ];

        $totalManifests = $manifests->count();
        if ($totalManifests === 0) {
            return [
                'metrics' => $metrics,
                'summary' => [
                    'total_manifests' => 0,
                    'avg_efficiency' => 0
                ]
            ];
        }

        $totalProcessingSpeed = 0;
        $totalDataCompleteness = 0;
        $totalUtilization = 0;
        $totalDeliveryRate = 0;
        $totalQuality = 0;

        foreach ($manifests as $manifest) {
            $efficiency = $this->calculateManifestEfficiency($manifest);
            $totalProcessingSpeed += min(100, max(0, 100 - ($efficiency['avg_processing_days'] ?? 15) * 2));
            $totalDataCompleteness += $efficiency['data_completeness'];
            $totalUtilization += $efficiency['utilization_rate'];
            $totalDeliveryRate += $efficiency['completion_rate'];
            $totalQuality += $efficiency['efficiency_score'];
        }

        $metrics[0]['score'] = round($totalProcessingSpeed / $totalManifests, 1);
        $metrics[1]['score'] = round($totalDataCompleteness / $totalManifests, 1);
        $metrics[2]['score'] = round($totalUtilization / $totalManifests, 1);
        $metrics[3]['score'] = round($totalDeliveryRate / $totalManifests, 1);
        $metrics[4]['score'] = round($totalQuality / $totalManifests, 1);

        return [
            'metrics' => $metrics,
            'summary' => [
                'total_manifests' => $totalManifests,
                'avg_efficiency' => round(array_sum(array_column($metrics, 'score')) / count($metrics), 1)
            ]
        ];
    }

    /**
     * Get volume trends data for charts
     */
    public function getVolumeTrends(array $filters = []): array
    {
        $query = Manifest::query()
            ->select(['id', 'name', 'type', 'shipment_date', 'created_at']);

        $this->applyFilters($query, $filters);

        $manifests = $query->orderBy('created_at')->get();
        
        $trends = [];
        $airTrends = [];
        $seaTrends = [];
        
        // Group by week
        $weeklyData = [];
        foreach ($manifests as $manifest) {
            $weekKey = Carbon::parse($manifest->created_at)->startOfWeek()->format('M j');
            $packageCount = $manifest->packages()->count();
            
            if (!isset($weeklyData[$weekKey])) {
                $weeklyData[$weekKey] = [
                    'period' => $weekKey,
                    'volume' => 0,
                    'air_volume' => 0,
                    'sea_volume' => 0
                ];
            }
            
            $weeklyData[$weekKey]['volume'] += $packageCount;
            
            if ($manifest->type === 'air') {
                $weeklyData[$weekKey]['air_volume'] += $packageCount;
            } else {
                $weeklyData[$weekKey]['sea_volume'] += $packageCount;
            }
        }

        $trends = array_values($weeklyData);
        $airTrends = array_map(function($item) {
            return ['period' => $item['period'], 'volume' => $item['air_volume']];
        }, $weeklyData);
        $seaTrends = array_map(function($item) {
            return ['period' => $item['period'], 'volume' => $item['sea_volume']];
        }, $weeklyData);

        return [
            'trends' => $trends,
            'air_trends' => array_values($airTrends),
            'sea_trends' => array_values($seaTrends),
            'summary' => [
                'total_manifests' => $manifests->count(),
                'total_packages' => array_sum(array_column($trends, 'volume')),
                'avg_packages_per_week' => count($trends) > 0 ? round(array_sum(array_column($trends, 'volume')) / count($trends), 1) : 0
            ]
        ];
    }

    /**
     * Get weight analysis data for charts
     */
    public function getWeightAnalysis(array $filters = []): array
    {
        $query = Package::query()
            ->select(['id', 'manifest_id', 'weight', 'length', 'width', 'height'])
            ->whereNotNull('weight')
            ->where('weight', '>', 0);

        if (!empty($filters['manifest_type'])) {
            $query->whereHas('manifest', function($q) use ($filters) {
                $q->where('type', $filters['manifest_type']);
            });
        }

        if (!empty($filters['office_id'])) {
            $query->where('office_id', $filters['office_id']);
        }

        $packages = $query->get();
        
        // Define weight ranges
        $weightRanges = [
            ['range' => '0-5 lbs', 'min' => 0, 'max' => 5],
            ['range' => '5-15 lbs', 'min' => 5, 'max' => 15],
            ['range' => '15-30 lbs', 'min' => 15, 'max' => 30],
            ['range' => '30-50 lbs', 'min' => 30, 'max' => 50],
            ['range' => '50+ lbs', 'min' => 50, 'max' => PHP_INT_MAX]
        ];

        $distribution = [];
        foreach ($weightRanges as $range) {
            $packagesInRange = $packages->filter(function($package) use ($range) {
                return $package->weight >= $range['min'] && $package->weight < $range['max'];
            });

            $distribution[] = [
                'range' => $range['range'],
                'count' => $packagesInRange->count(),
                'total_weight' => round($packagesInRange->sum('weight'), 2)
            ];
        }

        return [
            'weight_distribution' => $distribution,
            'summary' => [
                'total_packages' => $packages->count(),
                'total_weight' => round($packages->sum('weight'), 2),
                'avg_weight' => $packages->count() > 0 ? round($packages->avg('weight'), 2) : 0,
                'heaviest_package' => round($packages->max('weight') ?? 0, 2)
            ]
        ];
    }

    /**
     * Get type comparison data for charts
     */
    public function getTypeComparison(array $filters = []): array
    {
        $query = Manifest::query()
            ->select(['id', 'type'])
            ->selectRaw('COUNT(*) as manifest_count')
            ->groupBy('type');

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['office_id'])) {
            $query->whereHas('packages', function($q) use ($filters) {
                $q->where('office_id', $filters['office_id']);
            });
        }

        $typeData = $query->get();
        
        $comparison = $typeData->map(function($item) {
            return [
                'type' => ucfirst($item->type),
                'count' => $item->manifest_count
            ];
        })->toArray();

        $totalManifests = array_sum(array_column($comparison, 'count'));

        return [
            'comparison' => $comparison,
            'summary' => [
                'total_manifests' => $totalManifests,
                'air_percentage' => $totalManifests > 0 ? 
                    round((collect($comparison)->where('type', 'Air')->first()['count'] ?? 0) / $totalManifests * 100, 1) : 0,
                'sea_percentage' => $totalManifests > 0 ? 
                    round((collect($comparison)->where('type', 'Sea')->first()['count'] ?? 0) / $totalManifests * 100, 1) : 0
            ]
        ];
    }

    /**
     * Get processing times data for charts
     */
    public function getProcessingTimes(array $filters = []): array
    {
        // Define processing stages
        $stages = [
            ['stage' => 'Receipt', 'avg_hours' => 2],
            ['stage' => 'Processing', 'avg_hours' => 24],
            ['stage' => 'Ready', 'avg_hours' => 12],
            ['stage' => 'Delivered', 'avg_hours' => 6]
        ];

        // This would ideally calculate from actual package status history
        // For now, providing estimated values based on typical processing times

        return [
            'processing_stages' => $stages,
            'summary' => [
                'total_avg_hours' => array_sum(array_column($stages, 'avg_hours')),
                'fastest_stage' => collect($stages)->sortBy('avg_hours')->first()['stage'],
                'slowest_stage' => collect($stages)->sortByDesc('avg_hours')->first()['stage']
            ]
        ];
    }

    /**
     * Apply filters to the manifest query
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['manifest_type'])) {
            $query->where('type', $filters['manifest_type']);
        }

        if (!empty($filters['office_id'])) {
            $query->whereHas('packages', function ($q) use ($filters) {
                $q->where('office_id', $filters['office_id']);
            });
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'open') {
                $query->where('is_open', true);
            } elseif ($filters['status'] === 'closed') {
                $query->where('is_open', false);
            }
        }
    }
}