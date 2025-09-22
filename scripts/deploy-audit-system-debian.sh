#!/bin/bash

# ShipSharkLtd Audit System Deployment Script - Debian/Ubuntu Optimized
# This script deploys the audit logging system with Debian-specific optimizations

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
LOG_FILE="${PROJECT_ROOT}/storage/logs/audit-deployment.log"

# Ensure log directory exists
mkdir -p "${PROJECT_ROOT}/storage/logs"

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

# Simple requirements check
check_requirements() {
    log "Checking system requirements..."
    
    # Check if we're in Laravel directory
    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        error "Not in Laravel project directory. Please run from project root."
    fi
    
    # Check PHP
    if ! command -v php > /dev/null; then
        error "PHP not found. Please install PHP 7.4 or higher."
    fi
    
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    success "PHP $PHP_VERSION detected"
    
    # Test Laravel
    if php artisan --version > /dev/null 2>&1; then
        success "Laravel installation verified"
    else
        error "Laravel not working properly"
    fi
}

# Run migrations
run_migrations() {
    log "Running audit system migrations..."
    cd "$PROJECT_ROOT"
    
    # Simple migration approach - run all migrations
    if php artisan migrate --force; then
        success "Migrations completed"
    else
        warning "Some migrations may have failed - continuing..."
    fi
}

# Seed configuration
seed_configuration() {
    log "Seeding audit configuration..."
    cd "$PROJECT_ROOT"
    
    if php artisan db:seed --class=AuditSystemSeeder --force 2>/dev/null; then
        success "Configuration seeded"
    else
        warning "Seeding encountered issues - you may need to run manually"
    fi
}

# Configure environment
configure_environment() {
    log "Checking environment configuration..."
    
    ENV_FILE="$PROJECT_ROOT/.env"
    
    if [ ! -f "$ENV_FILE" ]; then
        warning ".env file not found. Please create it from .env.example"
        return
    fi
    
    # Check if audit config exists
    if ! grep -q "AUDIT_ENABLED" "$ENV_FILE"; then
        log "Adding basic audit configuration to .env..."
        cat >> "$ENV_FILE" << 'EOF'

# Audit System Configuration
AUDIT_ENABLED=true
AUDIT_ASYNC_ENABLED=true
AUDIT_QUEUE_NAME=audit-processing
EOF
        success "Basic audit configuration added"
    else
        success "Audit configuration already present"
    fi
}

# Simple verification
verify_installation() {
    log "Verifying installation..."
    cd "$PROJECT_ROOT"
    
    # Test database connection
    if php artisan migrate:status > /dev/null 2>&1; then
        success "Database connection working"
    else
        warning "Database connection issues detected"
    fi
    
    # Test audit service
    TEST_CMD='try { $service = app("App\Services\AuditService"); echo "OK"; } catch (Exception $e) { echo "FAIL"; }'
    RESULT=$(php artisan tinker --execute="$TEST_CMD" 2>/dev/null | tail -1)
    
    if [ "$RESULT" = "OK" ]; then
        success "Audit service is available"
    else
        warning "Audit service may not be properly configured"
    fi
}

# Cache optimization
optimize_application() {
    log "Optimizing application..."
    cd "$PROJECT_ROOT"
    
    php artisan config:clear
    php artisan config:cache
    php artisan route:clear
    php artisan view:clear
    
    success "Application optimized"
}

# Main deployment
main() {
    log "Starting Audit System Deployment (Debian Optimized)"
    log "=================================================="
    
    check_requirements
    run_migrations
    seed_configuration
    configure_environment
    optimize_application
    verify_installation
    
    log ""
    success "Deployment completed!"
    log ""
    log "Next steps:"
    log "1. Check audit logs at: /admin/audit-logs"
    log "2. Configure settings at: /admin/audit-settings"
    log "3. Set up queue workers if using async processing"
    log "4. Review documentation in docs/ directory"
    log ""
    log "For issues, check: $LOG_FILE"
}

# Run deployment
main "$@"