# Production Server Deployment Guide

This guide provides specific instructions for deploying the audit system on your production server at `/var/www/html/app.shipsharkltd.com`.

## Immediate Fix for Current Error

The error you're seeing is due to permission issues. Run these commands on your production server:

### Step 1: Fix Permissions (Run as root)

```bash
# Navigate to project directory
cd /var/www/html/app.shipsharkltd.com

# Upload and run the permission fix script
sudo bash scripts/fix-production-permissions.sh
```

### Step 2: Manual Permission Fix (Alternative)

If the script is not available, run these commands manually:

```bash
# Create required directories
sudo mkdir -p /var/www/html/app.shipsharkltd.com/storage/app/backups
sudo mkdir -p /var/www/html/app.shipsharkltd.com/storage/logs
sudo mkdir -p /var/www/html/app.shipsharkltd.com/storage/framework/{cache,sessions,views}

# Fix ownership
sudo chown -R www-data:www-data /var/www/html/app.shipsharkltd.com/storage/
sudo chown -R www-data:www-data /var/www/html/app.shipsharkltd.com/bootstrap/cache/

# Fix permissions
sudo chmod -R 775 /var/www/html/app.shipsharkltd.com/storage/
sudo chmod -R 775 /var/www/html/app.shipsharkltd.com/bootstrap/cache/

# Clear caches
sudo -u www-data php /var/www/html/app.shipsharkltd.com/artisan config:clear
sudo -u www-data php /var/www/html/app.shipsharkltd.com/artisan cache:clear
```

## Complete Audit System Deployment

Once permissions are fixed, deploy the audit system:

### Step 1: Run Migrations

```bash
cd /var/www/html/app.shipsharkltd.com
sudo -u www-data php artisan migrate --force
```

### Step 2: Seed Audit Configuration

```bash
sudo -u www-data php artisan db:seed --class=AuditSystemSeeder --force
```

### Step 3: Update Environment Configuration

Add these lines to your `.env` file:

```bash
# Add audit configuration
sudo tee -a /var/www/html/app.shipsharkltd.com/.env > /dev/null <<EOF

# Audit System Configuration
AUDIT_ENABLED=true
AUDIT_ASYNC_ENABLED=true
AUDIT_QUEUE_NAME=audit-processing
AUDIT_CACHE_TTL=3600
AUDIT_RETENTION_AUTHENTICATION=365
AUDIT_RETENTION_SECURITY_EVENTS=1095
AUDIT_RETENTION_MODEL_CHANGES=730
AUDIT_RETENTION_BUSINESS_ACTIONS=1095
AUDIT_SECURITY_ALERT_EMAIL=security@shipsharkltd.com
EOF
```

### Step 4: Optimize Application

```bash
cd /var/www/html/app.shipsharkltd.com
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan optimize
```

### Step 5: Set Up Queue Workers (Recommended)

```bash
# Install supervisor if not already installed
sudo apt update
sudo apt install supervisor

# Create supervisor configuration
sudo tee /etc/supervisor/conf.d/audit-queue-worker.conf > /dev/null <<EOF
[program:audit-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/app.shipsharkltd.com/artisan queue:work --queue=audit-processing --tries=3 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/app.shipsharkltd.com/storage/logs/audit-queue-worker.log
EOF

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start audit-queue-worker:*
```

### Step 6: Set Up Cron Jobs

```bash
# Edit www-data crontab
sudo crontab -u www-data -e

# Add these lines:
0 2 * * * cd /var/www/html/app.shipsharkltd.com && php artisan audit:cleanup >> /var/www/html/app.shipsharkltd.com/storage/logs/audit-cleanup.log 2>&1
0 6 * * * cd /var/www/html/app.shipsharkltd.com && php artisan audit:health-check >> /var/www/html/app.shipsharkltd.com/storage/logs/audit-health.log 2>&1
```

## Verification Steps

### Test 1: Check Application Status

```bash
cd /var/www/html/app.shipsharkltd.com
sudo -u www-data php artisan --version
```

### Test 2: Verify Database Connection

```bash
sudo -u www-data php artisan migrate:status
```

### Test 3: Test Audit Service

```bash
sudo -u www-data php artisan tinker --execute="
try {
    \$service = app('App\Services\AuditService');
    \$service->log([
        'event_type' => 'system_event',
        'action' => 'production_deployment_test',
        'additional_data' => ['server' => 'production']
    ]);
    echo 'SUCCESS: Audit service working';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
"
```

### Test 4: Check Web Interface

1. Access your application in a web browser
2. Log in as Super Admin
3. Navigate to Administration → Audit Logs
4. Verify you can see audit entries

## Troubleshooting Production Issues

### Permission Issues

```bash
# Check current permissions
ls -la /var/www/html/app.shipsharkltd.com/storage/

# Fix if needed
sudo chown -R www-data:www-data /var/www/html/app.shipsharkltd.com/storage/
sudo chmod -R 775 /var/www/html/app.shipsharkltd.com/storage/
```

### SELinux Issues (if applicable)

```bash
# Check SELinux status
sestatus

# If enabled, set proper contexts
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_unified 1
sudo restorecon -Rv /var/www/html/app.shipsharkltd.com/storage/
```

### Database Connection Issues

```bash
# Test MySQL connection
mysql -u your_username -p -e "SELECT 1;"

# Check Laravel database config
sudo -u www-data php artisan tinker --execute="echo config('database.default');"
```

### Queue Worker Issues

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers if needed
sudo supervisorctl restart audit-queue-worker:*

# Check worker logs
tail -f /var/www/html/app.shipsharkltd.com/storage/logs/audit-queue-worker.log
```

### Web Server Issues

```bash
# Check Apache/Nginx status
sudo systemctl status apache2  # or nginx

# Check error logs
sudo tail -f /var/log/apache2/error.log  # or /var/log/nginx/error.log

# Restart web server if needed
sudo systemctl restart apache2  # or nginx
```

## Security Considerations for Production

### File Permissions Security

```bash
# Secure file permissions (run after deployment)
cd /var/www/html/app.shipsharkltd.com

# Set secure permissions for application files
sudo find . -type f -not -path "./storage/*" -not -path "./bootstrap/cache/*" -exec chmod 644 {} \;
sudo find . -type d -not -path "./storage/*" -not -path "./bootstrap/cache/*" -exec chmod 755 {} \;

# Keep storage and cache writable
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
```

### Environment File Security

```bash
# Secure .env file
sudo chmod 600 /var/www/html/app.shipsharkltd.com/.env
sudo chown www-data:www-data /var/www/html/app.shipsharkltd.com/.env
```

### Database Security

```bash
# Create dedicated audit user (optional)
mysql -u root -p -e "
CREATE USER IF NOT EXISTS 'audit_user'@'localhost' IDENTIFIED BY 'secure_random_password';
GRANT SELECT, INSERT, UPDATE ON your_database.audit_logs TO 'audit_user'@'localhost';
GRANT SELECT, INSERT, UPDATE ON your_database.audit_settings TO 'audit_user'@'localhost';
FLUSH PRIVILEGES;
"
```

## Monitoring and Maintenance

### Log Monitoring

```bash
# Monitor application logs
tail -f /var/www/html/app.shipsharkltd.com/storage/logs/laravel.log

# Monitor audit logs
tail -f /var/www/html/app.shipsharkltd.com/storage/logs/audit-*.log

# Monitor web server logs
sudo tail -f /var/log/apache2/access.log
```

### Disk Space Monitoring

```bash
# Check disk usage
df -h

# Check audit log size
du -sh /var/www/html/app.shipsharkltd.com/storage/logs/

# Set up log rotation
sudo tee /etc/logrotate.d/laravel-audit > /dev/null <<EOF
/var/www/html/app.shipsharkltd.com/storage/logs/*.log {
    weekly
    rotate 12
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
}
EOF
```

### Performance Monitoring

```bash
# Monitor MySQL processes
mysqladmin -u root -p processlist

# Monitor system resources
htop

# Monitor queue status
sudo -u www-data php /var/www/html/app.shipsharkltd.com/artisan queue:monitor
```

## Rollback Plan

If deployment fails:

```bash
# 1. Restore database backup
mysql -u root -p your_database < backup_file.sql

# 2. Restore .env file
sudo cp .env.backup .env

# 3. Clear caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear

# 4. Restart services
sudo systemctl restart apache2
sudo supervisorctl restart all
```

## Support Contacts

- **System Administrator**: admin@shipsharkltd.com
- **Development Team**: dev@shipsharkltd.com
- **Emergency Contact**: [Your emergency contact]

---

**Important**: Always test changes in a staging environment before applying to production. Keep regular backups of your database and configuration files.