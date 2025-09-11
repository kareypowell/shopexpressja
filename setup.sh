#!/bin/bash

# ShipShark Setup Script
# This script sets up the application with fresh migrations and a superadmin account

echo "🚀 Setting up ShipShark application..."

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please copy .env.example to .env and configure your database settings."
    exit 1
fi

# Install dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "📦 Installing Node.js dependencies..."
npm install

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Run migrations and seeders
echo "🗄️  Running database migrations and seeders..."
php artisan migrate:fresh --seed

# Build assets
echo "🎨 Building frontend assets..."
npm run prod

# Clear and cache config
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅ Setup complete!"
echo ""
echo "🎯 Default SuperAdmin Account:"
echo "   Email: admin@shipshark.com"
echo "   Password: password"
echo ""
echo "⚠️  IMPORTANT: Please change the default password after first login!"
echo ""
echo "🚀 You can now start the application with:"
echo "   php artisan serve"
echo ""
echo "💡 To create additional superadmin accounts, use:"
echo "   php artisan admin:create-superadmin"