#!/bin/bash

echo "🚀 Starting deployment fix..."

# Step 1: Create cache directories
echo "📁 Creating cache directories..."
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Step 2: Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Step 3: Set ownership (adjust www-data if needed)
echo "👤 Setting ownership..."
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# Step 4: Clear and rebuild caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Step 5: Generate application key if needed
echo "🔑 Checking application key..."
if grep -q "APP_KEY=$" .env; then
    echo "Generating application key..."
    php artisan key:generate
else
    echo "Application key already exists"
fi

# Step 6: Cache configuration for production
echo "⚡ Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 7: Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

echo "✅ Deployment fix completed successfully!"
echo ""
echo "Next steps:"
echo "1. Test the application"
echo "2. Check that reports work"
echo "3. Verify logo displays correctly"