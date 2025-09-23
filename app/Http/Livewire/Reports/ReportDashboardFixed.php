<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ReportDashboardFixed extends Component
{
    public string $activeReportType = 'sales_collections';
    public ?string $reportType = null;
    public ?string $reportTitle = null;
    public array $availableReports = [];
    public array $activeFilters = [];
    public bool $isLoading = false;
    public ?string $error = null;
    public array $reportData = [];
    public string $lastUpdated = '';
    public bool $autoRefresh = false;
    public array $chartData = [];
    public bool $chartsLoaded = false;

    public function mount(?string $reportType = null, ?string $reportTitle = null)
    {
        try {
            // Set report type from parameter if provided
            if ($reportType) {
                $this->reportType = $reportType;
                $this->activeReportType = $this->mapReportType($reportType);
            }
            
            // Set report title
            $this->reportTitle = $reportTitle;
            
            $this->initializeReportTypes();
            $this->lastUpdated = now()->format('M j, Y g:i A');
        } catch (\Exception $e) {
            $this->error = 'Failed to initialize report dashboard. Please refresh the page.';
        }
    }

    protected function mapReportType(string $reportType): string
    {
        $mapping = [
            'sales' => 'sales_collections',
            'manifests' => 'manifest_performance',
            'customers' => 'customer_analytics',
            'financial' => 'financial_summary',
        ];
        
        return $mapping[$reportType] ?? 'sales_collections';
    }

    protected function initializeReportTypes(): void
    {
        $user = Auth::user();
        $this->availableReports = [];

        // Sales & Collections Report
        if ($user->can('report.viewSalesReports')) {
            $this->availableReports['sales_collections'] = [
                'name' => 'Sales & Collections',
                'description' => 'Revenue analysis and outstanding receivables',
                'icon' => 'currency-dollar',
                'color' => 'blue'
            ];
        }

        // Manifest Performance Report
        if ($user->can('report.viewManifestReports')) {
            $this->availableReports['manifest_performance'] = [
                'name' => 'Manifest Performance',
                'description' => 'Shipping efficiency and operational metrics',
                'icon' => 'truck',
                'color' => 'green'
            ];
        }

        // Customer Analytics Report
        if ($user->can('report.viewCustomerReports')) {
            $this->availableReports['customer_analytics'] = [
                'name' => 'Customer Analytics',
                'description' => 'Customer behavior and account analysis',
                'icon' => 'users',
                'color' => 'purple'
            ];
        }

        // Financial Summary Report
        if ($user->can('report.viewFinancialReports')) {
            $this->availableReports['financial_summary'] = [
                'name' => 'Financial Summary',
                'description' => 'Comprehensive financial overview',
                'icon' => 'chart-bar',
                'color' => 'yellow'
            ];
        }

        // Set default report type if current one is not available
        if (!isset($this->availableReports[$this->activeReportType])) {
            $this->activeReportType = array_key_first($this->availableReports) ?? 'sales_collections';
        }
    }

    public function getCurrentBreadcrumbTitle(): string
    {
        return $this->availableReports[$this->activeReportType]['name'] ?? 'Report';
    }

    public function shouldShowComponent(string $componentName): bool
    {
        // For now, show all components
        return true;
    }

    public function getSummaryStatsProperty(): array
    {
        return [
            [
                'label' => 'Total Revenue',
                'value' => '$0.00',
                'color' => 'blue',
                'change' => null
            ],
            [
                'label' => 'Collected',
                'value' => '$0.00',
                'color' => 'green',
                'change' => null
            ],
            [
                'label' => 'Outstanding',
                'value' => '$0.00',
                'color' => 'red',
                'change' => null
            ],
            [
                'label' => 'Collection Rate',
                'value' => '0%',
                'color' => 'purple',
                'change' => null
            ]
        ];
    }

    public function getSortedComponentsProperty(): array
    {
        return ['summary_cards', 'main_chart', 'data_table'];
    }

    public function getTableDataProperty(): array
    {
        return [];
    }

    public function getCurrentReportProperty(): array
    {
        return $this->availableReports[$this->activeReportType] ?? [];
    }

    public function render()
    {
        return view('livewire.reports.report-dashboard-working');
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function refreshReport()
    {
        $this->lastUpdated = now()->format('M j, Y g:i A');
    }

    public function loadChartData()
    {
        $this->chartsLoaded = true;
        $this->chartData = ['sample' => 'data'];
    }
}