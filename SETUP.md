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