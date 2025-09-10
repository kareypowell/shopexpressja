# Design Document

## Overview

The role management enhancement will transform the current hardcoded role ID system into a scalable, name-based role management system. The design focuses on maintaining backward compatibility while introducing new role-based helper methods, administrative interfaces for user creation with role assignment, and comprehensive role management capabilities.

## Architecture

### Current State Analysis
- Role system exists with `roles` table containing: superadmin, admin, customer, purchaser
- User model has `hasRole()` method that works with role names
- Customer creation component exists but is limited to customer role only
- Role checking is inconsistent across the application

### Enhanced Architecture
The enhancement will build upon the existing foundation with these key improvements:

1. **Enhanced User Model**: Add comprehensive role helper methods
2. **Administrative User Creation**: Extend existing customer creation to support all roles
3. **Role Management Interface**: Enhance existing role management components
4. **Consistent Permission Checking**: Standardize role-based authorization across the application

## Components and Interfaces

### 1. Enhanced User Model Methods

#### Role Helper Methods
```php
// Existing method (enhanced)
public function hasRole($role): bool

// New convenience methods
public function isAdmin(): bool
public function isSuperAdmin(): bool 
public function isPurchaser(): bool
public function isCustomer(): bool

// New multi-role checking
public function hasAnyRole(array $roles): bool
public function hasAllRoles(array $roles): bool

// Role-based scopes
public function scopeWithRole($query, $roleName)
public function scopeAdmins($query)
public function scopePurchasers($query)
```

#### Caching Strategy
- Cache role information on the User model to avoid repeated database queries
- Implement role caching with automatic invalidation on role changes
- Use Laravel's model caching for frequently accessed role data

### 2. Administrative User Creation Component

#### Enhanced CustomerCreate Component
Transform the existing `CustomerCreate` component into a more generic `UserCreate` component:

```php
class UserCreate extends Component
{
    // Existing customer fields
    public $firstName, $lastName, $email, $password;
    
    // New role selection
    public $selectedRole = 'customer';
    public $availableRoles = [];
    
    // Role-specific field visibility
    public $showCustomerFields = true;
    public $showAdminFields = false;
}
```

#### Role-Specific Field Management
- Customer role: Full profile creation (address, phone, etc.)
- Admin/SuperAdmin roles: Basic user information only
- Purchaser role: Basic user information with optional profile
- Dynamic form fields based on selected role

### 3. User Management Interface

#### Enhanced User Listing
- Display user roles clearly in user tables
- Filter users by role
- Bulk role assignment capabilities
- Role change audit logging

#### User Edit Component
```php
class UserEdit extends Component
{
    public $user;
    public $currentRole;
    public $newRole;
    public $roleChangeReason;
    
    public function changeRole()
    public function logRoleChange()
}
```

### 4. Role Management Component Enhancement

#### Enhanced Role Component
```php
class Role extends Component
{
    public $roles;
    public $selectedRole;
    public $userCounts = [];
    
    public function getUserCountByRole($roleId)
    public function createRole()
    public function updateRole()
    public function deleteRole() // With safety checks
}
```

### 5. Navigation Enhancement

#### Sidebar Navigation Updates
The sidebar navigation (`sidebar-nav.blade.php`) will be enhanced to include:

- **User Management Section**: Links to create users, manage existing users, and view user statistics
- **Role Management Section**: Links to manage roles, view role assignments, and role audit trails
- **Permission-based Visibility**: Navigation items will only be visible to users with appropriate permissions

#### Navigation Structure
```php
// Admin section (visible to admin and superadmin)
- User Management
  - Create User
  - Manage Users
  - User Statistics
  
- Role Management (visible to superadmin only)
  - Manage Roles
  - Role Assignments
  - Role Audit Trail
```

#### Permission-based Navigation
- Use role-based conditionals to show/hide navigation items
- Implement consistent permission checking across all navigation elements
- Ensure navigation reflects user's actual permissions

## Data Models

### User Model Enhancements
```php
class User extends Authenticatable
{
    // Existing relationships and methods...
    
    // Enhanced role caching
    protected $roleCache = null;
    
    // New role helper methods
    public function getRoleAttribute()
    {
        if ($this->roleCache === null) {
            $this->roleCache = $this->role()->first();
        }
        return $this->roleCache;
    }
    
    // Role-based authorization helpers
    public function canManageUsers(): bool
    public function canManageRoles(): bool
    public function canAccessAdminPanel(): bool
}
```

### Role Model Enhancements
```php
class Role extends Model
{
    protected $fillable = ['name', 'description'];
    
    // New methods
    public function getUserCount(): int
    public function isSystemRole(): bool // For superadmin, admin, customer
    public function canBeDeleted(): bool
    
    // Scopes
    public function scopeSystemRoles($query)
    public function scopeCustomRoles($query)
}
```

### Audit Trail Model
```php
class RoleChangeAudit extends Model
{
    protected $fillable = [
        'user_id',
        'changed_by_user_id', 
        'old_role_id',
        'new_role_id',
        'reason',
        'ip_address',
        'user_agent'
    ];
    
    protected $casts = [
        'created_at' => 'datetime'
    ];
}
```

## Error Handling

### Role Assignment Validation
- Validate role exists before assignment
- Prevent role changes that would leave system without superadmin
- Validate user permissions before allowing role changes
- Handle role assignment failures gracefully

### User Creation Error Handling
- Comprehensive validation for role-specific fields
- Transaction rollback on any creation failure
- Clear error messages for role-related failures
- Email notification failure handling (non-blocking)

### Role Management Safety
- Prevent deletion of system roles (superadmin, admin, customer)
- Prevent deletion of roles with assigned users
- Validate role name uniqueness
- Handle concurrent role modifications

## Testing Strategy

### Unit Tests
- User model role helper methods
- Role validation logic
- Permission checking methods
- Role caching functionality

### Feature Tests
- User creation with different roles
- Role assignment and changes
- Permission-based access control
- Role management CRUD operations

### Integration Tests
- Complete user creation workflow
- Role-based authorization across components
- Email notifications for different user types
- Audit trail logging

### Browser Tests
- User creation form with role selection
- Role change interface
- Permission-based UI element visibility
- Role management interface interactions

## Security Considerations

### Authorization Checks
- Verify user permissions before role operations
- Implement role hierarchy (superadmin > admin > customer)
- Prevent privilege escalation attacks
- Audit all role changes

### Data Protection
- Sanitize role names and descriptions
- Validate role assignments against business rules
- Protect against mass assignment vulnerabilities
- Secure role change audit trail

### Session Management
- Refresh user permissions after role changes
- Handle role changes for active sessions
- Implement proper logout on critical role changes
- Cache invalidation on role modifications

## Performance Optimizations

### Database Optimization
- Index role_id column for efficient queries
- Optimize role-based user queries
- Implement efficient role counting
- Cache frequently accessed role data

### Caching Strategy
- Cache user roles to reduce database queries
- Implement role-based permission caching
- Use Laravel's query result caching
- Automatic cache invalidation on role changes

### Query Optimization
- Eager load roles with users when needed
- Optimize role-based scopes
- Minimize N+1 queries in role checking
- Efficient bulk role operations