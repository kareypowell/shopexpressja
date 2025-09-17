# Backup System Administrator Guide

## Overview

This guide provides comprehensive information for system administrators on configuring, maintaining, and troubleshooting the ShipSharkLtd backup management system.

## System Requirements

### Server Requirements
- **PHP**: 7.3+ or 8.0+ with required extensions
- **MySQL/MariaDB**: 5.7+ or 10.2+
- **Disk Space**: Minimum 10GB free space for backup storage
- **Memory**: 512MB+ available for backup operations
- **Permissions**: Write access to storage directories

### Required PHP Extensions
- `pdo_mysql` - Database backup operations
- `zip` - File compression and archiving
- `exec` - Command execution for mysqldump
- `file_get_contents` - File operations

## Installation and Setup

### 1. Database Migration

Run the backup system migrations:

```bash
php artisan migrate
```

This creates the following tables:
- `backups` - Backup file records and metadata
- `backup_schedules` - Automated backup configuration
- `restore_logs` - Restoration operation audit trail
- `backup_settings` - System configuration settings

### 2. Configuration Files

#### Environment Variables

Add these variables to your `.env` file:

```env
# Backup Storage Configuration
BACKUP_STORAGE_PATH=storage/app/backups
BACKUP_DATABASE_RETENTION_DAYS=30
BACKUP_FILES_RETENTION_DAYS=14
BACKUP_MAX_FILE_SIZE=2048

# Database Backup Settings
DB_BACKUP_TIMEOUT=300
DB_BACKUP_SINGLE_TRANSACTION=true
DB_BACKUP_ROUTINES=true
DB_BACKUP_TRIGGERS=true

# File Backup Settings
BACKUP_COMPRESSION_LEVEL=6

# Notification Settings
BACKUP_NOTIFICATION_EMAIL=admin@shipsharkltd.com
BACKUP_NOTIFY_ON_SUCCESS=false
BACKUP_NOTIFY_ON_FAILURE=true
```

#### Backup Configuration

The system uses `config/backup.php` for detailed configuration:

```php
return [
    'storage' => [
        'path' => env('BACKUP_STORAGE_PATH', 'storage/app/backups'),
        'max_file_size' => env('BACKUP_MAX_FILE_SIZE', 2048), // MB
    ],
    
    'database' => [
        'timeout' => env('DB_BACKUP_TIMEOUT', 300),
        'single_transaction' => env('DB_BACKUP_SINGLE_TRANSACTION', true),
        'routines' => env('DB_BACKUP_ROUTINES', true),
        'triggers' => env('DB_BACKUP_TRIGGERS', true),
    ],
    
    'files' => [
        'directories' => [
            'storage/app/public/pre-alerts',
            'storage/app/public/receipts',
        ],
        'compression_level' => env('BACKUP_COMPRESSION_LEVEL', 6),
    ],
    
    'retention' => [
        'database_days' => env('BACKUP_DATABASE_RETENTION_DAYS', 30),
        'files_days' => env('BACKUP_FILES_RETENTION_DAYS', 14),
    ],
    
    'notifications' => [
        'email' => env('BACKUP_NOTIFICATION_EMAIL'),
        'notify_on_success' => env('BACKUP_NOTIFY_ON_SUCCESS', false),
        'notify_on_failure' => env('BACKUP_NOTIFY_ON_FAILURE', true),
    ],
];
```

### 3. Directory Permissions

Ensure proper permissions for backup directories:

```bash
# Create backup directory
mkdir -p storage/app/backups

# Set permissions
chmod 755 storage/app/backups
chown www-data:www-data storage/app/backups

# For file backups
chmod 755 storage/app/public/pre-alerts
chmod 755 storage/app/public/receipts
```

### 4. Laravel Scheduler Setup

For automated backups, configure the Laravel scheduler:

```bash
# Add to crontab (crontab -e)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Initial Configuration

Seed the backup settings:

```bash
php artisan db:seed --class=BackupSettingsSeeder
```

## Command Line Operations

### Manual Backup Commands

#### Create Full Backup
```bash
php artisan backup:create
```

#### Database Only Backup
```bash
php artisan backup:create --database
```

#### Files Only Backup
```bash
php artisan backup:create --files
```

#### Custom Named Backup
```bash
php artisan backup:create --name="pre-maintenance-backup"
```

### Restoration Commands

#### Restore Database
```bash
php artisan backup:restore backup-file.sql --database
```

#### Restore Files
```bash
php artisan backup:restore backup-files.zip --files
```

#### Force Restore (Skip Confirmations)
```bash
php artisan backup:restore backup-file.sql --database --force
```

### Maintenance Commands

#### Check Backup Status
```bash
php artisan backup:status
```

#### Clean Old Backups
```bash
php artisan backup:cleanup
```

#### Dry Run Cleanup (Preview Only)
```bash
php artisan backup:cleanup --dry-run
```

#### Health Check
```bash
php artisan backup:health-check
```

## Monitoring and Maintenance

### Log Files

Backup operations are logged in several locations:

1. **Laravel Log**: `storage/logs/laravel.log`
2. **Backup Database**: `backups` table records
3. **Restore Log**: `restore_logs` table records

### Key Metrics to Monitor

#### Storage Usage
- Monitor backup directory size
- Track retention policy effectiveness
- Alert when approaching disk space limits

#### Backup Success Rate
- Monitor failed backup attempts
- Track backup completion times
- Verify backup file integrity

#### System Performance
- Database backup duration
- File backup compression ratios
- Memory usage during operations

### Automated Monitoring

Set up monitoring for:

```bash
# Disk space monitoring
df -h | grep backup

# Backup file count
ls -la storage/app/backups | wc -l

# Recent backup status
php artisan backup:status
```

## Security Configuration

### Access Control

#### Role-Based Permissions

Ensure only administrators can access backup features:

```php
// In your AuthServiceProvider
Gate::define('manage-backups', function ($user) {
    return $user->role->name === 'admin' || $user->role->name === 'superadmin';
});
```

#### File Permissions

Secure backup files:

```bash
# Restrict backup directory access
chmod 700 storage/app/backups

# Secure backup files
find storage/app/backups -type f -exec chmod 600 {} \;
```

### Network Security

#### HTTPS Requirements
- Always use HTTPS for backup management interface
- Secure download links with time-based expiration
- Implement IP restrictions for sensitive environments

#### Firewall Configuration
- Restrict access to backup management URLs
- Consider VPN requirements for backup operations
- Monitor access logs for unauthorized attempts

## Performance Optimization

### Database Backup Optimization

#### Large Database Handling
```bash
# Increase timeout for large databases
DB_BACKUP_TIMEOUT=1800

# Use single transaction for consistency
DB_BACKUP_SINGLE_TRANSACTION=true

# Optimize mysqldump parameters
--single-transaction --routines --triggers --quick --lock-tables=false
```

#### Memory Management
- Monitor PHP memory limits during backup operations
- Consider increasing `memory_limit` for large backups
- Use streaming for large file operations

### File Backup Optimization

#### Compression Settings
```env
# Balance between compression and speed
BACKUP_COMPRESSION_LEVEL=6  # Default (good balance)
BACKUP_COMPRESSION_LEVEL=1  # Fast compression
BACKUP_COMPRESSION_LEVEL=9  # Maximum compression
```

#### Selective Backup
Configure which directories to include:

```php
'files' => [
    'directories' => [
        'storage/app/public/pre-alerts',
        'storage/app/public/receipts',
        // Add other critical directories
    ],
    'exclude_patterns' => [
        '*.tmp',
        '*.log',
        'cache/*',
    ],
],
```

## Troubleshooting

### Common Issues

#### Backup Fails - Permission Denied
```bash
# Check directory permissions
ls -la storage/app/
chmod 755 storage/app/backups
chown www-data:www-data storage/app/backups
```

#### Database Backup Fails
```bash
# Test mysqldump manually
mysqldump -u username -p database_name > test_backup.sql

# Check MySQL user permissions
GRANT SELECT, LOCK TABLES, SHOW VIEW ON database_name.* TO 'backup_user'@'localhost';
```

#### File Backup Fails
```bash
# Check available disk space
df -h

# Verify file permissions
ls -la storage/app/public/
```

#### Restoration Fails
```bash
# Check backup file integrity
file backup-file.sql
head -n 10 backup-file.sql

# Verify MySQL import
mysql -u username -p database_name < backup-file.sql
```

### Error Codes and Solutions

| Error Code | Description | Solution |
|------------|-------------|----------|
| BACKUP_001 | Insufficient disk space | Free up space or increase storage |
| BACKUP_002 | Database connection failed | Check database credentials |
| BACKUP_003 | File permission denied | Fix directory permissions |
| BACKUP_004 | Backup file corrupted | Re-create backup |
| RESTORE_001 | Invalid backup file | Verify file integrity |
| RESTORE_002 | Pre-restore backup failed | Check available space |

### Debug Mode

Enable detailed logging:

```env
LOG_LEVEL=debug
BACKUP_DEBUG=true
```

View detailed logs:

```bash
tail -f storage/logs/laravel.log | grep -i backup
```

## Backup Strategy Recommendations

### Retention Policies

#### Production Environment
- **Database**: 30-90 days retention
- **Files**: 14-30 days retention
- **Critical Backups**: Manual long-term storage

#### Development Environment
- **Database**: 7-14 days retention
- **Files**: 3-7 days retention
- **Focus**: Quick recovery over long-term storage

### Backup Frequency

#### High-Traffic Production
- **Database**: Daily automated backups
- **Files**: Weekly automated backups
- **Manual**: Before major deployments

#### Standard Production
- **Database**: Daily automated backups
- **Files**: Bi-weekly automated backups
- **Manual**: Before system changes

### Disaster Recovery

#### Offsite Storage
- Regularly download critical backups
- Store in geographically separate location
- Test restoration procedures quarterly

#### Recovery Testing
- Monthly restoration tests in staging environment
- Document recovery procedures
- Train staff on restoration processes

## Integration with Existing Systems

### Monitoring Integration

#### Nagios/Zabbix
```bash
# Check backup status script
#!/bin/bash
php artisan backup:status --format=json | jq '.status'
```

#### Slack/Discord Notifications
Configure webhook notifications for backup events:

```php
// In backup notification service
$webhook = env('BACKUP_WEBHOOK_URL');
// Send status updates to team channels
```

### CI/CD Integration

#### Pre-deployment Backups
```yaml
# In your deployment pipeline
before_deploy:
  - php artisan backup:create --name="pre-deploy-$(date +%Y%m%d-%H%M%S)"
```

#### Automated Testing
```bash
# Test backup creation in CI
php artisan backup:create --database
php artisan backup:status
```

## Maintenance Schedule

### Daily Tasks
- Monitor backup completion status
- Check available disk space
- Review error logs

### Weekly Tasks
- Verify automated backup schedules
- Test backup file integrity
- Review retention policy effectiveness

### Monthly Tasks
- Test restoration procedures
- Update backup documentation
- Review security access logs
- Optimize backup performance

### Quarterly Tasks
- Full disaster recovery test
- Review and update backup strategy
- Security audit of backup access
- Performance optimization review

## Support and Escalation

### Internal Support
1. Check system logs and error messages
2. Verify configuration settings
3. Test manual backup operations
4. Review disk space and permissions

### External Support
If issues persist:
1. Gather system information and logs
2. Document error messages and steps to reproduce
3. Contact development team with detailed information
4. Provide access to staging environment if needed

### Emergency Procedures
For critical backup failures:
1. Immediately create manual backup if possible
2. Notify stakeholders of backup system issues
3. Implement temporary backup procedures
4. Escalate to senior technical staff
5. Document incident for post-mortem review