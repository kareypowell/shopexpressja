# Audit System Administrator Guide

## Overview

This guide provides comprehensive instructions for system administrators to configure, maintain, and optimize the ShipSharkLtd Audit Logging System. It covers installation, configuration, monitoring, and troubleshooting procedures.

## System Requirements

### Minimum Requirements
- Laravel 8.x or higher
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3
- Redis (recommended for caching)
- Queue worker process

### Recommended Configuration
- Dedicated database partition for audit logs
- SSD storage for audit log tables
- Separate Redis instance for audit caching
- Multiple queue workers for high-volume environments

## Installation and Setup

### Database Migration

Run the audit system migrations:

```bash
# Run audit log migrations
php artisan migrate --path=database/migrations/2025_09_20_182606_create_audit_logs_table.php
php artisan migrate --path=database/migrations/2025_09_20_182648_create_audit_settings_table.php
php artisan migrate --path=database/migrations/2025_09_21_000001_add_audit_performance_indexes.php
```

### Initial Configuration

Seed the default audit settings:

```bash
php artisan db:seed --class=AuditSettingsSeeder
```

### Service Provider Registration

Ensure the AuditServiceProvider is registered in `config/app.php`:

```php
'providers' => [
    // Other providers...
    App\Providers\AuditServiceProvider::class,
],
```

### Queue Configuration

Configure queues for asynchronous audit processing in `.env`:

```env
QUEUE_CONNECTION=redis
AUDIT_QUEUE_NAME=audit-processing
AUDIT_ASYNC_ENABLED=true
```

## Configuration Management

### Audit Settings Interface

Access audit configuration through:
1. Navigate to **Administration** → **Audit Settings**
2. Configure retention policies, alert thresholds, and system behavior

### Environment Variables

Key environment variables for audit system:

```env
# Audit System Configuration
AUDIT_ENABLED=true
AUDIT_ASYNC_ENABLED=true
AUDIT_QUEUE_NAME=audit-processing
AUDIT_CACHE_TTL=3600
AUDIT_MAX_EXPORT_RECORDS=10000

# Retention Policies (in days)
AUDIT_RETENTION_AUTHENTICATION=365
AUDIT_RETENTION_SECURITY_EVENTS=1095
AUDIT_RETENTION_MODEL_CHANGES=730
AUDIT_RETENTION_BUSINESS_ACTIONS=1095
AUDIT_RETENTION_DEFAULT=365

# Security Monitoring
AUDIT_FAILED_LOGIN_THRESHOLD=5
AUDIT_SUSPICIOUS_ACTIVITY_THRESHOLD=10
AUDIT_SECURITY_ALERT_EMAIL=security@shopexpressja.com

# Performance Settings
AUDIT_BATCH_SIZE=100
AUDIT_CACHE_ENABLED=true
AUDIT_INDEX_OPTIMIZATION=true
```

### Database Configuration

#### Audit Log Table Optimization

For high-volume environments, consider table partitioning:

```sql
-- Example monthly partitioning
ALTER TABLE audit_logs PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202501 VALUES LESS THAN (202502),
    PARTITION p202502 VALUES LESS THAN (202503),
    -- Add partitions as needed
);
```

#### Index Optimization

Monitor and optimize indexes based on query patterns:

```sql
-- Additional indexes for specific use cases
CREATE INDEX idx_audit_user_date ON audit_logs (user_id, created_at);
CREATE INDEX idx_audit_model_action ON audit_logs (auditable_type, action, created_at);
CREATE INDEX idx_audit_security ON audit_logs (event_type, ip_address) WHERE event_type = 'security_event';
```

## Retention Policy Management

### Configuring Retention Policies

Set retention periods for different event types:

1. **Authentication Events**: 1 year (recommended)
2. **Security Events**: 3 years (compliance requirement)
3. **Model Changes**: 2 years (business requirement)
4. **Business Actions**: 3 years (audit requirement)

### Automated Cleanup

Configure automated cleanup via cron:

```bash
# Add to crontab
0 2 * * * cd /path/to/application && php artisan audit:cleanup
```

### Manual Cleanup

Run cleanup manually when needed:

```bash
# Clean up expired audit logs
php artisan audit:cleanup

# Clean up specific event types
php artisan audit:cleanup --event-type=authentication

# Dry run to see what would be cleaned
php artisan audit:cleanup --dry-run
```

## Security Monitoring Configuration

### Alert Thresholds

Configure security monitoring thresholds:

```php
// In AuditSettings or environment
'failed_login_threshold' => 5,        // Failed logins before alert
'suspicious_activity_threshold' => 10, // Suspicious actions before alert
'bulk_operation_threshold' => 50,     // Bulk operations before alert
'ip_change_alert' => true,            // Alert on IP address changes
```

### Notification Configuration

Set up security alert notifications:

```php
// config/audit.php
'notifications' => [
    'security_alerts' => [
        'enabled' => true,
        'channels' => ['mail', 'slack'],
        'recipients' => [
            'security@shopexpressja.com',
            'admin@shopexpressja.com'
        ]
    ]
]
```

### IP Whitelist Management

Configure trusted IP addresses:

```php
'trusted_ips' => [
    '192.168.1.0/24',    // Internal network
    '10.0.0.0/8',        // VPN network
    '203.0.113.0/24'     // Office network
]
```

## Performance Optimization

### Caching Configuration

Enable audit caching for better performance:

```php
// config/audit.php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,
    'prefix' => 'audit_',
    'store' => 'redis'
]
```

### Queue Optimization

Configure queue workers for audit processing:

```bash
# Start audit queue workers
php artisan queue:work --queue=audit-processing --tries=3 --timeout=60

# For high-volume environments
php artisan queue:work --queue=audit-processing --tries=3 --timeout=60 --memory=512
```

### Database Performance

#### Connection Pooling

Configure separate database connection for audit logs:

```php
// config/database.php
'connections' => [
    'audit' => [
        'driver' => 'mysql',
        'host' => env('AUDIT_DB_HOST', '127.0.0.1'),
        'database' => env('AUDIT_DB_DATABASE', 'audit_logs'),
        'username' => env('AUDIT_DB_USERNAME', 'forge'),
        'password' => env('AUDIT_DB_PASSWORD', ''),
        // Optimized for write-heavy workload
        'options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION sql_mode="STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO"'
        ]
    ]
]
```

#### Query Optimization

Monitor slow queries and optimize:

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Analyze audit log queries
EXPLAIN SELECT * FROM audit_logs WHERE created_at >= '2025-01-01' AND user_id = 123;
```

## Monitoring and Maintenance

### Health Checks

Regular health check commands:

```bash
# Check audit system status
php artisan audit:status

# Verify audit log integrity
php artisan audit:verify-integrity

# Check storage usage
php artisan audit:storage-report
```

### Performance Monitoring

Monitor key metrics:

1. **Audit Log Volume**: Records per day/hour
2. **Queue Processing**: Backlog and processing time
3. **Database Performance**: Query execution time
4. **Storage Usage**: Disk space consumption
5. **Cache Hit Rate**: Audit cache effectiveness

### Log Analysis

Analyze audit system logs:

```bash
# Check audit processing errors
tail -f storage/logs/laravel.log | grep "audit"

# Monitor queue processing
php artisan queue:monitor audit-processing
```

## Backup and Recovery

### Audit Data Backup

Include audit logs in backup strategy:

```bash
# Backup audit logs table
mysqldump --single-transaction audit_logs > audit_logs_backup.sql

# Backup audit settings
mysqldump --single-transaction audit_settings > audit_settings_backup.sql
```

### Recovery Procedures

Restore audit data when needed:

```bash
# Restore audit logs
mysql < audit_logs_backup.sql

# Verify data integrity after restore
php artisan audit:verify-integrity
```

## Troubleshooting

### Common Issues

#### Audit Logs Not Being Created

1. Check if audit system is enabled:
   ```bash
   php artisan audit:status
   ```

2. Verify queue workers are running:
   ```bash
   php artisan queue:monitor
   ```

3. Check for errors in logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "audit"
   ```

#### Performance Issues

1. **Slow Audit Log Queries**:
   - Check database indexes
   - Analyze query execution plans
   - Consider table partitioning

2. **Queue Backlog**:
   - Increase number of queue workers
   - Optimize queue processing
   - Check for failed jobs

3. **High Memory Usage**:
   - Reduce batch sizes
   - Implement pagination for large exports
   - Optimize caching strategy

#### Storage Issues

1. **Disk Space Running Low**:
   - Run retention cleanup
   - Archive old audit logs
   - Implement compression

2. **Database Performance Degradation**:
   - Optimize indexes
   - Consider read replicas
   - Implement table partitioning

### Diagnostic Commands

```bash
# System status
php artisan audit:status

# Performance report
php artisan audit:performance-report

# Storage analysis
php artisan audit:storage-analysis

# Queue status
php artisan queue:monitor audit-processing

# Failed jobs
php artisan queue:failed
```

## Security Considerations

### Access Control

- Limit audit log access to Super Admin role only
- Implement IP restrictions for audit management
- Use strong authentication for audit interfaces
- Regular access review and audit

### Data Protection

- Encrypt sensitive audit data at rest
- Secure transmission of audit logs
- Implement data masking for PII
- Regular security assessments

### Compliance

- Document retention policies
- Implement audit trail integrity checks
- Regular compliance reporting
- Maintain chain of custody documentation

## Advanced Configuration

### Custom Event Types

Add custom audit event types:

```php
// In AuditService
public function logCustomEvent($eventType, $data) {
    return $this->createAuditLog([
        'event_type' => $eventType,
        'action' => 'custom_action',
        'additional_data' => $data
    ]);
}
```

### Integration with External Systems

Configure external audit log forwarding:

```php
// config/audit.php
'external_forwarding' => [
    'enabled' => true,
    'endpoints' => [
        'siem_system' => 'https://siem.company.com/api/logs',
        'compliance_system' => 'https://compliance.company.com/api/audit'
    ]
]
```

### Custom Retention Policies

Implement custom retention logic:

```php
// Custom retention service
class CustomAuditRetentionService extends AuditRetentionService {
    protected function getRetentionPeriod($eventType, $auditLog) {
        // Custom logic based on event criticality
        if ($auditLog->isCriticalSecurityEvent()) {
            return 2555; // 7 years for critical events
        }
        
        return parent::getRetentionPeriod($eventType, $auditLog);
    }
}
```

## Support and Resources

### Documentation
- User Guide: `docs/AUDIT_LOG_USER_GUIDE.md`
- API Documentation: Generated via `php artisan audit:docs`
- Configuration Reference: `config/audit.php`

### Monitoring Tools
- Laravel Telescope (development)
- New Relic (production monitoring)
- Custom audit dashboards

### Support Contacts
- System Administrator: admin@shopexpressja.com
- Security Team: security@shopexpressja.com
- Development Team: dev@shopexpressja.com

---

*This guide covers standard audit system administration. Consult with the development team for custom configurations or advanced integrations.*