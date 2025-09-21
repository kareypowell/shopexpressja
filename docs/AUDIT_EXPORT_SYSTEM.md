# Audit Export and Reporting System

## Overview

The Audit Export and Reporting System provides comprehensive export and reporting capabilities for audit logs in the ShipSharkLtd application. This system allows administrators to export audit data in multiple formats and generate compliance reports for regulatory purposes.

## Features

### Export Formats
- **CSV Export**: Comma-separated values format for data analysis and spreadsheet applications
- **PDF Export**: Professional reports suitable for compliance documentation and archival

### Export Capabilities
- **Filtered Exports**: Apply current filters to exported data
- **Comprehensive Data**: Includes all audit log fields with proper formatting
- **Special Character Handling**: Properly escapes CSV special characters
- **Large Dataset Support**: Handles large volumes of audit data efficiently

### Compliance Reporting
- **Statistical Analysis**: Comprehensive statistics and breakdowns
- **Security Analysis**: Focused analysis of security events and patterns
- **Event Type Breakdown**: Detailed categorization of audit events
- **Professional Formatting**: Compliance-ready PDF reports

### Scheduled Reports
- **Automated Generation**: Schedule reports to run automatically
- **Multiple Frequencies**: Daily, weekly, and monthly scheduling options
- **Email Delivery**: Automatic email delivery to specified recipients
- **Configurable Filters**: Apply specific filters to scheduled reports

### Export Templates
- **Reusable Configurations**: Save export configurations for repeated use
- **Custom Field Selection**: Choose specific fields to include in exports
- **Filter Presets**: Save commonly used filter combinations

## Usage

### Manual Export

1. Navigate to **Administration > Audit Logs**
2. Apply desired filters to narrow down the data
3. Click the **Export** button
4. Choose your preferred format (CSV or PDF)
5. Click **Export** to generate the file
6. A download link will appear at the top of the page
7. Click the **Download** button to save the file to your computer

### Compliance Reports

1. Navigate to **Administration > Audit Logs**
2. Apply filters for the desired time period and criteria
3. Click **Compliance Report**
4. The system will generate a comprehensive PDF report
5. A download link will appear at the top of the page
6. Click the **Download** button to save the report

### Scheduled Reports

Scheduled reports can be configured programmatically using the `AuditExportService`:

```php
use App\Services\AuditExportService;

$exportService = new AuditExportService();

$configuration = [
    'name' => 'weekly_security_report',
    'frequency' => 'weekly',
    'filters' => ['event_type' => 'security_event'],
    'format' => 'pdf',
    'recipients' => ['admin@example.com', 'security@example.com'],
];

$exportService->scheduleReport($configuration);
```

### Export Templates

Create reusable export templates:

```php
$templateConfiguration = [
    'fields' => ['id', 'created_at', 'event_type', 'action', 'user_id'],
    'filters' => ['event_type' => 'authentication'],
    'format' => 'csv',
    'options' => ['include_headers' => true],
];

$exportService->createExportTemplate('auth_events_template', $templateConfiguration);
```

## Command Line Interface

### Generate Scheduled Reports

Run scheduled reports manually:

```bash
php artisan audit:generate-scheduled-reports
```

This command:
- Checks for scheduled reports that are due to run
- Generates reports in the specified format
- Sends reports to configured recipients via email
- Updates the next run time for each report

### Schedule the Command

The command is automatically scheduled to run hourly. You can modify the schedule in `app/Console/Kernel.php`:

```php
$schedule->command('audit:generate-scheduled-reports')
    ->hourly()
    ->withoutOverlapping(15)
    ->runInBackground();
```

## File Storage

### Export Files
- **CSV Files**: Stored temporarily for download
- **PDF Reports**: Stored in `storage/app/public/audit-reports/`
- **Scheduled Reports**: Stored in `storage/app/public/scheduled-reports/`

### File Cleanup
Consider implementing a cleanup strategy for old export files to manage storage space.

## Security and Permissions

### Access Control
- Only **superadmin** users can access export functionality
- Export permissions are enforced through the `AuditLogPolicy`
- All export activities are logged in the audit system

### Data Protection
- Exported files contain sensitive audit information
- Files should be handled according to organizational data security policies
- Email delivery uses secure transmission methods

## API Reference

### AuditExportService Methods

#### `exportToCsv(Collection $auditLogs, array $filters = []): string`
Exports audit logs to CSV format.

#### `exportToPdf(Collection $auditLogs, array $filters = [], array $options = []): string`
Exports audit logs to PDF format and returns the file path.

#### `generateComplianceReport(array $filters = [], array $options = []): array`
Generates comprehensive compliance report data.

#### `createExportTemplate(string $templateName, array $configuration): bool`
Creates a reusable export template.

#### `scheduleReport(array $configuration): bool`
Schedules a report for automatic generation.

#### `getExportTemplates(): Collection`
Retrieves all available export templates.

#### `getScheduledReports(): Collection`
Retrieves all active scheduled reports.

## Email Templates

The system uses the `emails.scheduled-audit-report` template for scheduled report delivery. The template includes:
- Report metadata (name, generation time, format)
- Attachment information
- Security and handling guidelines
- Company branding and contact information

## Configuration

### Export Settings
Export settings are managed through the `AuditSetting` model:
- Allowed export formats
- File size limits
- Retention policies
- Email configuration

### PDF Styling
PDF reports use custom CSS styling defined in the report template. The styling includes:
- Company branding
- Professional layout
- Responsive design elements
- Print-friendly formatting

## Troubleshooting

### Common Issues

1. **Large Export Timeouts**
   - Increase PHP execution time limits
   - Consider implementing chunked exports for very large datasets

2. **PDF Generation Errors**
   - Ensure DomPDF is properly installed
   - Check for memory limits when generating large reports

3. **Email Delivery Failures**
   - Verify SMTP configuration
   - Check recipient email addresses
   - Review email size limits

### Performance Optimization

- Use database indexes for efficient filtering
- Implement caching for frequently accessed data
- Consider background job processing for large exports

## Future Enhancements

- Excel export format support
- Advanced filtering options
- Custom report templates
- Real-time export progress tracking
- Bulk export operations
- Integration with external reporting tools