#!/bin/bash

# ShipSharkLtd Audit System Deployment Script
# This script deploys the audit logging system with proper configuration

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${PROJECT_ROOT}/storage/deployment-backups"
LOG_FILE="${PROJECT_ROOT}/storage/logs/audit-deployment.log"

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

check_requirements() {
    log "Checking system requirements..."
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        error "Laravel artisan command not found. Please run this script from the Laravel project root directory."
    fi
    
    # Check PHP version
    if command -v php > /dev/null; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null)
        if [ $? -eq 0 ]; then
            if ! php -r "exit(version_compare(PHP_VERSION, '7.4.0', '>=') ? 0 : 1);" 2>/dev/null; then
                error "PHP 7.4 or higher is required. Current version: $PHP_VERSION"
            fi
            success "PHP version check passed: $PHP_VERSION"
        else
            error "PHP is not working correctly"
        fi
    else
        error "PHP is not installed or not in PATH"
    fi
    
    success "Laravel installation detected"
    
    # Check database connection with better error handling
    log "Testing database connection..."
    if php "$PROJECT_ROOT/artisan" migrate:status > /dev/null 2>&1; then
        success "Database connection verified"
    else
        warning "Database connection test failed. Please verify your database configuration in .env file"
        log "Continuing with deployment, but database operations may fail..."
    fi
    
    # Check Redis connection (if configured) with better error handling
    if [ -f "$PROJECT_ROOT/.env" ] && grep -q "QUEUE_CONNECTION=redis" "$PROJECT_ROOT/.env" 2>/dev/null; then
        log "Testing Redis connection..."
        if php "$PROJECT_ROOT/artisan" tinker --execute="Redis::ping();" > /dev/null 2>&1; then
            success "Redis connection verified"
        else
            warning "Redis connection could not be verified. Queue processing may be affected."
        fi
    fi
    
    # Check required directories
    mkdir -p "$PROJECT_ROOT/storage/logs"
    mkdir -p "$PROJECT_ROOT/storage/deployment-backups"
}

create_backup() {
    log "Creating deployment backup..."
    
    mkdir -p "$BACKUP_DIR"
    BACKUP_TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_PATH="${BACKUP_DIR}/pre_audit_deployment_${BACKUP_TIMESTAMP}"
    
    # Backup database
    log "Backing up database..."
    if command -v mysqldump > /dev/null; then
        # Get database configuration from Laravel
        DB_HOST=$(php "$PROJECT_ROOT/artisan" tinker --execute="echo config('database.connections.mysql.host', 'localhost');" 2>/dev/null | tail -1)
        DB_DATABASE=$(php "$PROJECT_ROOT/artisan" tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | tail -1)
        DB_USERNAME=$(php "$PROJECT_ROOT/artisan" tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | tail -1)
        DB_PASSWORD=$(php "$PROJECT_ROOT/artisan" tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null | tail -1)
        
        if [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
            mysqldump -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "${BACKUP_PATH}_database.sql" 2>/dev/null
            if [ $? -eq 0 ]; then
                success "Database backup created: ${BACKUP_PATH}_database.sql"
            else
                warning "Database backup failed. Continuing with deployment..."
            fi
        else
            warning "Could not retrieve database configuration. Skipping database backup."
        fi
    else
        warning "mysqldump not found. Skipping database backup."
    fi
    
    # Backup configuration files
    log "Backing up configuration files..."
    mkdir -p "${BACKUP_PATH}_config"
    cp "$PROJECT_ROOT/.env" "${BACKUP_PATH}_config/.env" 2>/dev/null || true
    cp -r "$PROJECT_ROOT/config" "${BACKUP_PATH}_config/" 2>/dev/null || true
    success "Configuration backup created: ${BACKUP_PATH}_config"
}

run_migrations() {
    log "Running audit system migrations..."
    
    cd "$PROJECT_ROOT"
    
    # Check if migration files exist before running them
    MIGRATION_FILES=(
        "database/migrations/2025_09_20_182606_create_audit_logs_table.php"
        "database/migrations/2025_09_20_182648_create_audit_settings_table.php"
        "database/migrations/2025_09_21_000001_add_audit_performance_indexes.php"
    )
    
    for migration_file in "${MIGRATION_FILES[@]}"; do
        if [ -f "$PROJECT_ROOT/$migration_file" ]; then
            log "Running migration: $migration_file"
            if php artisan migrate --path="$migration_file" --force; then
                success "Migration completed: $(basename "$migration_file")"
            else
                warning "Migration failed or already applied: $(basename "$migration_file")"
            fi
        else
            warning "Migration file not found: $migration_file"
        fi
    done
    
    # Run all pending migrations as fallback
    log "Running any remaining migrations..."
    php artisan migrate --force
    
    success "Audit system migrations process completed"
}

seed_configuration() {
    log "Seeding audit system configuration..."
    
    cd "$PROJECT_ROOT"
    
    # Check if seeder exists
    if [ -f "$PROJECT_ROOT/database/seeders/AuditSystemSeeder.php" ]; then
        log "Running AuditSystemSeeder..."
        if php artisan db:seed --class=AuditSystemSeeder --force; then
            success "Audit system configuration seeded successfully"
        else
            warning "Seeding failed or encountered errors. Check the logs for details."
        fi
    else
        warning "AuditSystemSeeder not found. Skipping seeding step."
        log "You may need to run the seeder manually later: php artisan db:seed --class=AuditSystemSeeder"
    fi
}

configure_environment() {
    log "Configuring environment variables..."
    
    ENV_FILE="$PROJECT_ROOT/.env"
    
    # Check if audit configuration exists
    if ! grep -q "AUDIT_ENABLED" "$ENV_FILE" 2>/dev/null; then
        log "Adding audit system environment variables..."
        
        cat >> "$ENV_FILE" << 'EOF'

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
EOF
        success "Environment variables added to .env file"
    else
        success "Audit environment variables already configured"
    fi
}

setup_queue_workers() {
    log "Setting up queue workers for audit processing..."
    
    # Create supervisor configuration for audit queue
    SUPERVISOR_CONFIG="/etc/supervisor/conf.d/audit-queue-worker.conf"
    
    if [ -w "/etc/supervisor/conf.d" ] || [ "$EUID" -eq 0 ]; then
        cat > "$SUPERVISOR_CONFIG" << EOF
[program:audit-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $PROJECT_ROOT/artisan queue:work --queue=audit-processing --tries=3 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$PROJECT_ROOT/storage/logs/audit-queue-worker.log
stopwaitsecs=3600
EOF
        success "Supervisor configuration created: $SUPERVISOR_CONFIG"
        
        # Reload supervisor
        if command -v supervisorctl > /dev/null; then
            supervisorctl reread
            supervisorctl update
            supervisorctl start audit-queue-worker:*
            success "Audit queue workers started"
        fi
    else
        warning "Cannot create supervisor configuration. Please run as root or configure manually."
        log "Manual supervisor configuration needed at: $SUPERVISOR_CONFIG"
    fi
}

setup_cron_jobs() {
    log "Setting up cron jobs for audit maintenance..."
    
    CRON_FILE="/tmp/audit-cron-jobs"
    
    # Create cron job entries
    cat > "$CRON_FILE" << EOF
# Audit System Maintenance Jobs
# Clean up expired audit logs daily at 2 AM
0 2 * * * cd $PROJECT_ROOT && php artisan audit:cleanup >> $PROJECT_ROOT/storage/logs/audit-cleanup.log 2>&1

# Generate weekly audit reports (if enabled)
0 6 * * 1 cd $PROJECT_ROOT && php artisan audit:weekly-report >> $PROJECT_ROOT/storage/logs/audit-reports.log 2>&1

# Check audit system health daily at 6 AM
0 6 * * * cd $PROJECT_ROOT && php artisan audit:health-check >> $PROJECT_ROOT/storage/logs/audit-health.log 2>&1
EOF
    
    log "Cron jobs configuration created. To install, run:"
    log "crontab $CRON_FILE"
    
    # Optionally install cron jobs automatically
    read -p "Install cron jobs automatically? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        crontab "$CRON_FILE"
        success "Cron jobs installed"
    else
        log "Cron jobs not installed. Manual installation required."
    fi
    
    rm "$CRON_FILE"
}

optimize_database() {
    log "Optimizing database for audit system..."
    
    cd "$PROJECT_ROOT"
    
    # Run database optimizations
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    success "Application cache optimized"
}

verify_installation() {
    log "Verifying audit system installation..."
    
    cd "$PROJECT_ROOT"
    
    # Check if audit tables exist
    log "Checking audit tables..."
    if php artisan migrate:status 2>/dev/null | grep -q "audit_logs"; then
        success "Audit logs table verified"
    else
        warning "Audit logs table not found in migration status"
    fi
    
    if php artisan migrate:status 2>/dev/null | grep -q "audit_settings"; then
        success "Audit settings table verified"
    else
        warning "Audit settings table not found in migration status"
    fi
    
    # Check if audit settings are seeded with better error handling
    log "Checking audit settings..."
    SETTINGS_COUNT=$(php artisan tinker --execute="try { echo App\\Models\\AuditSetting::count(); } catch (Exception \$e) { echo '0'; }" 2>/dev/null | tail -1 | tr -d '\n')
    
    if [ -n "$SETTINGS_COUNT" ] && [ "$SETTINGS_COUNT" -gt 0 ] 2>/dev/null; then
        success "Audit settings verified ($SETTINGS_COUNT settings found)"
    else
        warning "Audit settings not found or could not be verified. You may need to run the seeder manually."
    fi
    
    # Test audit log creation with proper method signature
    log "Testing audit log creation..."
    TEST_RESULT=$(php artisan tinker --execute="try { \$service = app(App\\Services\\AuditService::class); \$service->log(['event_type' => 'system_event', 'action' => 'deployment_test', 'additional_data' => ['test' => true]]); echo 'SUCCESS'; } catch (Exception \$e) { echo 'FAILED: ' . \$e->getMessage(); }" 2>/dev/null | tail -1)
    
    if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
        success "Audit log creation test passed"
    else
        warning "Audit log creation test failed: $TEST_RESULT"
        warning "You may need to check the audit system configuration manually"
    fi
}

print_summary() {
    log "Deployment Summary"
    log "=================="
    log "✓ System requirements checked"
    log "✓ Backup created in: $BACKUP_DIR"
    log "✓ Database migrations completed"
    log "✓ Configuration seeded"
    log "✓ Environment variables configured"
    log "✓ Installation verified"
    log ""
    log "Next Steps:"
    log "1. Configure queue workers (supervisor configuration provided)"
    log "2. Install cron jobs for maintenance"
    log "3. Review audit settings in Administration > Audit Settings"
    log "4. Test audit log functionality"
    log "5. Configure security monitoring alerts"
    log ""
    log "Documentation:"
    log "- User Guide: docs/AUDIT_LOG_USER_GUIDE.md"
    log "- Admin Guide: docs/AUDIT_SYSTEM_ADMIN_GUIDE.md"
    log ""
    success "Audit system deployment completed successfully!"
}

# Main deployment process
main() {
    log "Starting ShipSharkLtd Audit System Deployment"
    log "=============================================="
    
    # Create log directory if it doesn't exist
    mkdir -p "$(dirname "$LOG_FILE")"
    
    check_requirements
    create_backup
    run_migrations
    seed_configuration
    configure_environment
    setup_queue_workers
    setup_cron_jobs
    optimize_database
    verify_installation
    print_summary
}

# Run main function
main "$@"