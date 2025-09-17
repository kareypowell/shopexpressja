# ShipShark Setup Guide

This guide will help you set up the ShipShark application from scratch.

## Prerequisites

- PHP 7.3+ or 8.0+
- Composer
- Node.js and NPM
- MySQL or MariaDB
- Web server (Apache/Nginx) or use Laravel's built-in server

## Quick Setup

### Option 1: Automated Setup (Recommended)

**For Linux/macOS:**
```bash
./setup.sh
```

**For Windows:**
```cmd
setup.bat
```

### Option 2: Manual Setup

1. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
   Edit `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=shipshark
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

3. **Database Setup**
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Build Assets**
   ```bash
   npm run prod
   ```

5. **Optimize Application**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Default SuperAdmin Account

After running the setup, a default superadmin account will be created:

- **Email:** `admin@shipshark.com`
- **Password:** `password`

⚠️ **IMPORTANT:** Change this password immediately after first login!

## Creating Additional SuperAdmin Accounts

You can create additional superadmin accounts using the Artisan command:

```bash
# Interactive mode
php artisan admin:create-superadmin

# With parameters
php artisan admin:create-superadmin --email=admin@example.com --name="John Doe" --password=secure123

# Force overwrite existing user
php artisan admin:create-superadmin --email=admin@example.com --force
```

## Backup System Setup

The application includes a comprehensive backup management system. After completing the basic setup, configure the backup system:

### 1. Environment Configuration

Add backup configuration to your `.env` file:

```env
# Backup Storage Configuration
BACKUP_STORAGE_PATH=storage/app/backups
BACKUP_DATABASE_RETENTION_DAYS=30
BACKUP_FILES_RETENTION_DAYS=14
BACKUP_MAX_FILE_SIZE=2048

# Database Backup Settings
DB_BACKUP_TIMEOUT=300
DB_BACKUP_SINGLE_TRANSACTION=true

# Notification Settings
BACKUP_NOTIFICATION_EMAIL=admin@example.com
BACKUP_NOTIFY_ON_FAILURE=true
```

### 2. Initialize Backup System

```bash
# Seed backup settings
php artisan db:seed --class=BackupSettingsSeeder

# Create backup directory with proper permissions
mkdir -p storage/app/backups
chmod 755 storage/app/backups
```

### 3. Test Backup System

```bash
# Create a test backup
php artisan backup:create --database

# Check backup status
php artisan backup:status

# Verify backup files
ls -la storage/app/backups/
```

### 4. Configure Automated Backups

For automated backups, ensure the Laravel scheduler is configured in your crontab:

```bash
# Add to crontab (crontab -e)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

Then configure automated backups through the admin interface:
1. Login as an administrator
2. Navigate to **System** → **Backup Management** → **Settings**
3. Enable automated backups and set your preferred schedule

### 5. Backup System Documentation

For detailed backup system information, see:
- [Backup User Guide](docs/BACKUP_USER_GUIDE.md)
- [Backup Admin Guide](docs/BACKUP_ADMIN_GUIDE.md)
- [Backup Deployment Guide](docs/BACKUP_DEPLOYMENT_GUIDE.md)

## Starting the Application

### Development Server
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Production Server
Configure your web server to point to the `public` directory.

## Troubleshooting

### Database Connection Issues
- Verify your database credentials in `.env`
- Ensure your database server is running
- Check that the database exists

### Permission Issues (Linux/macOS)
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Asset Build Issues
```bash
# Clear npm cache
npm cache clean --force

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install

# Rebuild assets
npm run dev
```

### Cache Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear compiled views
php artisan view:clear
```

## Role Management

The application includes a comprehensive role management system with the following default roles:

- **SuperAdmin:** Full system access and user management
- **Admin:** Administrative access to manage operations
- **Customer:** Customer portal access
- **Purchaser:** Purchasing and procurement access

### Managing Roles

SuperAdmins can:
- Create, edit, and delete roles
- Assign roles to users
- View role change audit logs
- Manage user accounts

### Role Permissions

Each role has specific permissions defined in the application policies. The role system uses:
- Role-based authorization
- Policy-based access control
- Audit logging for role changes
- Unique role name constraints

## Support

For additional help or issues:
1. Check the application logs in `storage/logs/laravel.log`
2. Review the troubleshooting section above
3. Consult the Laravel documentation for framework-specific issues