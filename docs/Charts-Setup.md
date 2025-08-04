# Charts Setup Documentation

## Overview
The application uses Chart.js for data visualization in the admin dashboard analytics components.

## Dependencies

### Frontend Dependencies (package.json)
- **chart.js**: ^4.5.0 - Main charting library for creating interactive charts

### Installation
Chart.js is installed as an npm dependency and compiled into the main application bundle:

```bash
npm install chart.js
npm run dev  # or npm run production
```

## Implementation

### 1. Chart.js Integration
Chart.js is imported in `resources/js/bootstrap.js` and made available globally:

```javascript
import Chart from 'chart.js/auto';
window.Chart = Chart;
```

### 2. Chart Utilities
Common chart configurations and utilities are defined in `resources/js/charts.js`:
- Default chart configurations
- Color schemes
- Initialization helpers
- Data validation functions

### 3. Chart Components
Charts are implemented in the following Livewire components:

#### Customer Analytics (`resources/views/livewire/customer-analytics.blade.php`)
- Customer Growth Trends (Line Chart)
- Customer Status Distribution (Doughnut Chart)
- Geographic Distribution (Bar Chart)
- Customer Activity Levels (Multiple Charts)

#### Financial Analytics (`resources/views/livewire/financial-analytics.blade.php`)
- Revenue Trends (Line Chart)
- Revenue by Service Type (Doughnut Chart)
- Revenue by Customer Segment (Doughnut Chart)
- Profit Margin Analysis (Line Chart)
- Customer Lifetime Value (Bar Chart)

#### Shipment Analytics (`resources/views/livewire/shipment-analytics.blade.php`)
- Shipment Volume Trends
- Status Distribution
- Performance Metrics

## Chart Initialization Pattern

All charts follow a consistent initialization pattern:

```javascript
function initializeCharts() {
    // Wait for Chart.js to be available
    if (typeof Chart === 'undefined') {
        setTimeout(initializeCharts, 100);
        return;
    }
    
    // Initialize individual charts with error handling
    try {
        // Chart initialization code
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', initializeCharts);
```

## Data Flow

1. **Backend**: Livewire components fetch and process data from models
2. **Data Transformation**: Data is formatted for Chart.js consumption
3. **Frontend**: JavaScript receives data via `@json()` directive
4. **Chart Rendering**: Chart.js creates interactive visualizations

## Configuration

### Chart Colors
Consistent color scheme defined in `resources/js/charts.js`:
- Primary: #3B82F6 (Blue)
- Secondary: #10B981 (Green)
- Warning: #F59E0B (Yellow)
- Danger: #EF4444 (Red)
- And more...

### Default Options
- Responsive: true
- Maintain Aspect Ratio: false
- Legend Position: top
- Tooltips: enabled with custom formatting

## Troubleshooting

### Charts Not Displaying
1. Check browser console for JavaScript errors
2. Verify Chart.js is loaded: `typeof Chart !== 'undefined'`
3. Ensure data is properly formatted
4. Check canvas element exists in DOM

### Performance Issues
1. Use data caching in backend components
2. Limit data points for large datasets
3. Consider chart.js performance optimizations

## Development

### Adding New Charts
1. Create chart data method in Livewire component
2. Add canvas element to view
3. Implement JavaScript initialization
4. Follow existing patterns for consistency

### Updating Chart.js
```bash
npm update chart.js
npm run dev
```

## Production Deployment
Always run production build for optimized assets:
```bash
npm run production
```