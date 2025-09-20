# Role-Based Access Control (RBAC) Implementation

## Overview

This document describes the role-based access control system implemented for ShipSharkLtd. The system restricts regular admins to specific sections while allowing superadmins full system access.

## User Roles

### Superadmin
- **Full system access** - Can access all features and sections
- **Role management** - Can create, edit, and delete roles
- **Backup management** - Can access backup dashboard, history, and settings
- **User management** - Can manage all users including other admins
- **All other admin features** - Complete access to operations, customers, financial, analytics

### Admin (Regular)
- **Limited admin access** - Restricted to specific sections only
- **Dashboard** - Full access to admin dashboard
- **Operations** - Package distribution, manifests
- **Customer Management** - Customer records, broadcast messages, pre-alerts
- **Financial** - Purchase requests, transactions, rates (full access)
- **Analytics** - Reports and analytics
- **Administration (Limited)** - User management, offices, shipping addresses only
- **No access to** - Role management, backup management

### Customer
- **Customer portal access** - Self-service features only
- **Package tracking** - View own packages and shipments
- **Account management** - Manage personal profile and settings

## Implementation Details

### Middleware
- `AdminRoleRestriction` middleware protects superadmin-only routes
- Applied to role management and backup management routes
- Returns 403 error for unauthorized access attempts

### User Model Methods
```php
// Check specific permissions
$user->canAccessRoleManagement()      // Superadmin only
$user->canAccessBackupManagement()    // Superadmin only
$user->canAccessAdministration()      // Both admin and superadmin

// Get allowed sections
$user->getAllowedAdministrationSections()  // Returns array of allowed sections
```

### Navigation Control
- Sidebar navigation conditionally shows sections based on user permissions
- Uses helper methods to determine visibility
- Gracefully handles permission checks

### Route Protection
```php
// Superadmin-only routes
Route::get('/roles', Role::class)->middleware(['can:role.viewAny', 'admin.restriction']);
Route::get('/backup-dashboard', BackupDashboard::class)->middleware('admin.restriction');
```

## Testing

### Test Users
Create test users with the artisan command:
```bash
php artisan users:create-test
```

This creates:
- `superadmin@test.com` / `password` (Superadmin role)
- `admin@test.com` / `password` (Admin role)  
- `customer@test.com` / `password` (Customer role)

### Role Test Page
Access the role test page at `/admin/test/role-access` (development environments only) to:
- View current user role and permissions
- Test access to different sections
- Verify middleware protection works correctly

### Manual Testing
1. Login as regular admin (`admin@test.com`)
2. Verify you can access:
   - Dashboard
   - Operations (Package Distribution, Manifests)
   - Customer Management (Customers, Broadcast Messages, Pre-Alerts)
   - Financial (Purchase Requests, Transactions, Rates)
   - Analytics
   - Administration > User Management
   - Administration > Offices
   - Administration > Shipping Addresses

3. Verify you CANNOT access:
   - Administration > Role Management (should not appear in menu)
   - Administration > Backup Management (should not appear in menu)
   - Direct URLs to `/admin/roles` or `/admin/backup-*` (should return 403)

4. Login as superadmin (`superadmin@test.com`)
5. Verify you can access ALL sections including role and backup management

## Security Considerations

### Defense in Depth
- **Route-level protection** - Middleware blocks unauthorized requests
- **Navigation-level protection** - UI elements hidden based on permissions  
- **Policy-level protection** - Laravel policies provide additional authorization
- **Database-level protection** - Role relationships enforce data access

### Best Practices
- Always check permissions in both UI and backend
- Use middleware for route protection
- Implement proper error handling for unauthorized access
- Log security-related events for auditing
- Regular review of user roles and permissions

## Maintenance

### Adding New Restricted Features
1. Add route protection with `admin.restriction` middleware
2. Update `AdminRoleRestriction` middleware if needed
3. Add permission check methods to User model
4. Update navigation templates with permission checks
5. Test with both admin and superadmin users

### Modifying Permissions
1. Update the `getAllowedAdministrationSections()` method in User model
2. Update navigation templates accordingly
3. Test thoroughly with affected user roles
4. Update documentation

## Troubleshooting

### Common Issues
- **403 errors for admins** - Check if route has correct middleware applied
- **Menu items not showing** - Verify permission check methods in navigation
- **Middleware not working** - Ensure middleware is registered in Kernel.php
- **Role checks failing** - Verify user has correct role assigned in database

### Debug Steps
1. Check user's role: `auth()->user()->role->name`
2. Check allowed sections: `auth()->user()->getAllowedAdministrationSections()`
3. Verify middleware registration in `app/Http/Kernel.php`
4. Check route definitions in `routes/web.php`
5. Use role test page for comprehensive testing