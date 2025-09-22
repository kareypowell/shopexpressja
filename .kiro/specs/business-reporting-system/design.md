# Design Document

## Overview

The Business Reporting System will provide comprehensive analytics and reporting capabilities for ShipSharkLtd operations. The system leverages Laravel's existing architecture with Livewire components for reactive interfaces, Chart.js for data visualization, and a service-oriented approach for data processing. The design focuses on performance, scalability, and user experience with real-time data updates, interactive visualizations, and flexible export capabilities.

## Architecture

### System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Presentation  │    │   Business      │    │   Data Access   │
│     Layer       │    │   Logic Layer   │    │     Layer       │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│ Livewire        │    │ Report Services │    │ Models &        │
│ Components      │◄──►│ Analytics       │◄──►│ Repositories    │
│                 │    │ Services        │    │                 │
│ Blade Templates │    │ Export Services │    │ Database        │
│ Chart.js        │    │ Cache Services  │    │ Queries         │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         ▲                       ▲                       ▲
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   User          │    │   Background    │    │   External      │
│   Interface     │    │   Jobs          │    │   Storage       │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│ Interactive     │    │ Report          │    │ File System     │
│ Dashboards      │    │ Generation      │    │ (PDF/CSV)       │
│                 │    │ Cache Warming   │    │                 │
│ Export Controls │    │ Data Processing │    │ Temporary       │
│ Filter Options  │    │ Notifications   │    │ Storage         │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Technology Stack Integration

- **Backend Framework**: Laravel 8.x with existing service patterns
- **Frontend Components**: Livewire 2.x for reactive interfaces
- **Visualization**: Chart.js 4.x (already integrated)
- **Styling**: Tailwind CSS 2.x with existing design system
- **Caching**: Redis/Database caching following existing patterns
- **Export Libraries**: DomPDF for PDFs, Laravel Excel for CSV
- **Background Processing**: Laravel Queue system

## Components and Interfaces

### Core Components

#### 1. Report Management Components

**ReportDashboard (Livewire Component)**
- Main reporting interface with navigation and filters
- Real-time data updates and chart rendering
- Export controls and permission management

**ReportFilters (Livewire Component)**
- Date range selection (preset and custom ranges)
- Manifest type filtering (air/sea)
- Office location filtering
- Customer/user filtering
- Saved filter management

**ReportExporter (Livewire Component)**
- Export format selection (PDF/CSV)
- Background job status tracking
- Download management and notifications

#### 2. Visualization Components

**CollectionsChart (Livewire Component)**
- Interactive charts showing owed vs collected amounts
- Drill-down capabilities by manifest or time period
- Trend analysis and growth indicators

**ManifestPerformanceChart (Livewire Component)**
- Processing time analytics
- Volume and weight trend visualizations
- Efficiency metrics and comparisons

**FinancialAnalyticsChart (Livewire Component)**
- Revenue breakdown by service type
- Customer payment patterns
- Outstanding balance analysis

#### 3. Data Table Components

**ReportDataTable (Livewire Component)**
- Sortable and filterable data tables
- Pagination with performance optimization
- Export integration for table data

### Service Layer Architecture

#### 1. Report Services

**BusinessReportService**
```php
class BusinessReportService
{
    public function generateSalesCollectionsReport(array $filters): array
    public function generateManifestPerformanceReport(array $filters): array
    public function generateCustomerAnalyticsReport(array $filters): array
    public function generateFinancialSummaryReport(array $filters): array
}
```

**ReportDataService**
```php
class ReportDataService
{
    public function getSalesCollectionsData(array $filters): array
    public function getManifestMetrics(array $filters): array
    public function getCustomerStatistics(array $filters): array
    public function getFinancialBreakdown(array $filters): array
}
```

**ReportCacheService**
```php
class ReportCacheService
{
    public function cacheReportData(string $key, array $data, int $ttl): void
    public function getCachedReportData(string $key): ?array
    public function invalidateReportCache(string $pattern): void
    public function warmUpReportCache(array $filters): void
}
```

#### 2. Export Services

**ReportExportService**
```php
class ReportExportService
{
    public function exportToPdf(array $reportData, string $template): string
    public function exportToCsv(array $reportData, array $headers): string
    public function queueExport(string $type, array $data, User $user): string
    public function getExportStatus(string $jobId): array
}
```

**PdfReportGenerator**
```php
class PdfReportGenerator
{
    public function generateSalesReport(array $data): string
    public function generateManifestReport(array $data): string
    public function generateCustomerReport(array $data): string
    public function includeCharts(array $chartData): void
}
```

#### 3. Analytics Services

**SalesAnalyticsService**
```php
class SalesAnalyticsService
{
    public function calculateCollectionRates(array $filters): array
    public function getOutstandingBalances(array $filters): array
    public function analyzePaymentTrends(array $filters): array
    public function getRevenueProjections(array $filters): array
}
```

**ManifestAnalyticsService**
```php
class ManifestAnalyticsService
{
    public function calculateProcessingTimes(array $filters): array
    public function analyzeVolumePatterns(array $filters): array
    public function getEfficiencyMetrics(array $filters): array
    public function compareManifestTypes(array $filters): array
}
```

## Data Models

### Report Configuration Models

**ReportTemplate**
```php
class ReportTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'template_config',
        'default_filters',
        'created_by',
        'is_active'
    ];
    
    protected $casts = [
        'template_config' => 'array',
        'default_filters' => 'array',
        'is_active' => 'boolean'
    ];
}
```

**SavedReportFilter**
```php
class SavedReportFilter extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'report_type',
        'filter_config',
        'is_shared',
        'shared_with_roles'
    ];
    
    protected $casts = [
        'filter_config' => 'array',
        'is_shared' => 'boolean',
        'shared_with_roles' => 'array'
    ];
}
```

**ReportExportJob**
```php
class ReportExportJob extends Model
{
    protected $fillable = [
        'user_id',
        'report_type',
        'export_format',
        'filters',
        'status',
        'file_path',
        'error_message',
        'started_at',
        'completed_at'
    ];
    
    protected $casts = [
        'filters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
}
```

### Extended Existing Models

**Enhanced Package Model Methods**
```php
// Add to existing Package model
public function scopeForReporting($query)
{
    return $query->with(['user', 'manifest', 'office'])
                 ->select(['id', 'user_id', 'manifest_id', 'office_id', 
                          'freight_price', 'customs_duty', 'storage_fee', 
                          'delivery_fee', 'status', 'created_at']);
}

public function getTotalChargesAttribute(): float
{
    return ($this->freight_price ?? 0) + 
           ($this->customs_duty ?? 0) + 
           ($this->storage_fee ?? 0) + 
           ($this->delivery_fee ?? 0);
}
```

**Enhanced CustomerTransaction Model Methods**
```php
// Add to existing CustomerTransaction model
public function scopeForSalesReport($query)
{
    return $query->with(['user'])
                 ->whereIn('type', [self::TYPE_CHARGE, self::TYPE_PAYMENT])
                 ->select(['id', 'user_id', 'type', 'amount', 
                          'reference_type', 'reference_id', 'created_at']);
}

public function scopeCollections($query)
{
    return $query->where('type', self::TYPE_PAYMENT);
}

public function scopeCharges($query)
{
    return $query->where('type', self::TYPE_CHARGE);
}
```

## Error Handling

### Exception Classes

**ReportGenerationException**
```php
class ReportGenerationException extends Exception
{
    public function __construct(string $reportType, string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("Report generation failed for {$reportType}: {$message}", $code, $previous);
    }
}
```

**ExportException**
```php
class ExportException extends Exception
{
    public function __construct(string $format, string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("Export to {$format} failed: {$message}", $code, $previous);
    }
}
```

### Error Handling Strategy

1. **Graceful Degradation**: Show cached data when real-time data fails
2. **User Feedback**: Clear error messages with suggested actions
3. **Logging**: Comprehensive error logging for debugging
4. **Retry Logic**: Automatic retry for transient failures
5. **Fallback Options**: Alternative data sources when primary fails

## Testing Strategy

### Unit Testing

**Service Layer Tests**
- BusinessReportService methods with various filter combinations
- Data calculation accuracy and edge cases
- Cache behavior and invalidation logic
- Export generation and format validation

**Model Tests**
- New model relationships and scopes
- Data aggregation methods
- Query optimization verification

### Integration Testing

**Report Generation Flow**
- End-to-end report generation with real data
- Filter application and data accuracy
- Cache integration and performance
- Export job processing and file generation

**Livewire Component Tests**
- User interaction flows
- Real-time data updates
- Filter state management
- Export trigger and status tracking

### Performance Testing

**Load Testing**
- Large dataset report generation
- Concurrent user report access
- Cache performance under load
- Export queue processing capacity

**Query Optimization**
- Database query performance analysis
- Index usage verification
- N+1 query prevention
- Memory usage optimization

## Security Considerations

### Access Control

**Role-Based Permissions**
```php
// Policy methods for report access
public function viewSalesReports(User $user): bool
public function viewManifestReports(User $user): bool
public function viewCustomerReports(User $user): bool
public function exportReports(User $user): bool
public function viewAllCustomerData(User $user): bool
```

**Data Filtering**
- Automatic data filtering based on user role
- Customer-specific data isolation
- Office-based data access restrictions
- Audit logging for sensitive report access

### Data Protection

**Sensitive Data Handling**
- PII masking in exported reports
- Secure temporary file storage
- Automatic file cleanup after download
- Encrypted data transmission

**Export Security**
- Signed download URLs with expiration
- User authentication for file access
- Audit trail for all exports
- Rate limiting for export requests

## Performance Optimization

### Caching Strategy

**Multi-Level Caching**
```php
// Cache hierarchy
1. Query Result Cache (15 minutes)
2. Aggregated Data Cache (1 hour)
3. Report Template Cache (24 hours)
4. Chart Data Cache (30 minutes)
```

**Cache Invalidation**
- Model observer-based invalidation
- Time-based expiration
- Manual cache clearing for admins
- Selective cache warming

### Database Optimization

**Optimized Queries**
- Eager loading for relationships
- Selective field loading
- Aggregation at database level
- Proper indexing for report queries

**Background Processing**
- Async report generation for large datasets
- Queue-based export processing
- Progress tracking and notifications
- Resource usage monitoring

## Integration Points

### Existing System Integration

**Dashboard Integration**
- Embed key reports in existing admin dashboard
- Shared styling and navigation patterns
- Consistent user experience
- Unified permission system

**Notification Integration**
- Export completion notifications
- Report sharing notifications
- Error alert notifications
- Scheduled report delivery

**Audit Integration**
- Report access logging
- Export activity tracking
- Filter usage analytics
- Performance monitoring

### External System Compatibility

**API Endpoints**
- RESTful API for report data access
- JSON export format support
- Webhook notifications for exports
- Rate limiting and authentication

**Third-Party Integration**
- Business intelligence tool compatibility
- Accounting system data export
- Customer portal integration
- Mobile app data access