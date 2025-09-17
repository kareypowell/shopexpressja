# Backup System Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the ShipSharkLtd backup management system in production environments. It covers installation, configuration, security hardening, and operational procedures.

## Pre-Deployment Checklist

### System Requirements

- [ ] **Server Specifications**
  - Minimum 4 vCPUs, 16GB RAM, 100GB+ storage
  - Additional storage for backup retention (calculate based on data size)
  - Network connectivity for offsite backup transfers

- [ ] **Software Requirements**
  - PHP 7.3+ or 8.0+ with required extensions
  - MySQL/MariaDB 5.7+ or 10.2+
  - Laravel 8.x framework
  - Sufficient disk space (minimum 50GB for backups)

- [ ] **PHP Extensions**
  - `pdo_mysql` - Database operations
  - `zip` - File compression
  - `exec` - Command execution
  - `file_get_contents` - File operations

- [ ] **Permissions**
  - Write access to storage directories
  - Execute permissions for mysqldump
  - Cron job configuration access

### Security Requirements

- [ ] **Access Control**
  - Role-based authentication system
  - HTTPS/TLS encryption
  - Firewall configuration
  - VPN access (recommended for production)

- [ ] **File Security**
  - Secure backup file storage
  - Encrypted backup files (optional)
  - Access logging and monitoring

## Installation Steps

### Step 1: Database Migration

Run the backup system database migrations:

```bash
# Navigate to application directory
cd /var/www/html/your-application

# Run backup system migrations
php artisan migrate --path=database/migrations/2025_09_16_183905_create_backups_table.php
php artisan migrate --path=database/migrations/2025_09_16_183940_create_backup_schedules_table.php
php artisan migrate --path=database/migrations/2025_09_16_184021_create_restore_logs_table.php
php artisan migrate --path=database/migrations/2025_09_17_111235_create_backup_settings_table.php
php artisan migrate --path=database/migrations/2025_09_17_124905_update_backups_table_file_path_column.php

# Verify migrations
php artisan migrate:status | grep backup
```

### Step 2: Environment Configuration

Create or update your production `.env` file with backup configuration:

```bash
# Backup Storage Configuration
BACKUP_STORAGE_PATH=storage/app/backups
BACKUP_DATABASE_RETENTION_DAYS=30
BACKUP_FILES_RETENTION_DAYS=14
BACKUP_MAX_FILE_SIZE=2048

# Database Backup Settings
DB_BACKUP_TIMEOUT=600
DB_BACKUP_SINGLE_TRANSACTION=true
DB_BACKUP_ROUTINES=true
DB_BACKUP_TRIGGERS=true

# File Backup Settings
BACKUP_COMPRESSION_LEVEL=6

# Notification Settings
BACKUP_NOTIFICATION_EMAIL=admin@yourcompany.com
BACKUP_NOTIFY_ON_SUCCESS=false
BACKUP_NOTIFY_ON_FAILURE=true

# Security Settings
BACKUP_DOWNLOAD_EXPIRY=3600
BACKUP_MAX_DOWNLOAD_ATTEMPTS=3
```

### Step 3: Directory Setup and Permissions

Create backup directories with proper permissions:

```bash
# Create backup directory structure
mkdir -p storage/app/backups
mkdir -p storage/app/backups/database
mkdir -p storage/app/backups/files
mkdir -p storage/app/backups/temp

# Set ownership (adjust user/group as needed)
chown -R www-data:www-data storage/app/backups

# Set secure permissions
chmod 750 storage/app/backups
chmod 750 storage/app/backups/database
chmod 750 storage/app/backups/files
chmod 750 storage/app/backups/temp

# Verify permissions
ls -la storage/app/backups/
```

### Step 4: Database User Configuration

Create a dedicated database user for backup operations:

```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create backup user
CREATE USER 'backup_user'@'localhost' IDENTIFIED BY 'secure_backup_password';

-- Grant necessary permissions
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON your_database.* TO 'backup_user'@'localhost';

-- Grant PROCESS privilege for consistent backups
GRANT PROCESS ON *.* TO 'backup_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Test the user
EXIT;
mysql -u backup_user -p your_database
```

Update your `.env` file with backup user credentials:

```env
# Backup Database Configuration
DB_BACKUP_USERNAME=backup_user
DB_BACKUP_PASSWORD=secure_backup_password
```

### Step 5: Laravel Scheduler Configuration

Configure the Laravel scheduler for automated backups:

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler (replace path with your actual path)
* * * * * cd /var/www/html/your-application && php artisan schedule:run >> /dev/null 2>&1

# Verify cron is running
systemctl status cron
```

### Step 6: Initialize Backup Settings

Seed the backup system with default settings:

```bash
# Run backup settings seeder
php artisan db:seed --class=BackupSettingsSeeder

# Verify settings were created
php artisan tinker
>>> App\Models\BackupSetting::all();
>>> exit
```

### Step 7: Test Installation

Perform comprehensive testing of the backup system:

```bash
# Test database backup
php artisan backup:create --database
echo "Database backup test: $?"

# Test file backup
php artisan backup:create --files
echo "File backup test: $?"

# Test full backup
php artisan backup:create
echo "Full backup test: $?"

# Check backup status
php artisan backup:status

# List created backups
ls -la storage/app/backups/
```

## Configuration

### Production Configuration File

Create or update `config/backup.php`:

```php
<?php

return [
    'storage' => [
        'path' => env('BACKUP_STORAGE_PATH', 'storage/app/backups'),
        'max_file_size' => env('BACKUP_MAX_FILE_SIZE', 2048), // MB
        'temp_path' => env('BACKUP_TEMP_PATH', 'storage/app/backups/temp'),
    ],
    
    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_BACKUP_USERNAME', env('DB_USERNAME')),
        'password' => env('DB_BACKUP_PASSWORD', env('DB_PASSWORD')),
        'timeout' => env('DB_BACKUP_TIMEOUT', 600),
        'single_transaction' => env('DB_BACKUP_SINGLE_TRANSACTION', true),
        'routines' => env('DB_BACKUP_ROUTINES', true),
        'triggers' => env('DB_BACKUP_TRIGGERS', true),
        'add_drop_table' => true,
        'add_locks' => true,
        'extended_insert' => true,
    ],
    
    'files' => [
        'directories' => [
            'storage/app/public/pre-alerts',
            'storage/app/public/receipts',
            'storage/app/public/uploads',
        ],
        'compression_level' => env('BACKUP_COMPRESSION_LEVEL', 6),
        'exclude_patterns' => [
            '*.tmp',
            '*.log',
            'cache/*',
            'sessions/*',
        ],
    ],
    
    'retention' => [
        'database_days' => env('BACKUP_DATABASE_RETENTION_DAYS', 30),
        'files_days' => env('BACKUP_FILES_RETENTION_DAYS', 14),
        'cleanup_frequency' => 'daily', // daily, weekly, monthly
    ],
    
    'notifications' => [
        'email' => env('BACKUP_NOTIFICATION_EMAIL'),
        'notify_on_success' => env('BACKUP_NOTIFY_ON_SUCCESS', false),
        'notify_on_failure' => env('BACKUP_NOTIFY_ON_FAILURE', true),
        'notify_on_cleanup' => env('BACKUP_NOTIFY_ON_CLEANUP', false),
    ],
    
    'security' => [
        'download_expiry' => env('BACKUP_DOWNLOAD_EXPIRY', 3600), // seconds
        'max_download_attempts' => env('BACKUP_MAX_DOWNLOAD_ATTEMPTS', 3),
        'require_authentication' => true,
        'log_access' => true,
    ],
    
    'monitoring' => [
        'health_check_frequency' => 'hourly',
        'storage_warning_threshold' => 80, // percentage
        'backup_age_warning' => 48, // hours
    ],
];
```

### Web Server Configuration

#### Nginx Configuration

Add backup-specific configuration to your Nginx site config:

```nginx
# Backup management routes
location /admin/backup {
    # Restrict access to admin users only
    # Add IP restrictions if needed
    # allow 192.168.1.0/24;
    # deny all;
    
    try_files $uri $uri/ /index.php?$query_string;
}

# Secure backup file downloads
location /backup-download {
    internal;
    alias /var/www/html/your-application/storage/app/backups;
    
    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
}

# Block direct access to backup files
location ~* ^/storage/app/backups/ {
    deny all;
    return 404;
}
```

#### Apache Configuration

Add to your Apache virtual host or `.htaccess`:

```apache
# Backup management security
<LocationMatch "^/admin/backup">
    # Add IP restrictions if needed
    # Require ip 192.168.1
    Require valid-user
</LocationMatch>

# Block direct access to backup files
<DirectoryMatch "^/var/www/html/your-application/storage/app/backups">
    Require all denied
</DirectoryMatch>

# Security headers for backup downloads
<LocationMatch "^/backup-download">
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</LocationMatch>
```

## Security Hardening

### File System Security

```bash
# Set restrictive permissions on backup directory
chmod 750 storage/app/backups
chown www-data:backup-group storage/app/backups

# Create backup group for controlled access
groupadd backup-group
usermod -a -G backup-group www-data

# Set umask for backup files
echo "umask 027" >> /etc/profile.d/backup-security.sh
```

### Database Security

```sql
-- Limit backup user to specific host
DROP USER 'backup_user'@'localhost';
CREATE USER 'backup_user'@'127.0.0.1' IDENTIFIED BY 'very_secure_password';

-- Grant minimal required permissions
GRANT SELECT, LOCK TABLES, SHOW VIEW ON your_database.* TO 'backup_user'@'127.0.0.1';
GRANT PROCESS ON *.* TO 'backup_user'@'127.0.0.1';

-- Set password expiration
ALTER USER 'backup_user'@'127.0.0.1' PASSWORD EXPIRE INTERVAL 90 DAY;

FLUSH PRIVILEGES;
```

### Application Security

Update your backup service configuration:

```php
// In config/backup.php
'security' => [
    'encrypt_backups' => env('BACKUP_ENCRYPT', false),
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
    'require_2fa' => env('BACKUP_REQUIRE_2FA', false),
    'ip_whitelist' => env('BACKUP_IP_WHITELIST', ''),
    'max_concurrent_backups' => 2,
],
```

## Monitoring and Alerting

### Health Check Setup

Create a monitoring script:

```bash
#!/bin/bash
# /usr/local/bin/backup-health-check.sh

APP_PATH="/var/www/html/your-application"
LOG_FILE="/var/log/backup-health.log"

cd $APP_PATH

# Run health check
php artisan backup:health-check >> $LOG_FILE 2>&1

# Check exit code
if [ $? -ne 0 ]; then
    echo "$(date): Backup health check failed" >> $LOG_FILE
    # Send alert (email, Slack, etc.)
    # mail -s "Backup Health Check Failed" admin@yourcompany.com < $LOG_FILE
fi
```

Make it executable and add to cron:

```bash
chmod +x /usr/local/bin/backup-health-check.sh

# Add to crontab
crontab -e
# Add: 0 */6 * * * /usr/local/bin/backup-health-check.sh
```

### Log Monitoring

Configure log rotation for backup logs:

```bash
# Create logrotate configuration
cat > /etc/logrotate.d/backup-system << EOF
/var/log/backup-health.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 root root
}
EOF
```

## Backup Strategy Configuration

### Production Backup Schedule

Configure automated backups through the admin interface or directly in the database:

```sql
-- Insert production backup schedule
INSERT INTO backup_schedules (name, type, frequency, time, is_active, retention_days, created_at, updated_at) VALUES
('Daily Database Backup', 'database', 'daily', '02:00:00', 1, 30, NOW(), NOW()),
('Weekly File Backup', 'files', 'weekly', '03:00:00', 1, 14, NOW(), NOW()),
('Monthly Full Backup', 'full', 'monthly', '01:00:00', 1, 90, NOW(), NOW());
```

### Retention Policy

Configure retention policies based on your business requirements:

```env
# Production retention settings
BACKUP_DATABASE_RETENTION_DAYS=30
BACKUP_FILES_RETENTION_DAYS=14
BACKUP_FULL_RETENTION_DAYS=90

# Archive settings for long-term storage
BACKUP_ARCHIVE_AFTER_DAYS=90
BACKUP_ARCHIVE_LOCATION=/mnt/archive/backups
```

## Testing and Validation

### Deployment Testing Checklist

- [ ] **Backup Creation Tests**
  ```bash
  php artisan backup:create --database
  php artisan backup:create --files
  php artisan backup:create
  ```

- [ ] **Backup Integrity Tests**
  ```bash
  php artisan backup:status
  # Verify all backups show as "completed"
  ```

- [ ] **Restoration Tests**
  ```bash
  # Test in staging environment
  php artisan backup:restore latest-backup.sql --database --force
  ```

- [ ] **Automated Schedule Tests**
  ```bash
  # Manually trigger scheduled backup
  php artisan schedule:run
  # Check if backup was created
  ```

- [ ] **Notification Tests**
  ```bash
  # Trigger a backup failure to test notifications
  # Verify email notifications are received
  ```

- [ ] **Permission Tests**
  ```bash
  # Verify backup files have correct permissions
  ls -la storage/app/backups/
  ```

- [ ] **Web Interface Tests**
  - Login as admin user
  - Access backup management interface
  - Create manual backup
  - Download backup file
  - Configure backup settings

### Performance Testing

Test backup performance with production-sized data:

```bash
# Time database backup
time php artisan backup:create --database

# Monitor system resources during backup
top -p $(pgrep -f "backup:create")

# Check backup file sizes
du -sh storage/app/backups/*
```

## Troubleshooting

### Common Deployment Issues

#### Permission Errors
```bash
# Fix backup directory permissions
chown -R www-data:www-data storage/app/backups
chmod -R 750 storage/app/backups
```

#### Database Connection Issues
```bash
# Test database connection
mysql -u backup_user -p -h localhost your_database

# Verify user permissions
mysql -u root -p
SHOW GRANTS FOR 'backup_user'@'localhost';
```

#### Disk Space Issues
```bash
# Check available space
df -h

# Clean old backups manually
php artisan backup:cleanup --dry-run
php artisan backup:cleanup
```

#### Cron Job Issues
```bash
# Check cron service
systemctl status cron

# Test Laravel scheduler
php artisan schedule:list
php artisan schedule:run
```

### Log Analysis

Monitor backup operations through logs:

```bash
# Application logs
tail -f storage/logs/laravel.log | grep -i backup

# System logs
tail -f /var/log/syslog | grep -i backup

# Cron logs
tail -f /var/log/cron.log
```

## Maintenance Procedures

### Regular Maintenance Tasks

#### Daily
- Monitor backup completion status
- Check available disk space
- Review error logs

#### Weekly
- Verify backup file integrity
- Test restoration procedures in staging
- Review retention policy effectiveness

#### Monthly
- Full disaster recovery test
- Security audit of backup access
- Performance optimization review
- Update backup documentation

### Backup System Updates

When updating the backup system:

1. **Create pre-update backup**
   ```bash
   php artisan backup:create --name="pre-update-$(date +%Y%m%d)"
   ```

2. **Test in staging environment first**

3. **Run migrations**
   ```bash
   php artisan migrate
   ```

4. **Update configuration files**

5. **Test all backup functionality**

6. **Update documentation**

## Disaster Recovery

### Recovery Procedures

#### Database Recovery
```bash
# Put application in maintenance mode
php artisan down

# Restore database
php artisan backup:restore latest-database-backup.sql --database --force

# Verify data integrity
php artisan tinker
>>> User::count();
>>> Package::count();

# Bring application back online
php artisan up
```

#### File Recovery
```bash
# Backup current files
mv storage/app/public storage/app/public.backup

# Restore files
php artisan backup:restore latest-files-backup.zip --files --force

# Verify file permissions
chown -R www-data:www-data storage/app/public
```

### Business Continuity

- Maintain offsite backup copies
- Document recovery procedures
- Train staff on restoration processes
- Test recovery procedures regularly
- Maintain emergency contact information

## Support and Escalation

### Internal Support Process
1. Check system logs and error messages
2. Verify configuration settings
3. Test manual backup operations
4. Review disk space and permissions
5. Consult troubleshooting documentation

### External Support
For critical issues:
1. Gather system information and logs
2. Document error messages and reproduction steps
3. Contact development team with detailed information
4. Provide access to staging environment if needed

### Emergency Contacts
- System Administrator: [contact information]
- Database Administrator: [contact information]
- Development Team: [contact information]
- Infrastructure Team: [contact information]

## Conclusion

Following this deployment guide ensures a secure, reliable backup system for your ShipSharkLtd application. Regular testing and maintenance are crucial for ensuring the backup system functions correctly when needed.

For additional support or questions, refer to the [Backup Admin Guide](BACKUP_ADMIN_GUIDE.md) or contact your system administrator.