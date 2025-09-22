<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Services\SalesAnalyticsService;
use App\Services\ReportCacheService;
use Carbon\Carbon;

class FinancialAnalyticsChart extends Component
{
    public $chartType = 'revenue_breakdown';
    public $dateRange = '30_days';
    public $manifestType = 'all';
    public $officeId = null;
    public $customerSegment = 'all';
    public $drillDownLevel = 'overview';
    public $selectedService = null;
    public $selectedCustomer = null;
    
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
        $this->customerSegment = $filters['customer_segment'] ?? $this->customerSegment;
        
        $this->resetDrillDown();
    }

    public function setChartType($type)
    {
        $this->chartType = $type;
        $this->resetDrillDown();
    }

    public function handleDrillDown($data)
    {
        if (isset($data['service_type'])) {
            $this->selectedService = $data['service_type'];
            $this->drillDownLevel = 'service';
        } elseif (isset($data['customer_id'])) {
            $this->selectedCustomer = $data['customer_id'];
            $this->drillDownLevel = 'customer';
        }
    }

    public function resetDrillDown()
    {
        $this->drillDownLevel = 'overview';
        $this->selectedService = null;
        $this->selectedCustomer = null;
    }

    public function getChartDataProperty()
    {
        $cacheKey = "financial_analytics_chart_{$this->chartType}_{$this->dateRange}_{$this->manifestType}_{$this->officeId}_{$this->customerSegment}_{$this->drillDownLevel}_{$this->selectedService}_{$this->selectedCustomer}";
        
        return $this->cacheService->getCachedReportData($cacheKey) ?: 
               $this->cacheService->cacheReportData($cacheKey, $this->generateChartData(), 900);
    }

    protected function generateChartData()
    {
        $filters = $this->buildFilters();

        switch ($this->chartType) {
            case 'revenue_breakdown':
                return $this->getRevenueBreakdownData($filters);
            case 'customer_patterns':
                return $this->getCustomerPatternsData($filters);
            case 'outstanding_aging':
                return $this->getOutstandingAgingData($filters);
            case 'revenue_trends':
                return $this->getRevenueTrendsData($filters);
            case 'service_performance':
                return $this->getServicePerformanceData($filters);
            default:
                return $this->getRevenueBreakdownData($filters);
        }
    }

    protected function buildFilters()
    {
        $filters = [
            'manifest_type' => $this->manifestType !== 'all' ? $this->manifestType : null,
            'office_id' => $this->officeId,
            'customer_segment' => $this->customerSegment !== 'all' ? $this->customerSegment : null,
        ];

        // Add date range
        $dateRange = $this->getDateRange();
        $filters['start_date'] = $dateRange['start'];
        $filters['end_date'] = $dateRange['end'];

        // Add drill-down filters
        if ($this->drillDownLevel === 'service' && $this->selectedService) {
            $filters['service_type'] = $this->selectedService;
        }

        if ($this->drillDownLevel === 'customer' && $this->selectedCustomer) {
            $filters['customer_id'] = $this->selectedCustomer;
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

    protected function getRevenueBreakdownData($filters)
    {
        $data = $this->salesAnalytics->getRevenueBreakdown($filters);
        
        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_column($data['breakdown'], 'service'),
                'datasets' => [[
                    'data' => array_column($data['breakdown'], 'amount'),
                    'backgroundColor' => [
                        '#3B82F6', // Freight - Blue
                        '#10B981', // Customs - Green
                        '#F59E0B', // Storage - Yellow
                        '#EF4444', // Delivery - Red
                        '#8B5CF6'  // Other - Purple
                    ],
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
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return label + ": " + value + " (" + percentage + "%)";
                            }'
                        ]
                    ]
                ],
                'onClick' => 'function(event, elements) {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const service = this.data.labels[index];
                        window.livewire.emit("chartDrillDown", {service_type: service});
                    }
                }'
            ],
            'summary' => $data['summary']
        ];
    }

    protected function getCustomerPatternsData($filters)
    {
        $data = $this->salesAnalytics->getCustomerPaymentPatterns($filters);
        
        return [
            'type' => 'scatter',
            'data' => [
                'datasets' => [
                    [
                        'label' => 'Customer Payment Patterns',
                        'data' => array_map(function($customer) {
                            return [
                                'x' => $customer['avg_payment_days'],
                                'y' => $customer['total_revenue'],
                                'customer_id' => $customer['customer_id'],
                                'customer_name' => $customer['customer_name']
                            ];
                        }, $data['customers']),
                        'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                        'borderColor' => '#3B82F6',
                        'borderWidth' => 1,
                        'pointRadius' => 6,
                        'pointHoverRadius' => 8
                    ]
                ]
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
                            'title' => 'function(context) {
                                return context[0].raw.customer_name;
                            }',
                            'label' => 'function(context) {
                                const revenue = new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD"
                                }).format(context.parsed.y);
                                return [
                                    "Revenue: " + revenue,
                                    "Avg Payment Days: " + context.parsed.x
                                ];
                            }'
                        ]
                    ]
                ],
                'scales' => [
                    'x' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Average Payment Days'
                        ],
                        'beginAtZero' => true
                    ],
                    'y' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Total Revenue ($)'
                        ],
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
                        const dataIndex = elements[0].index;
                        const customerId = this.data.datasets[0].data[dataIndex].customer_id;
                        window.livewire.emit("chartDrillDown", {customer_id: customerId});
                    }
                }'
            ],
            'summary' => $data['summary']
        ];
    }

    protected function getOutstandingAgingData($filters)
    {
        $data = $this->salesAnalytics->getOutstandingAnalysis($filters);
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($data['aging_buckets'], 'label'),
                'datasets' => [
                    [
                        'label' => 'Outstanding Amount',
                        'data' => array_column($data['aging_buckets'], 'amount'),
                        'backgroundColor' => [
                            '#10B981', // 0-30 days - Green
                            '#F59E0B', // 31-60 days - Yellow
                            '#EF4444', // 61-90 days - Red
                            '#7C2D12'  // 90+ days - Dark Red
                        ],
                        'borderWidth' => 1,
                        'borderColor' => '#ffffff'
                    ],
                    [
                        'label' => 'Customer Count',
                        'data' => array_column($data['aging_buckets'], 'count'),
                        'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                        'borderColor' => '#3B82F6',
                        'borderWidth' => 1,
                        'yAxisID' => 'y1',
                        'type' => 'line'
                    ]
                ]
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
                                if (context.datasetIndex === 0) {
                                    return "Outstanding: " + new Intl.NumberFormat("en-US", {
                                        style: "currency",
                                        currency: "USD"
                                    }).format(context.parsed.y);
                                } else {
                                    return "Customers: " + context.parsed.y;
                                }
                            }'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'title' => [
                            'display' => true,
                            'text' => 'Outstanding Amount ($)'
                        ],
                        'ticks' => [
                            'callback' => 'function(value) {
                                return new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD",
                                    notation: "compact"
                                }).format(value);
                            }'
                        ]
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'title' => [
                            'display' => true,
                            'text' => 'Customer Count'
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

    protected function getRevenueTrendsData($filters)
    {
        $data = $this->salesAnalytics->getRevenueTrends($filters);
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => array_column($data['trends'], 'period'),
                'datasets' => [
                    [
                        'label' => 'Total Revenue',
                        'data' => array_column($data['trends'], 'revenue'),
                        'borderColor' => '#3B82F6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'Collections',
                        'data' => array_column($data['trends'], 'collections'),
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
                        'title' => [
                            'display' => true,
                            'text' => 'Amount ($)'
                        ],
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

    protected function getServicePerformanceData($filters)
    {
        $data = $this->salesAnalytics->getServicePerformance($filters);
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_column($data['services'], 'name'),
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => array_column($data['services'], 'revenue'),
                        'backgroundColor' => '#3B82F6',
                        'yAxisID' => 'y'
                    ],
                    [
                        'label' => 'Profit Margin %',
                        'data' => array_column($data['services'], 'margin'),
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
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'label' => 'function(context) {
                                if (context.datasetIndex === 0) {
                                    return "Revenue: " + new Intl.NumberFormat("en-US", {
                                        style: "currency",
                                        currency: "USD"
                                    }).format(context.parsed.y);
                                } else {
                                    return "Margin: " + context.parsed.y + "%";
                                }
                            }'
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'title' => [
                            'display' => true,
                            'text' => 'Revenue ($)'
                        ],
                        'ticks' => [
                            'callback' => 'function(value) {
                                return new Intl.NumberFormat("en-US", {
                                    style: "currency",
                                    currency: "USD",
                                    notation: "compact"
                                }).format(value);
                            }'
                        ]
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'title' => [
                            'display' => true,
                            'text' => 'Profit Margin (%)'
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
        return view('livewire.reports.financial-analytics-chart', [
            'chartData' => $this->chartData
        ]);
    }
}