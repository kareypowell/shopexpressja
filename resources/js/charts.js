/**
 * Chart.js utilities and initialization functions
 */

// Chart.js default configuration
Chart.defaults.responsive = true;
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.plugins.legend.position = 'top';

// Common chart colors
const chartColors = {
    primary: '#3B82F6',
    secondary: '#10B981', 
    warning: '#F59E0B',
    danger: '#EF4444',
    info: '#06B6D4',
    success: '#22C55E',
    purple: '#8B5CF6',
    pink: '#EC4899',
    gray: '#6B7280'
};

// Chart initialization helper
window.initializeChart = function(canvasId, config) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.warn(`Canvas element with id "${canvasId}" not found`);
        return null;
    }

    try {
        return new Chart(canvas.getContext('2d'), config);
    } catch (error) {
        console.error(`Error initializing chart "${canvasId}":`, error);
        return null;
    }
};

// Chart data validation helper
window.validateChartData = function(data, chartType = 'line') {
    if (!data || (Array.isArray(data) && data.length === 0)) {
        return false;
    }
    
    if (chartType === 'line' || chartType === 'bar') {
        return data.labels && data.datasets && data.datasets.length > 0;
    }
    
    if (chartType === 'doughnut' || chartType === 'pie') {
        return data.labels && data.datasets && data.datasets[0] && data.datasets[0].data;
    }
    
    return true;
};

// Common chart configurations
window.chartConfigs = {
    line: {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
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
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Value'
                    }
                }
            }
        }
    },
    
    doughnut: {
        type: 'doughnut',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    },
    
    bar: {
        type: 'bar',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Value'
                    }
                }
            }
        }
    }
};

// Export colors for use in other files
window.chartColors = chartColors;

console.log('Charts.js loaded successfully');