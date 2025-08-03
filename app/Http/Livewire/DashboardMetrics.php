<?php

namespace App\Http\Livewire;

use App\Services\DashboardAnalyticsService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class DashboardMetrics extends Component
{
    public array $filters = [];
    public bool $isLoading = true;
    public array $metrics = [
        'customers' => [
            'total' => 0,
            'active' => 0,
            'new_this_period' => 0,
            'growth_percentage' => 0,
            'inactive' => 0,
        ],
        'packages' => [
            'total' => 0,
            'in_transit' => 0,
            'delivered' => 0,
            'delayed' => 0,
            'pending' => 0,
            'processing_time_avg' => 0,
            'status_distribution' => [],
        ],
        'revenue' => [
            'current_period' => 0,
            'previous_period' => 0,
            'growth_percentage' => 0,
            'average_order_value' => 0,
            'total_orders' => 0,
        ]
    ];
    public ?string $error = null;

    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
        'refreshMetrics' => 'loadMetrics'
    ];

    protected DashboardAnalyticsService $analyticsService;

    public function boot(DashboardAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function mount(array $filters = [])
    {
        $this->filters = array_merge([
            'date_range' => '30',
            'custom_start' => null,
            'custom_end' => null,
        ], $filters);
        
        $this->loadMetrics();
    }

    public function updateFilters(array $filters)
    {
        $this->filters = $filters;
        $this->loadMetrics();
    }

    public function loadMetrics()
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            // Load customer metrics
            $customerMetrics = $this->analyticsService->getCustomerMetrics($this->filters);
            $this->metrics['customers'] = array_merge($this->metrics['customers'], $customerMetrics);
            
            // Load package/shipment metrics
            $packageMetrics = $this->analyticsService->getShipmentMetrics($this->filters);
            $this->metrics['packages'] = array_merge($this->metrics['packages'], $packageMetrics);
            
            // Load financial metrics
            $revenueMetrics = $this->analyticsService->getFinancialMetrics($this->filters);
            $this->metrics['revenue'] = array_merge($this->metrics['revenue'], $revenueMetrics);

        } catch (\Exception $e) {
            Log::error('Dashboard metrics loading failed', [
                'error' => $e->getMessage(),
                'filters' => $this->filters
            ]);
            
            $this->error = 'Failed to load dashboard metrics. Please try again.';
        } finally {
            $this->isLoading = false;
        }
    }

    public function refreshMetrics()
    {
        $this->loadMetrics();
    }

    /**
     * Get formatted percentage change with sign
     */
    public function getFormattedPercentage(float $percentage): string
    {
        $sign = $percentage >= 0 ? '+' : '';
        return $sign . number_format($percentage, 1) . '%';
    }

    /**
     * Get trend direction for styling
     */
    public function getTrendDirection(float $percentage): string
    {
        if ($percentage > 0) {
            return 'up';
        } elseif ($percentage < 0) {
            return 'down';
        }
        return 'neutral';
    }

    /**
     * Get CSS classes for trend indicators
     */
    public function getTrendClasses(float $percentage): string
    {
        $direction = $this->getTrendDirection($percentage);
        
        switch ($direction) {
            case 'up':
                return 'text-green-600 bg-green-100';
            case 'down':
                return 'text-red-600 bg-red-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    }

    /**
     * Get trend icon for display
     */
    public function getTrendIcon(float $percentage): string
    {
        $direction = $this->getTrendDirection($percentage);
        
        switch ($direction) {
            case 'up':
                return '↗';
            case 'down':
                return '↘';
            default:
                return '→';
        }
    }

    /**
     * Format currency values
     */
    public function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    /**
     * Format large numbers with K/M suffixes
     */
    public function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    /**
     * Get period label based on filters
     */
    public function getPeriodLabel(): string
    {
        if (isset($this->filters['custom_start']) && isset($this->filters['custom_end'])) {
            return 'Custom Period';
        }
        
        $days = $this->filters['date_range'] ?? 30;
        
        switch ($days) {
            case '7':
                return 'Last 7 Days';
            case '30':
                return 'Last 30 Days';
            case '90':
                return 'Last 90 Days';
            case '365':
                return 'Last Year';
            default:
                return "Last {$days} Days";
        }
    }

    public function render()
    {
        return view('livewire.dashboard-metrics');
    }
}