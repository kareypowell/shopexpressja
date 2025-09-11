@echo off
REM ShipShark Setup Script for Windows
REM This script sets up the application with fresh migrations and a superadmin account

echo 🚀 Setting up ShipShark application...

REM Check if .env file exists
if not exist .env (
    echo ❌ .env file not found. Please copy .env.example to .env and configure your database settings.
    pause
    exit /b 1
)

REM Install dependencies
echo 📦 Installing PHP dependencies...
composer install --no-dev --optimize-autoloader

echo 📦 Installing Node.js dependencies...
npm install

REM Generate application key if not set
findstr /C:"APP_KEY=base64:" .env >nul
if errorlevel 1 (
    echo 🔑 Generating application key...
    php artisan key:generate
)

REM Run migrations and seeders
echo 🗄️  Running database migrations and seeders...
php artisan migrate:fresh --seed

REM Build assets
echo 🎨 Building frontend assets...
npm run prod

REM Clear and cache config
echo ⚡ Optimizing application...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo.
echo ✅ Setup complete!
echo.
echo 🎯 Default SuperAdmin Account:
echo    Email: admin@shipshark.com
echo    Password: password
echo.
echo ⚠️  IMPORTANT: Please change the default password after first login!
echo.
echo 🚀 You can now start the application with:
echo    php artisan serve
echo.
echo 💡 To create additional superadmin accounts, use:
echo    php artisan admin:create-superadmin
echo.
pause