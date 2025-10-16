#!/bin/bash

# Fix Laravel cache directory structure and permissions
# Run this script on your production server

echo "Creating Laravel cache directory structure..."

# Create the cache directory structure
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

echo "Setting proper permissions..."

# Set proper permissions for storage and bootstrap cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Set proper ownership (adjust www-data to your web server user if different)
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

echo "Cache directories created and permissions set!"
echo ""
echo "Next steps:"
echo "1. Run: php artisan config:cache"
echo "2. Run: php artisan route:cache"
echo "3. Run: php artisan view:cache"
echo "4. Test the reports functionality"