# Implementation Plan

- [x] 1. Enhance User model with role helper methods
  - Add comprehensive role checking methods (isAdmin, isSuperAdmin, isPurchaser, isCustomer)
  - Implement role caching to avoid repeated database queries
  - Add multi-role checking methods (hasAnyRole, hasAllRoles)
  - Create role-based query scopes (scopeAdmins, scopePurchasers)
  - Write unit tests for all new role helper methods
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 4.1, 4.2, 4.3, 4.4_

- [x] 2. Create role change audit system
  - Create RoleChangeAudit migration with user_id, changed_by_user_id, old_role_id, new_role_id, reason, ip_address, user_agent fields
  - Implement RoleChangeAudit model with appropriate relationships and casts
  - Create service class for logging role changes with IP and user agent tracking
  - Write unit tests for audit logging functionality
  - _Requirements: 3.4, 6.1, 6.2_

- [x] 3. Transform CustomerCreate component into UserCreate component
  - Rename CustomerCreate to UserCreate and update class references
  - Add role selection dropdown with all available roles (admin, purchaser, customer, superadmin)
  - Implement role-specific field visibility (customer fields for customers, basic fields for admin/purchaser)
  - Update validation rules to handle different role requirements
  - Modify user creation logic to support all role types
  - Update welcome email logic to handle different user types appropriately
  - Write feature tests for creating users with different roles
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 4. Create user management interface
  - Create UserManagement Livewire component for listing all users with their roles
  - Add role filtering and search functionality to user listing
  - Implement user role display in user tables with clear role badges
  - Create UserEdit component for modifying existing user roles
  - Add role change functionality with reason tracking and audit logging
  - Write feature tests for user management operations
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [ ] 5. Enhance Role management component
  - Update existing Role component to display user counts per role
  - Add role creation, editing, and deletion functionality with safety checks
  - Implement protection against deleting system roles or roles with assigned users
  - Add role assignment management interface
  - Create role audit trail viewing functionality
  - Write unit and feature tests for role management operations
  - _Requirements: 1.1, 6.1, 6.2_

- [ ] 6. Update sidebar navigation with role-based links
  - Modify sidebar-nav.blade.php to include User Management section (Create User, Manage Users, User Statistics)
  - Add Role Management section visible only to superadmins (Manage Roles, Role Assignments, Role Audit Trail)
  - Implement permission-based visibility using role helper methods
  - Ensure navigation links route to appropriate management interfaces
  - Write browser tests for navigation visibility based on user roles
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [ ] 7. Create routes and policies for user and role management
  - Define routes for user creation, editing, listing, and role management
  - Create or update UserPolicy with methods for user management permissions
  - Create RolePolicy for role management permissions
  - Implement proper authorization checks in all management components
  - Write tests for policy authorization methods
  - _Requirements: 1.1, 3.1, 5.4, 6.3_

- [ ] 8. Update existing code to use role helper methods
  - Replace any hardcoded role ID checks with role name-based methods
  - Update policies, middleware, and components to use new role helper methods
  - Ensure consistent role checking across the entire application
  - Update existing scopes and queries to use role names instead of IDs
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 6.3_

- [ ] 9. Update and fix existing tests
  - Review all existing tests for hardcoded role ID usage
  - Update tests to use role names and helper methods instead of IDs
  - Remove or update unrealistic test conditions to reflect actual business scenarios
  - Add comprehensive test coverage for new role management functionality
  - Ensure all existing functionality continues to work with new role system
  - _Requirements: 6.2, 6.3, 6.4_

- [ ] 10. Create comprehensive documentation and validation
  - Document new role helper methods and their usage
  - Create user guide for role and user management interfaces
  - Validate that all role-based permissions work correctly across the application
  - Perform integration testing to ensure backward compatibility
  - Test email notifications for different user types
  - _Requirements: 1.4, 2.4, 6.1, 6.3_