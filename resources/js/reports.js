// Reports Dashboard JavaScript
class ReportsDashboard {
    constructor() {
        this.charts = new Map();
        this.autoRefreshInterval = null;
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupEventListeners();
        });
    }

    setupEventListeners() {
        // Listen for Livewire events
        if (typeof Livewire !== 'undefined') {
            // Chart data loaded event
            Livewire.on('chartDataLoaded', (chartData) => {
                this.renderChart(chartData);
            });

            // Auto-refresh events
            window.addEventListener('startAutoRefresh', (event) => {
                this.startAutoRefresh(event.detail.interval);
            });

            window.addEventListener('stopAutoRefresh', () => {
                this.stopAutoRefresh();
            });

            // File download event
            window.addEventListener('downloadFile', (event) => {
                this.downloadFile(event.detail.url, event.detail.filename);
            });

            // Export polling event
            window.addEventListener('startExportPolling', (event) => {
                this.startExportPolling(event.detail.interval);
            });

            // Notification events
            window.addEventListener('notify', (event) => {
                this.showNotification(event.detail.type, event.detail.message);
            });
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    }

    renderChart(chartData) {
        const canvas = document.getElementById('report-main-chart');
        if (!canvas || !chartData) return;

        const ctx = canvas.getContext('2d');
        
        // Destroy existing chart
        if (this.charts.has('main')) {
            this.charts.get('main').destroy();
        }

        // Create new chart based on type
        const config = this.getChartConfig(chartData);
        const chart = new Chart(ctx, config);
        this.charts.set('main', chart);
    }

    renderComponentChart(chartId, chartData) {
        const canvas = document.getElementById(chartId);
        if (!canvas || !chartData) return;

        const ctx = canvas.getContext('2d');
        
        // Destroy existing chart
        if (this.charts.has(chartId)) {
            this.charts.get(chartId).destroy();
        }

        if (chartData && chartData.data) {
            const chart = new Chart(ctx, {
                type: chartData.type,
                data: chartData.data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    ...chartData.options,
                    onClick: (event, elements) => {
                        this.handleChartClick(chartId, chartData.type, elements, chart);
                    }
                }
            });
            this.charts.set(chartId, chart);
        }
    }

    handleChartClick(chartId, chartType, elements, chart) {
        if (elements.length > 0) {
            const index = elements[0].index;
            
            // Determine which Livewire component to call based on chart ID
            let componentCall = null;
            
            if (chartId.includes('collectionsChart')) {
                if (chartType === 'doughnut') {
                    const label = chart.data.labels[index];
                    componentCall = () => Livewire.find(this.getComponentId(chartId)).call('handleDrillDown', {type: label.toLowerCase()});
                } else if (chartType === 'line' || chartType === 'bar') {
                    const period = chart.data.labels[index];
                    componentCall = () => Livewire.find(this.getComponentId(chartId)).call('handleDrillDown', {period: period});
                }
            } else if (chartId.includes('manifestPerformanceChart')) {
                if (chartType === 'doughnut') {
                    const type = chart.data.labels[index];
                    componentCall = () => Livewire.find(this.getComponentId(chartId)).call('handleDrillDown', {manifest_type: type});
                } else if (chartType === 'line' || chartType === 'bar') {
                    const period = chart.data.labels[index];
                    componentCall = () => Livewire.find(this.getComponentId(chartId)).call('handleDrillDown', {period: period});
                }
            } else if (chartId.includes('financialAnalyticsChart')) {
                if (chartType === 'doughnut') {
                    const service = chart.data.labels[index];
                    componentCall = () => Livewire.find(this.getComponentId(chartId)).call('handleDrillDown', {service_type: service});
                } else if (chartType === 'scatter') {
                    const dataIndex = elements[0].index;
                    const customerId = chart.data.datasets[0].data[dataIndex].customer_id;
                    componentCall = () => Livewire.find(this.getComponentId(chartId)).call('handleDrillDown', {customer_id: customerId});
                }
            }

            if (componentCall) {
                componentCall();
            }
        }
    }

    getComponentId(chartId) {
        // Extract component ID from chart ID
        return chartId.replace(/Chart.*$/, '');
    }

    getChartConfig(chartData) {
        const baseConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: chartData.xAxisLabel || 'Date'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: chartData.yAxisLabel || 'Value'
                    }
                }
            }
        };

        switch (chartData.type) {
            case 'collections':
                return {
                    type: 'line',
                    data: chartData.data,
                    options: {
                        ...baseConfig,
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        }
                    }
                };
            case 'manifest_performance':
                return {
                    type: 'bar',
                    data: chartData.data,
                    options: baseConfig
                };
            case 'customer_analytics':
                return {
                    type: 'doughnut',
                    data: chartData.data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            }
                        }
                    }
                };
            case 'financial_summary':
                return {
                    type: 'bar',
                    data: chartData.data,
                    options: {
                        ...baseConfig,
                        scales: {
                            ...baseConfig.scales,
                            y: {
                                ...baseConfig.scales.y,
                                beginAtZero: true
                            }
                        }
                    }
                };
            default:
                return {
                    type: 'line',
                    data: chartData.data,
                    options: baseConfig
                };
        }
    }

    startAutoRefresh(interval) {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
        
        this.autoRefreshInterval = setInterval(() => {
            // Find the report dashboard component and call refresh
            const dashboardComponent = document.querySelector('[wire\\:id]');
            if (dashboardComponent && typeof Livewire !== 'undefined') {
                const componentId = dashboardComponent.getAttribute('wire:id');
                Livewire.find(componentId).call('refreshReport');
            }
        }, interval);
    }

    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }
    }

    downloadFile(url, filename) {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    startExportPolling(interval) {
        if (this.exportPollingInterval) {
            clearInterval(this.exportPollingInterval);
        }
        
        this.exportPollingInterval = setInterval(() => {
            // Find the export component and refresh active exports
            const exportComponent = document.querySelector('[wire\\:id*="report-exporter"]');
            if (exportComponent && typeof Livewire !== 'undefined') {
                const componentId = exportComponent.getAttribute('wire:id');
                Livewire.find(componentId).call('refreshActiveExports');
            }
        }, interval);
    }

    showNotification(type, message) {
        console.log(`${type}: ${message}`);
        // You can integrate with your notification system here
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        }
    }

    cleanup() {
        // Clear intervals
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
        if (this.exportPollingInterval) {
            clearInterval(this.exportPollingInterval);
        }

        // Destroy all charts
        this.charts.forEach(chart => {
            if (chart) {
                chart.destroy();
            }
        });
        this.charts.clear();
    }

    // Public method to initialize charts from components
    initChart(chartId, chartData) {
        setTimeout(() => {
            this.renderComponentChart(chartId, chartData);
        }, 100);
    }

    // Public method to reinitialize charts on component updates
    reinitChart(chartId, chartData) {
        setTimeout(() => {
            this.renderComponentChart(chartId, chartData);
        }, 200);
    }
}

// Initialize the reports dashboard
window.reportsDashboard = new ReportsDashboard();

// Expose methods for Livewire components
window.initReportChart = (chartId, chartData) => {
    window.reportsDashboard.initChart(chartId, chartData);
};

window.reinitReportChart = (chartId, chartData) => {
    window.reportsDashboard.reinitChart(chartId, chartData);
};