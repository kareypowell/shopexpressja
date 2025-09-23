<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Services\SalesAnalyticsService;
use App\Services\ReportCacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CollectionsChart extends Component
{
    public $chartType = 'collections_overview';
    public $dateRange = '30_days';
    public $manifestType = 'all';
    public $officeId = null;
    public $drillDownLevel = 'overview';
    public $selectedManifest = null;
    public $selectedPeriod = null;
    
    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
        'chartDrillDown' => 'handleDrillDown',
        'resetDrillDown' => 'resetDrillDown'
    ];

    protected SalesAnalyticsService $salesAnalytics;
    protected ReportCacheService $cacheService;

    public function boot(
        SalesAnalyticsService $salesAnalytics,
        ReportCacheService $cacheService
    ) {
        $this->salesAnalytics = $salesAnalytics;
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

    public function handleDrillDown($data)
    {
        if (isset($data['manifest_id'])) {
            $this->selectedManifest = $data['manifest_id'];
            $this->drillDownLevel = 'manifest';
        } elseif (isset($data['period'])) {
            $this->selectedPeriod = $data['period'];
            $this->drillDownLevel = 'period';
        }
    }

    public function resetDrillDown()
    {
        $this->drillDownLevel = 'overview';
        $this->selectedManifest = null;
        $this->selectedPeriod = null;
    }

    public function getChartDataProperty()
    {
        $cacheKey = "collections_chart_{$this->chartType}_{$this->dateRange}_{$this->manifestType}_{$this->officeId}_{$this->drillDownLevel}_{$this->selectedManifest}_{$this->selectedPeriod}";
        
        return $this->cacheService->getCachedReportData($cacheKey) ?: 
               $this->cacheService->cacheReportData($cacheKey, $this->generateChartData(), 900);
    }

    protected function generateChartData()
    {
        $filters = $this->buildFilters();

        switch ($this->chartType) {
            case 'collections_overview':
                return $this->getCollectionsOverviewData($filters);
            case 'collections_trend':
                return $this->getCollectionsTrendData($filters);
            case 'outstanding_analysis':
                return $this->getOutstandingAnalysisData($filters);
            case 'payment_patterns':
                return $this->getPaymentPatternsData($filters);
            default:
                return $this->getCollectionsOverviewData($filters);
        }
    }

    protected function buildFilters()
    {
        $filters = [
            'manifest_type' => $this->manifestType !== 'all' ? $this->manifestType : null,
            'office_id' => $this->officeId,
        ];

        // Add date range
        $dateRange = $this->getDateRange();
        $filters['start_date'] = $dateRange['start'];
        $filters['end_date'] = $dateRange['end'];

        // Add drill-down filters
        if ($this->drillDownLevel === 'manifest' && $this->selectedManifest) {
            $filters['manifest_id'] = $this->selectedManifest;
        }

        if ($this->drillDownLevel === 'period' && $this->selectedPeriod) {
            $filters['period'] = $this->selectedPeriod;
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

    protected function getCollectionsOverviewData($filters)
    {
        $data = $this->salesAnalytics->getCollectionsOverview($filters);
        
        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Collected', 'Outstanding'],
                'datasets' => [[
                    'data' => [
                        $data['total_collected'],
                        $data['total_outstanding']
                    ],
                    'backgroundColor' => ['#10B981', '#EF4444'],
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
                                const value = new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD"
                                }).format(context.parsed);
                                return label + ": " + value;
                            }'
                        ]
                    ]
                ],
                'onClick' => 'function(event, elements) {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const label = this.data.labels[index];
                        window.livewire.emit("chartDrillDown", {type: label.toLowerCase()});
                    }
                }'
            ],
            'summary' => [
                'total_owed' => $data['total_owed'],
                'total_collected' => $data['total_collected'],
                'total_outstanding' => $data['total_outstanding'],
                'collection_rate' => $data['collection_rate'],
                'growth_rate' => $data['growth_rate'] ?? 0
            ]
        ];
    }

    protected function getCollectionsTrendData($filters)
    {
        $data = $this->salesAnalytics->getCollectionsTrend($filters);
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => array_column($data['periods'], 'label'),
                'datasets' => [
                    [
                        'label' => 'Amount Owed',
                        'data' => array_column($data['periods'], 'owed'),
                        'borderColor' => '#3B82F6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'Amount Collected',
                        'data' => array_column($data['periods'], 'collected'),
                        'borderColor' => '#10B981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                        'tension' => 0.4
                    ]
                ]
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
                                const label = context.dataset.label || "";
                                const value = new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD"
                                }).format(context.parsed.y);
                                return label + ": " + value;
                            }'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'callback' => 'function(value) {
                                return new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD",
                                    notation: "compact"
                                }).format(value);
                            }'
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

    protected function getOutstandingAnalysisData($filters)
    {
        $data = $this->salesAnalytics->getOutstandingAnalysis($filters);
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($data['aging_buckets'], 'label'),
                'datasets' => [[
                    'label' => 'Outstanding Amount',
                    'data' => array_column($data['aging_buckets'], 'amount'),
                    'backgroundColor' => [
                        '#10B981', // 0-30 days - Green
                        '#F59E0B', // 31-60 days - Yellow
                        '#EF4444', // 61-90 days - Orange
                        '#7C2D12'  // 90+ days - Red
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
                                const value = new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD"
                                }).format(context.parsed.y);
                                return "Outstanding: " + value;
                            }'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'callback' => 'function(value) {
                                return new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD",
                                    notation: "compact"
                                }).format(value);
                            }'
                        ]
                    ]
                ]
            ],
            'summary' => $data['summary']
        ];
    }

    protected function getPaymentPatternsData($filters)
    {
        $data = $this->salesAnalytics->getPaymentPatterns($filters);
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($data['patterns'], 'period'),
                'datasets' => [
                    [
                        'label' => 'Average Days to Payment',
                        'data' => array_column($data['patterns'], 'avg_days'),
                        'backgroundColor' => '#3B82F6',
                        'yAxisID' => 'y'
                    ],
                    [
                        'label' => 'Payment Volume',
                        'data' => array_column($data['patterns'], 'payment_count'),
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
                            'text' => 'Days to Payment'
                        ]
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'title' => [
                            'display' => true,
                            'text' => 'Payment Count'
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

    public function render()
    {
        try {
            $chartData = $this->chartData;
            
            // Log chart rendering for monitoring
            if (empty($chartData) || !isset($chartData['data'])) {
                Log::warning('Collections chart rendered with empty data', [
                    'chart_type' => $this->chartType,
                    'date_range' => $this->dateRange,
                    'manifest_type' => $this->manifestType,
                    'user_id' => auth()->id()
                ]);
            }
            
            return view('livewire.reports.collections-chart', [
                'chartData' => $chartData
            ]);
        } catch (\Exception $e) {
            Log::error('Collections chart render error: ' . $e->getMessage(), [
                'chart_type' => $this->chartType,
                'filters' => [
                    'date_range' => $this->dateRange,
                    'manifest_type' => $this->manifestType,
                    'office_id' => $this->officeId
                ],
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('livewire.reports.collections-chart', [
                'chartData' => null,
                'error' => 'Chart temporarily unavailable'
            ]);
        }
    }
}