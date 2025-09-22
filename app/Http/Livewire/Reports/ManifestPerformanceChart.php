<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Services\ManifestAnalyticsService;
use App\Services\ReportCacheService;
use Carbon\Carbon;

class ManifestPerformanceChart extends Component
{
    public $chartType = 'processing_efficiency';
    public $dateRange = '30_days';
    public $manifestType = 'all';
    public $officeId = null;
    public $comparisonMode = false;
    public $selectedManifest = null;
    
    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
        'chartDrillDown' => 'handleDrillDown',
        'resetDrillDown' => 'resetDrillDown'
    ];

    protected ManifestAnalyticsService $manifestAnalytics;
    protected ReportCacheService $cacheService;

    public function boot(
        ManifestAnalyticsService $manifestAnalytics,
        ReportCacheService $cacheService
    ) {
        $this->manifestAnalytics = $manifestAnalytics;
        $this->cacheService = $cacheService;
    }

    public function mount($filters = [])
    {
        $this->updateFilters($filters);
    }

    public function updateFilters($filters)
    {
        $this->dateRange = $filters['date_range'] ?? $this->dateRange;
        $this->manifestType = $filters['manifest_type'] ?? $this->manifestType;
        $this->officeId = $filters['office_id'] ?? $this->officeId;
        
        $this->resetDrillDown();
    }

    public function setChartType($type)
    {
        $this->chartType = $type;
        $this->resetDrillDown();
    }

    public function toggleComparison()
    {
        $this->comparisonMode = !$this->comparisonMode;
    }

    public function handleDrillDown($data)
    {
        if (isset($data['manifest_id'])) {
            $this->selectedManifest = $data['manifest_id'];
        }
    }

    public function resetDrillDown()
    {
        $this->selectedManifest = null;
    }

    public function getChartDataProperty()
    {
        $cacheKey = "manifest_performance_chart_{$this->chartType}_{$this->dateRange}_{$this->manifestType}_{$this->officeId}_{$this->comparisonMode}_{$this->selectedManifest}";
        
        return $this->cacheService->getCachedReportData($cacheKey) ?: 
               $this->cacheService->cacheReportData($cacheKey, $this->generateChartData(), 900);
    }

    protected function generateChartData()
    {
        $filters = $this->buildFilters();

        switch ($this->chartType) {
            case 'processing_efficiency':
                return $this->getProcessingEfficiencyData($filters);
            case 'volume_trends':
                return $this->getVolumeTrendsData($filters);
            case 'weight_analysis':
                return $this->getWeightAnalysisData($filters);
            case 'type_comparison':
                return $this->getTypeComparisonData($filters);
            case 'processing_times':
                return $this->getProcessingTimesData($filters);
            default:
                return $this->getProcessingEfficiencyData($filters);
        }
    }

    protected function buildFilters()
    {
        $filters = [
            'manifest_type' => $this->manifestType !== 'all' ? $this->manifestType : null,
            'office_id' => $this->officeId,
            'comparison_mode' => $this->comparisonMode
        ];

        // Add date range
        $dateRange = $this->getDateRange();
        $filters['start_date'] = $dateRange['start'];
        $filters['end_date'] = $dateRange['end'];

        // Add drill-down filters
        if ($this->selectedManifest) {
            $filters['manifest_id'] = $this->selectedManifest;
        }

        return $filters;
    }

    protected function getDateRange()
    {
        $end = Carbon::now();
        
        switch ($this->dateRange) {
            case '7_days':
                $start = $end->copy()->subDays(7);
                break;
            case '30_days':
                $start = $end->copy()->subDays(30);
                break;
            case '90_days':
                $start = $end->copy()->subDays(90);
                break;
            case '1_year':
                $start = $end->copy()->subYear();
                break;
            default:
                $start = $end->copy()->subDays(30);
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    protected function getProcessingEfficiencyData($filters)
    {
        $data = $this->manifestAnalytics->getProcessingEfficiency($filters);
        
        return [
            'type' => 'radar',
            'data' => [
                'labels' => array_column($data['metrics'], 'label'),
                'datasets' => [[
                    'label' => 'Current Period',
                    'data' => array_column($data['metrics'], 'score'),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 2,
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2
                ]]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'top'
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'label' => 'function(context) {
                                return context.dataset.label + ": " + context.parsed.r + "%";
                            }'
                        ]
                    ]
                ],
                'scales' => [
                    'r' => [
                        'beginAtZero' => true,
                        'max' => 100,
                        'ticks' => [
                            'stepSize' => 20
                        ]
                    ]
                ]
            ],
            'summary' => $data['summary']
        ];
    }

    protected function getVolumeTrendsData($filters)
    {
        $data = $this->manifestAnalytics->getVolumeTrends($filters);
        
        $datasets = [];
        
        if ($this->comparisonMode) {
            $datasets = [
                [
                    'label' => 'Air Freight',
                    'data' => array_column($data['air_trends'], 'volume'),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4
                ],
                [
                    'label' => 'Sea Freight',
                    'data' => array_column($data['sea_trends'], 'volume'),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4
                ]
            ];
        } else {
            $datasets = [[
                'label' => 'Package Volume',
                'data' => array_column($data['trends'], 'volume'),
                'borderColor' => '#3B82F6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'fill' => true,
                'tension' => 0.4
            ]];
        }
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => array_column($data['trends'], 'period'),
                'datasets' => $datasets
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => [
                    'intersect' => false,
                    'mode' => 'index'
                ],
                'plugins' => [
                    'legend' => [
                        'position' => 'top'
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'label' => 'function(context) {
                                return context.dataset.label + ": " + context.parsed.y + " packages";
                            }'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => [
                            'display' => true,
                            'text' => 'Package Count'
                        ]
                    ]
                ],
                'onClick' => 'function(event, elements) {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const period = this.data.labels[index];
                        window.livewire.emit("chartDrillDown", {period: period});
                    }
                }'
            ],
            'summary' => $data['summary']
        ];
    }

    protected function getWeightAnalysisData($filters)
    {
        $data = $this->manifestAnalytics->getWeightAnalysis($filters);
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($data['weight_distribution'], 'range'),
                'datasets' => [
                    [
                        'label' => 'Package Count',
                        'data' => array_column($data['weight_distribution'], 'count'),
                        'backgroundColor' => '#3B82F6',
                        'yAxisID' => 'y'
                    ],
                    [
                        'label' => 'Total Weight (lbs)',
                        'data' => array_column($data['weight_distribution'], 'total_weight'),
                        'backgroundColor' => '#10B981',
                        'yAxisID' => 'y1',
                        'type' => 'line',
                        'borderColor' => '#10B981',
                        'tension' => 0.4
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'top'
                    ]
                ],
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'title' => [
                            'display' => true,
                            'text' => 'Package Count'
                        ]
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'title' => [
                            'display' => true,
                            'text' => 'Total Weight (lbs)'
                        ],
                        'grid' => [
                            'drawOnChartArea' => false
                        ]
                    ]
                ]
            ],
            'summary' => $data['summary']
        ];
    }

    protected function getTypeComparisonData($filters)
    {
        $data = $this->manifestAnalytics->getTypeComparison($filters);
        
        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_column($data['comparison'], 'type'),
                'datasets' => [[
                    'data' => array_column($data['comparison'], 'count'),
                    'backgroundColor' => ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff'
                ]]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom'
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'label' => 'function(context) {
                                const label = context.label || "";
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ": " + value + " (" + percentage + "%)";
                            }'
                        ]
                    ]
                ],
                'onClick' => 'function(event, elements) {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const type = this.data.labels[index];
                        window.livewire.emit("chartDrillDown", {manifest_type: type});
                    }
                }'
            ],
            'summary' => $data['summary']
        ];
    }

    protected function getProcessingTimesData($filters)
    {
        $data = $this->manifestAnalytics->getProcessingTimes($filters);
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($data['processing_stages'], 'stage'),
                'datasets' => [[
                    'label' => 'Average Processing Time (hours)',
                    'data' => array_column($data['processing_stages'], 'avg_hours'),
                    'backgroundColor' => [
                        '#10B981', // Receipt - Green
                        '#F59E0B', // Processing - Yellow
                        '#3B82F6', // Ready - Blue
                        '#EF4444'  // Delivered - Red
                    ],
                    'borderWidth' => 1,
                    'borderColor' => '#ffffff'
                ]]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'label' => 'function(context) {
                                return "Average: " + context.parsed.y + " hours";
                            }'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => [
                            'display' => true,
                            'text' => 'Hours'
                        ]
                    ]
                ]
            ],
            'summary' => $data['summary']
        ];
    }

    public function render()
    {
        return view('livewire.reports.manifest-performance-chart', [
            'chartData' => $this->chartData
        ]);
    }
}