# Role Management System Documentation

## Overview

The Role Management System provides a comprehensive solution for managing user roles and permissions within the ShipSharkLtd application. This system replaces hardcoded role ID checks with a scalable, name-based approach that supports dynamic role creation and management.

## Table of Contents

1. [Role Helper Methods](#role-helper-methods)
2. [User Management Interface](#user-management-interface)
3. [Role Management Interface](#role-management-interface)
4. [Permission System](#permission-system)
5. [Audit Trail](#audit-trail)
6. [Navigation Integration](#navigation-integration)
7. [Email Notifications](#email-notifications)
8. [Testing](#testing)
9. [Migration Guide](#migration-guide)

## Role Helper Methods

The User model provides comprehensive role checking methods that replace hardcoded role ID comparisons with readable, maintainable code.

### Basic Role Checking

#### `hasRole($role)`
Checks if the user has a specific role by name.

```php
// Usage examples
if ($user->hasRole('admin')) {
    // User is an admin
}

if ($user->hasRole('superadmin')) {
    // User is a superadmin
}
```

**Parameters:**
- `$role` (string): The role name to check (case-insensitive)

**Returns:** `bool` - True if user has the role, false otherwise

**Features:**
- Case-insensitive role matching
- Role caching to avoid repeated database queries
- Supports all system roles: 'superadmin', 'admin', 'customer', 'purchaser'

### Convenience Role Methods

#### `isSuperAdmin()`
Checks if the user is a superadmin.

```php
if ($user->isSuperAdmin()) {
    // User has superadmin privileges
}
```

#### `isAdmin()`
Checks if the user is an admin.

```php
if ($user->isAdmin()) {
    // User has admin privileges
}
```

#### `isCustomer()`
Checks if the user is a customer.

```php
if ($user->isCustomer()) {
    // User is a customer
}
```

#### `isPurchaser()`
Checks if the user is a purchaser.

```php
if ($user->isPurchaser()) {
    // User has purchaser privileges
}
```

### Multi-Role Checking

#### `hasAnyRole(array $roles)`
Checks if the user has any of the specified roles.

```php
// Check if user is admin or superadmin
if ($user->hasAnyRole(['admin', 'superadmin'])) {
    // User has administrative privileges
}

// Check if user can manage packages
if ($user->hasAnyRole(['admin', 'superadmin', 'purchaser'])) {
    // User can manage packages
}
```

**Parameters:**
- `$roles` (array): Array of role names to check

**Returns:** `bool` - True if user has any of the roles, false otherwise

#### `hasAllRoles(array $roles)`
Checks if the user has all of the specified roles (useful for future multi-role support).

```php
// Currently not used as users have single roles
// Reserved for future multi-role functionality
if ($user->hasAllRoles(['admin', 'manager'])) {
    // User has both admin and manager roles
}
```

### Permission Helper Methods

#### `canManageUsers()`
Checks if the user can manage other users.

```php
if ($user->canManageUsers()) {
    // User can create, edit, and manage other users
}
```

**Allowed Roles:** admin, superadmin

#### `canManageRoles()`
Checks if the user can manage roles.

```php
if ($user->canManageRoles()) {
    // User can create, edit, and delete roles
}
```

**Allowed Roles:** superadmin only

### Query Scopes

The User model provides several query scopes for efficient role-based queries:

#### `withRole($roleName)`
Scope to get users with a specific role.

```php
// Get all admin users
$admins = User::withRole('admin')->get();

// Get all customers
$customers = User::withRole('customer')->get();
```

#### `admins()`
Scope to get only admin users.

```php
$adminUsers = User::admins()->get();
```

#### `superAdmins()`
Scope to get only superadmin users.

```php
$superAdmins = User::superAdmins()->get();
```

#### `purchasers()`
Scope to get only purchaser users.

```php
$purchasers = User::purchasers()->get();
```

#### `customerUsers()`
Scope to get only customer users.

```php
$customers = User::customerUsers()->get();
```

#### `withAnyRole(array $roles)`
Scope to get users with any of the specified roles.

```php
// Get all administrative users
$adminUsers = User::withAnyRole(['admin', 'superadmin'])->get();
```

#### `withoutRole($roleName)`
Scope to get users excluding a specific role.

```php
// Get all non-customer users
$nonCustomers = User::withoutRole('customer')->get();
```

### Performance Considerations

#### Role Caching
The role system implements caching to avoid repeated database queries:

```php
// Role is cached after first access
$user->hasRole('admin'); // Database query
$user->hasRole('admin'); // Uses cached result
$user->isAdmin();        // Uses cached result
```

#### Cache Invalidation
Role cache is automatically invalidated when:
- User's role is changed
- User model is refreshed
- Role relationships are modified

## User Management Interface

### UserCreate Component

The UserCreate component allows administrators to create new users with specific roles.

#### Features
- Role selection dropdown with all available roles
- Role-specific field visibility
- Comprehensive validation
- Welcome email notifications
- Audit trail logging

#### Usage
```php
// Access via route
Route::get('/admin/users/create', UserCreate::class)->name('admin.users.create');
```

#### Role-Specific Fields
- **Customer Role**: Full profile creation (address, phone, tax number, etc.)
- **Admin/SuperAdmin Roles**: Basic user information only
- **Purchaser Role**: Basic user information with optional profile

### UserManagement Component

The UserManagement component provides a comprehensive interface for managing existing users.

#### Features
- User listing with role display
- Role filtering and search functionality
- User role modification
- Bulk operations
- Audit trail viewing

#### Usage
```php
// Access via route
Route::get('/admin/users', UserManagement::class)->name('admin.users.index');
```

### UserEdit Component

The UserEdit component allows modification of existing user roles and information.

#### Features
- Role change functionality
- Reason tracking for role changes
- Audit logging
- Permission validation

#### Usage
```php
// Access via route
Route::get('/admin/users/{user}/edit', UserEdit::class)->name('admin.users.edit');
```

## Role Management Interface

### Role Component

The enhanced Role component provides comprehensive role management capabilities.

#### Features
- Role creation, editing, and deletion
- User count display per role
- Safety checks for system roles
- Role assignment management
- Audit trail viewing

#### System Role Protection
The following roles are protected from deletion:
- superadmin
- admin
- customer
- purchaser

#### Usage
```php
// Access via route
Route::get('/admin/roles', Role::class)->name('admin.roles.index');
```

## Permission System

### Authorization Policies

#### UserPolicy
Controls access to user management operations:

```php
// Check if user can view user management
$user->can('viewAny', User::class)

// Check if user can create users
$user->can('create', User::class)

// Check if user can update specific user
$user->can('update', $targetUser)

// Check if user can delete specific user
$user->can('delete', $targetUser)
```

#### RolePolicy
Controls access to role management operations:

```php
// Check if user can manage roles
$user->can('viewAny', Role::class)

// Check if user can create roles
$user->can('create', Role::class)

// Check if user can update specific role
$user->can('update', $role)

// Check if user can delete specific role
$user->can('delete', $role)
```

### Route Protection

All management routes are protected with appropriate middleware:

```php
// User management routes (admin and superadmin)
Route::middleware(['auth', 'can:viewAny,App\Models\User'])->group(function () {
    Route::get('/admin/users', UserManagement::class);
    Route::get('/admin/users/create', UserCreate::class);
    Route::get('/admin/users/{user}/edit', UserEdit::class);
});

// Role management routes (superadmin only)
Route::middleware(['auth', 'can:viewAny,App\Models\Role'])->group(function () {
    Route::get('/admin/roles', Role::class);
});
```

## Audit Trail

### RoleChangeAudit Model

The system maintains a comprehensive audit trail of all role changes:

#### Fields
- `user_id`: The user whose role was changed
- `changed_by_user_id`: The user who made the change
- `old_role_id`: The previous role ID
- `new_role_id`: The new role ID
- `reason`: Reason for the role change
- `ip_address`: IP address of the user making the change
- `user_agent`: Browser/client information
- `created_at`: Timestamp of the change

#### Usage
```php
// Get role change history for a user
$auditTrail = $user->roleChangeAudits()->with(['oldRole', 'newRole', 'changedByUser'])->get();

// Get all role changes made by a user
$changesMade = $user->changedRoleAudits()->with(['user', 'oldRole', 'newRole'])->get();
```

### RoleChangeAuditService

The service handles automatic logging of role changes:

```php
// Automatically called when user role is changed
RoleChangeAuditService::logRoleChange($user, $oldRole, $newRole, $reason, $changedByUser);
```

## Navigation Integration

### Sidebar Navigation

The sidebar navigation is enhanced with role-based sections:

#### User Management Section (Admin and SuperAdmin)
- Create User
- Manage Users
- User Statistics

#### Role Management Section (SuperAdmin Only)
- Manage Roles
- Role Assignments
- Role Audit Trail

#### Permission-Based Visibility
Navigation items are conditionally displayed based on user permissions:

```blade
@can('viewAny', App\Models\User::class)
    <!-- User Management Section -->
    <div class="nav-section">
        <h3>User Management</h3>
        <a href="{{ route('admin.users.create') }}">Create User</a>
        <a href="{{ route('admin.users.index') }}">Manage Users</a>
    </div>
@endcan

@can('viewAny', App\Models\Role::class)
    <!-- Role Management Section -->
    <div class="nav-section">
        <h3>Role Management</h3>
        <a href="{{ route('admin.roles.index') }}">Manage Roles</a>
    </div>
@endcan
```

## Email Notifications

### User Creation Notifications

Different welcome emails are sent based on user role:

#### Customer Welcome Email
- Complete onboarding information
- Account setup instructions
- Service overview

#### Admin/Staff Welcome Email
- System access information
- Role-specific instructions
- Administrative guidelines

### Role Change Notifications

When a user's role is changed:
- User receives notification of role change
- Includes new permissions and access levels
- Provides contact information for questions

## Testing

### Unit Tests

#### Role Helper Methods
```php
// Test basic role checking
$this->assertTrue($user->hasRole('admin'));
$this->assertFalse($user->hasRole('customer'));

// Test convenience methods
$this->assertTrue($user->isAdmin());
$this->assertFalse($user->isCustomer());

// Test multi-role checking
$this->assertTrue($user->hasAnyRole(['admin', 'superadmin']));
$this->assertFalse($user->hasAnyRole(['customer', 'purchaser']));
```

#### Permission Methods
```php
// Test permission methods
$this->assertTrue($adminUser->canManageUsers());
$this->assertFalse($customerUser->canManageUsers());
$this->assertTrue($superAdminUser->canManageRoles());
$this->assertFalse($adminUser->canManageRoles());
```

### Feature Tests

#### User Creation
```php
// Test creating users with different roles
$this->actingAs($adminUser)
     ->post('/admin/users', [
         'first_name' => 'John',
         'last_name' => 'Doe',
         'email' => 'john@example.com',
         'role' => 'customer'
     ])
     ->assertRedirect();

$this->assertDatabaseHas('users', [
    'email' => 'john@example.com',
    'role_id' => $customerRole->id
]);
```

#### Role Management
```php
// Test role change functionality
$this->actingAs($superAdminUser)
     ->put("/admin/users/{$user->id}/role", [
         'role' => 'admin',
         'reason' => 'Promotion to admin'
     ])
     ->assertRedirect();

$this->assertDatabaseHas('role_change_audits', [
    'user_id' => $user->id,
    'new_role_id' => $adminRole->id,
    'reason' => 'Promotion to admin'
]);
```

### Browser Tests

#### Navigation Visibility
```php
// Test that navigation items are visible based on role
$this->browse(function (Browser $browser) {
    $browser->loginAs($adminUser)
            ->visit('/dashboard')
            ->assertSee('User Management')
            ->assertDontSee('Role Management');
            
    $browser->loginAs($superAdminUser)
            ->visit('/dashboard')
            ->assertSee('User Management')
            ->assertSee('Role Management');
});
```

## Migration Guide

### From Hardcoded Role IDs

#### Before (Hardcoded IDs)
```php
// Old approach - hardcoded role IDs
if ($user->role_id === 3) {
    // Superadmin logic
}

if (in_array($user->role_id, [1, 3])) {
    // Admin or superadmin logic
}
```

#### After (Role Names)
```php
// New approach - role names
if ($user->isSuperAdmin()) {
    // Superadmin logic
}

if ($user->hasAnyRole(['admin', 'superadmin'])) {
    // Admin or superadmin logic
}
```

### Policy Updates

#### Before
```php
public function update(User $user, Package $package)
{
    return $user->role_id === 3 || $user->role_id === 1;
}
```

#### After
```php
public function update(User $user, Package $package)
{
    return $user->hasAnyRole(['admin', 'superadmin']);
}
```

### Query Updates

#### Before
```php
$admins = User::where('role_id', 1)->get();
$superAdmins = User::where('role_id', 3)->get();
```

#### After
```php
$admins = User::admins()->get();
$superAdmins = User::superAdmins()->get();
```

## Best Practices

### Role Checking
1. Always use role names instead of IDs
2. Use convenience methods when available (`isAdmin()` vs `hasRole('admin')`)
3. Use multi-role methods for complex permission checks
4. Cache role information when performing multiple checks

### Permission Management
1. Use policies for authorization logic
2. Implement permission helper methods for common checks
3. Use middleware for route protection
4. Validate permissions at both controller and view levels

### Audit Trail
1. Always provide reasons for role changes
2. Log all administrative actions
3. Include IP address and user agent information
4. Regularly review audit logs for security

### Testing
1. Test all role combinations
2. Verify permission boundaries
3. Test edge cases and error conditions
4. Include browser tests for UI interactions

## Troubleshooting

### Common Issues

#### Role Cache Not Updating
If role changes aren't reflected immediately:
```php
// Refresh the user model to clear cache
$user->refresh();

// Or manually clear role cache
$user->roleCache = null;
```

#### Permission Denied Errors
Check that:
1. User has the correct role assigned
2. Policies are properly configured
3. Routes have correct middleware
4. Role names match exactly (case-insensitive)

#### Email Notifications Not Sending
Verify:
1. Mail configuration is correct
2. Queue workers are running (if using queues)
3. Email templates exist for all user types
4. User has valid email address

### Debugging

#### Check User Role
```php
// Debug user role information
dd([
    'user_id' => $user->id,
    'role_id' => $user->role_id,
    'role_name' => $user->role->name ?? 'No role',
    'is_admin' => $user->isAdmin(),
    'is_superadmin' => $user->isSuperAdmin(),
    'can_manage_users' => $user->canManageUsers(),
]);
```

#### Verify Permissions
```php
// Check specific permissions
dd([
    'can_view_users' => $user->can('viewAny', User::class),
    'can_create_users' => $user->can('create', User::class),
    'can_manage_roles' => $user->can('viewAny', Role::class),
]);
```

## Security Considerations

### Role Assignment Security
- Only superadmins can assign superadmin role
- Admins cannot elevate their own privileges
- Role changes are logged and audited
- IP address and user agent tracking

### Data Protection
- Role names are validated and sanitized
- Audit trail is immutable
- Sensitive operations require additional confirmation
- Session invalidation on critical role changes

### Access Control
- All management interfaces require authentication
- Permission checks at multiple levels
- Route protection with middleware
- CSRF protection on all forms

## API Reference

### User Model Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `hasRole($role)` | string $role | bool | Check if user has specific role |
| `isAdmin()` | none | bool | Check if user is admin |
| `isSuperAdmin()` | none | bool | Check if user is superadmin |
| `isCustomer()` | none | bool | Check if user is customer |
| `isPurchaser()` | none | bool | Check if user is purchaser |
| `hasAnyRole($roles)` | array $roles | bool | Check if user has any of the roles |
| `hasAllRoles($roles)` | array $roles | bool | Check if user has all roles |
| `canManageUsers()` | none | bool | Check if user can manage other users |
| `canManageRoles()` | none | bool | Check if user can manage roles |

### Query Scopes

| Scope | Parameters | Description |
|-------|------------|-------------|
| `withRole($role)` | string $role | Users with specific role |
| `admins()` | none | Admin users only |
| `superAdmins()` | none | Superadmin users only |
| `purchasers()` | none | Purchaser users only |
| `customerUsers()` | none | Customer users only |
| `withAnyRole($roles)` | array $roles | Users with any of the roles |
| `withoutRole($role)` | string $role | Users without specific role |

### Livewire Components

| Component | Route | Description |
|-----------|-------|-------------|
| `UserCreate` | `/admin/users/create` | Create new users |
| `UserManagement` | `/admin/users` | Manage existing users |
| `UserEdit` | `/admin/users/{user}/edit` | Edit user details |
| `Role` | `/admin/roles` | Manage roles |

This documentation provides comprehensive coverage of the role management system, including usage examples, best practices, and troubleshooting guides.