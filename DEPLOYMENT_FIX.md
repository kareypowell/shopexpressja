# Fix for Business Reports Cache Directory Issue

## Problem
The Business Reports are failing with the error:
```
file_put_contents(/var/www/html/app.shopexpressja.com/storage/framework/cache/data/...): failed to open stream: No such file or directory
```

This happens because the Laravel cache directory structure doesn't exist on the production server.

## Quick Fix (Run on Production Server)

### Option 1: Use the provided script
```bash
# Make the script executable
chmod +x fix-cache-directories.sh

# Run the script
./fix-cache-directories.sh
```

### Option 2: Manual commands
```bash
# Create cache directories
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions  
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Set ownership (adjust www-data if your web server uses a different user)
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### Option 3: Use the new Artisan command
```bash
php artisan cache:fix-directories
```

## After fixing directories, run these commands:
```bash
php artisan config:cache
php artisan route:cache  
php artisan view:cache
```

## Test the fix

### Option 1: Use the test command
```bash
php artisan reports:test-system
```

### Option 2: Manual testing
1. Go to the Business Reports page
2. The reports should now load without the "Unable to load reports" error
3. Check that all tabs (Sales & Collections, Manifest Performance, etc.) work

## Prevention for Future Deployments

Add this to your deployment script:
```bash
# Ensure cache directories exist
php artisan cache:fix-directories

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## What was changed in the code:

1. **Added error handling** to cache operations in:
   - `ReportMonitoringService.php` 
   - `ReportErrorHandlingService.php`

2. **Created new Artisan command** `cache:fix-directories` to handle this automatically

3. **Added fallback mechanisms** so reports can still work even if cache fails

The reports should now be more resilient to cache directory issues and provide better error handling.