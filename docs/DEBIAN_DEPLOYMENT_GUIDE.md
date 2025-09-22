# Audit System Deployment Guide for Debian/Ubuntu

This guide provides step-by-step instructions for deploying the audit system on Debian 12 (bookworm) and Ubuntu systems.

## Prerequisites

### System Requirements
- Debian 12 (bookworm) or Ubuntu 20.04+
- PHP 7.4 or higher with required extensions
- MySQL/MariaDB database
- Web server (Apache/Nginx)
- Composer for PHP dependencies

### Install Required Packages

```bash
# Update package list
sudo apt update

# Install PHP and required extensions
sudo apt install php php-cli php-mysql php-mbstring php-xml php-curl php-zip php-gd php-json php-bcmath

# Install MySQL/MariaDB (if not already installed)
sudo apt install mysql-server

# Install Composer (if not already installed)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## Manual Deployment Steps

### 1. Prepare the Environment

```bash
# Navigate to your Laravel project directory
cd /path/to/your/laravel/project

# Ensure proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 2. Database Setup

```bash
# Create database backup (recommended)
mysqldump -u your_username -p your_database > backup_$(date +%Y%m%d_%H%M%S).sql

# Run migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

### 3. Seed Configuration

```bash
# Run the audit system seeder
php artisan db:seed --class=AuditSystemSeeder --force

# Verify seeding
php artisan tinker --execute="echo App\Models\AuditSetting::count();"
```

### 4. Environment Configuration

Add these variables to your `.env` file:

```env
# Basic Audit Configuration
AUDIT_ENABLED=true
AUDIT_ASYNC_ENABLED=true
AUDIT_QUEUE_NAME=audit-processing
AUDIT_CACHE_TTL=3600

# Retention Policies (days)
AUDIT_RETENTION_AUTHENTICATION=365
AUDIT_RETENTION_SECURITY_EVENTS=1095
AUDIT_RETENTION_MODEL_CHANGES=730
AUDIT_RETENTION_BUSINESS_ACTIONS=1095

# Security Monitoring
AUDIT_FAILED_LOGIN_THRESHOLD=5
AUDIT_SUSPICIOUS_ACTIVITY_THRESHOLD=10
AUDIT_SECURITY_ALERT_EMAIL=security@yourcompany.com
```

### 5. Application Optimization

```bash
# Clear and cache configuration
php artisan config:clear
php artisan config:cache

# Clear other caches
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan optimize
```

### 6. Queue Configuration (Optional but Recommended)

If using Redis for queues:

```bash
# Install Redis
sudo apt install redis-server

# Start Redis service
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Update .env for Redis
echo "QUEUE_CONNECTION=redis" >> .env
```

Set up Supervisor for queue workers:

```bash
# Install Supervisor
sudo apt install supervisor

# Create supervisor config
sudo tee /etc/supervisor/conf.d/audit-queue-worker.conf > /dev/null <<EOF
[program:audit-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --queue=audit-processing --tries=3 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/audit-queue-worker.log
EOF

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start audit-queue-worker:*
```

### 7. Cron Jobs Setup

```bash
# Edit crontab for www-data user
sudo crontab -u www-data -e

# Add these entries:
# Clean up expired audit logs daily at 2 AM
0 2 * * * cd /path/to/your/project && php artisan audit:cleanup >> /path/to/your/project/storage/logs/audit-cleanup.log 2>&1

# Health check daily at 6 AM
0 6 * * * cd /path/to/your/project && php artisan audit:health-check >> /path/to/your/project/storage/logs/audit-health.log 2>&1
```

## Verification Steps

### 1. Test Database Connection

```bash
php artisan migrate:status
```

### 2. Test Audit Service

```bash
php artisan tinker --execute="
try {
    \$service = app('App\Services\AuditService');
    \$service->log([
        'event_type' => 'system_event',
        'action' => 'deployment_test',
        'additional_data' => ['test' => true]
    ]);
    echo 'Audit service working correctly';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage();
}
"
```

### 3. Access Audit Interface

1. Log in as Super Admin
2. Navigate to Administration → Audit Logs
3. Verify you can see audit entries
4. Test search and export functionality

## Troubleshooting

### Common Issues on Debian

#### Permission Issues
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

#### PHP Extensions Missing
```bash
# Check installed extensions
php -m

# Install missing extensions
sudo apt install php-extension-name
```

#### Database Connection Issues
```bash
# Check MySQL service
sudo systemctl status mysql

# Test connection
mysql -u username -p -e "SELECT 1;"
```

#### Queue Worker Issues
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart audit-queue-worker:*

# Check worker logs
tail -f storage/logs/audit-queue-worker.log
```

### Log Files to Check

- Application logs: `storage/logs/laravel.log`
- Deployment logs: `storage/logs/audit-deployment.log`
- Queue worker logs: `storage/logs/audit-queue-worker.log`
- Web server logs: `/var/log/apache2/` or `/var/log/nginx/`

## Security Considerations

### File Permissions
```bash
# Secure file permissions
find /path/to/project -type f -exec chmod 644 {} \;
find /path/to/project -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
```

### Database Security
```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create dedicated audit user (optional)
mysql -u root -p -e "
CREATE USER 'audit_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE ON your_database.audit_logs TO 'audit_user'@'localhost';
GRANT SELECT, INSERT, UPDATE ON your_database.audit_settings TO 'audit_user'@'localhost';
FLUSH PRIVILEGES;
"
```

### Firewall Configuration
```bash
# Basic UFW setup (if using UFW)
sudo ufw allow ssh
sudo ufw allow 'Apache Full'  # or 'Nginx Full'
sudo ufw enable
```

## Performance Optimization

### Database Optimization
```bash
# Optimize MySQL for audit logs
sudo tee -a /etc/mysql/mysql.conf.d/audit-optimization.cnf > /dev/null <<EOF
[mysqld]
# Audit log optimizations
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 64M
query_cache_type = 1
EOF

# Restart MySQL
sudo systemctl restart mysql
```

### PHP Optimization
```bash
# Install OPcache
sudo apt install php-opcache

# Configure OPcache in php.ini
echo "opcache.enable=1" | sudo tee -a /etc/php/7.4/apache2/php.ini
echo "opcache.memory_consumption=128" | sudo tee -a /etc/php/7.4/apache2/php.ini
```

## Monitoring Setup

### Log Rotation
```bash
# Create logrotate config
sudo tee /etc/logrotate.d/audit-logs > /dev/null <<EOF
/path/to/your/project/storage/logs/audit-*.log {
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

### System Monitoring
```bash
# Install basic monitoring tools
sudo apt install htop iotop nethogs

# Monitor disk space
df -h

# Monitor MySQL processes
mysqladmin -u root -p processlist
```

## Support

For additional help:

1. Check the main documentation in `docs/AUDIT_SYSTEM_ADMIN_GUIDE.md`
2. Review Laravel logs in `storage/logs/laravel.log`
3. Test the deployment with the provided script: `scripts/deploy-audit-system-debian.sh`
4. Contact your system administrator for server-specific issues

---

*This guide is specifically tailored for Debian/Ubuntu systems. For other distributions, adapt the package installation commands accordingly.*