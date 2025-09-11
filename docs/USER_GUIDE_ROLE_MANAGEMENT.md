# User Guide: Role and User Management

## Table of Contents

1. [Getting Started](#getting-started)
2. [User Management](#user-management)
3. [Role Management](#role-management)
4. [Common Tasks](#common-tasks)
5. [Troubleshooting](#troubleshooting)

## Getting Started

### Who Can Access Role Management?

The role and user management features are available based on your user role:

- **Customers**: No access to management features
- **Purchasers**: No access to management features  
- **Admins**: Can manage users but not roles
- **SuperAdmins**: Full access to both user and role management

### Accessing Management Features

1. Log in to your account
2. Navigate to the sidebar menu
3. Look for the "User Management" and "Role Management" sections
4. Click on the desired management option

**Note**: If you don't see these sections, you may not have the required permissions.

## User Management

### Creating New Users

#### Step 1: Navigate to User Creation
1. Click on "User Management" in the sidebar
2. Select "Create User"

#### Step 2: Fill User Information
1. **Basic Information**:
   - First Name (required)
   - Last Name (required)
   - Email Address (required, must be unique)
   - Password (required, minimum 8 characters)

2. **Role Selection**:
   - Choose from available roles: Customer, Admin, Purchaser, SuperAdmin
   - Role determines what features the user can access

#### Step 3: Role-Specific Fields

**For Customer Role**:
- Tax Number
- Telephone Number
- Parish/State
- Street Address
- City/Town
- Country
- Pickup Location

**For Admin/SuperAdmin/Purchaser Roles**:
- Only basic information is required
- Profile information is optional

#### Step 4: Create User
1. Review all information
2. Click "Create User"
3. The system will:
   - Create the user account
   - Send a welcome email
   - Log the creation in audit trail

### Managing Existing Users

#### Viewing All Users
1. Go to "User Management" → "Manage Users"
2. You'll see a table with all users showing:
   - Name and email
   - Current role (displayed as colored badge)
   - Registration date
   - Status (Active/Deleted)

#### Searching for Users
Use the search box to find users by:
- First or last name
- Email address
- Account number (for customers)
- Tax number (for customers)
- Phone number (for customers)

#### Filtering Users
- **By Role**: Use the role filter dropdown
- **By Status**: Filter active or deleted users
- **By Registration Date**: Set date ranges

#### Editing User Information
1. Find the user in the list
2. Click the "Edit" button
3. Modify the information as needed
4. Save changes

#### Changing User Roles
1. Open the user edit form
2. Select the new role from the dropdown
3. **Important**: Provide a reason for the role change
4. Click "Update Role"
5. The system will:
   - Update the user's role immediately
   - Log the change in the audit trail
   - Send notification to the user (if configured)

#### Deactivating Users
1. Find the user in the list
2. Click "Deactivate" or "Delete"
3. Confirm the action
4. The user will be soft-deleted (can be restored later)

### User Statistics

View comprehensive statistics about your users:
- Total users by role
- Recent registrations
- Active vs inactive users
- Growth trends

## Role Management

**Note**: Role management is only available to SuperAdmins.

### Viewing Existing Roles

1. Navigate to "Role Management" → "Manage Roles"
2. You'll see all roles with:
   - Role name and description
   - Number of users assigned to each role
   - System role indicator (for protected roles)

### Creating New Roles

1. Click "Create New Role"
2. Enter role information:
   - **Name**: Unique role identifier (e.g., "warehouse_manager")
   - **Description**: Human-readable description
3. Click "Create Role"

**Important**: New roles won't have any permissions by default. You'll need to update your application code to handle new role permissions.

### Editing Roles

1. Find the role in the list
2. Click "Edit"
3. Modify the name or description
4. Save changes

**Note**: You cannot edit system roles (SuperAdmin, Admin, Customer, Purchaser).

### Deleting Roles

1. Find the role in the list
2. Click "Delete"
3. Confirm the deletion

**Restrictions**:
- Cannot delete system roles
- Cannot delete roles that have users assigned
- Must reassign users to different roles first

### Role Assignments

View and manage which users are assigned to each role:
1. Click on a role name
2. See all users with that role
3. Reassign users to different roles if needed

### Role Audit Trail

View the complete history of role changes:
1. Go to "Role Management" → "Role Audit Trail"
2. See all role changes with:
   - Who made the change
   - When it was made
   - What changed (old role → new role)
   - Reason for the change
   - IP address and browser information

## Common Tasks

### Promoting a Customer to Admin

1. Go to "User Management" → "Manage Users"
2. Search for the customer by name or email
3. Click "Edit" next to their name
4. Change role from "Customer" to "Admin"
5. Provide reason: "Promoted to admin role"
6. Click "Update Role"
7. Verify the change in the audit trail

### Creating a New Admin User

1. Go to "User Management" → "Create User"
2. Fill in basic information
3. Select "Admin" as the role
4. Complete the form (profile information not required for admins)
5. Click "Create User"
6. The new admin will receive a welcome email

### Bulk Role Changes

Currently, role changes must be done individually. For bulk changes:
1. Create a list of users to change
2. Process each user individually through the edit interface
3. Provide consistent reasons for audit purposes

### Restoring Deleted Users

1. Go to "User Management" → "Manage Users"
2. Change status filter to "Deleted"
3. Find the deleted user
4. Click "Restore"
5. The user will be reactivated

### Finding Recent Role Changes

1. Go to "Role Management" → "Role Audit Trail"
2. Sort by date (newest first)
3. Review recent changes
4. Contact users if needed to explain changes

## Troubleshooting

### Common Issues

#### "Access Denied" When Trying to Manage Users
**Problem**: You don't have sufficient permissions.
**Solution**: 
- Check your role (must be Admin or SuperAdmin)
- Contact a SuperAdmin to verify your permissions
- Log out and log back in if role was recently changed

#### User Not Receiving Welcome Email
**Problem**: Email not delivered after user creation.
**Solutions**:
- Verify the email address is correct
- Check spam/junk folders
- Contact system administrator about email configuration
- Manually send login credentials if needed

#### Cannot Delete a Role
**Problem**: "Cannot delete role with assigned users" error.
**Solution**:
- First reassign all users to different roles
- Then delete the empty role
- System roles cannot be deleted at all

#### Role Changes Not Taking Effect
**Problem**: User still has old permissions after role change.
**Solutions**:
- Ask user to log out and log back in
- Clear browser cache
- Wait a few minutes for system to update
- Contact system administrator if problem persists

#### Search Not Finding Users
**Problem**: Cannot find users with search function.
**Solutions**:
- Try partial names instead of full names
- Search by email address instead
- Check if user is deleted (change status filter)
- Try different search terms

### Getting Help

#### For Technical Issues
- Contact your system administrator
- Check the system logs for error messages
- Provide specific error messages when reporting issues

#### For Permission Issues
- Contact a SuperAdmin to review your role
- Verify you're using the correct login credentials
- Check if your account has been deactivated

#### For Training
- Review this user guide
- Ask experienced users for demonstrations
- Practice with test accounts (if available)
- Contact your supervisor for additional training

### Best Practices

#### When Creating Users
- Double-check email addresses for accuracy
- Choose appropriate roles based on job function
- Include complete profile information for customers
- Use strong, unique passwords

#### When Changing Roles
- Always provide clear reasons for changes
- Notify users about role changes
- Document business justification
- Review changes periodically

#### For Security
- Regularly review user roles and permissions
- Remove access for departed employees immediately
- Monitor the audit trail for suspicious changes
- Use strong passwords and enable two-factor authentication

#### For Maintenance
- Periodically review and clean up unused roles
- Archive or delete old user accounts
- Keep role descriptions up to date
- Document any custom roles and their purposes

## Quick Reference

### Keyboard Shortcuts
- **Ctrl+F**: Search within user lists
- **Enter**: Submit forms
- **Esc**: Cancel operations

### Status Indicators
- 🟢 **Green Badge**: Active user
- 🔴 **Red Badge**: Deleted user
- 🔵 **Blue Badge**: Admin role
- 🟡 **Yellow Badge**: Customer role
- 🟣 **Purple Badge**: SuperAdmin role

### Role Hierarchy
1. **SuperAdmin**: Full system access
2. **Admin**: User management, most features
3. **Purchaser**: Package management, limited admin features
4. **Customer**: Basic user, package tracking only

### Important Notes
- Role changes take effect immediately
- All changes are logged in the audit trail
- System roles cannot be deleted
- Users can only have one role at a time
- Email notifications may be delayed depending on system configuration

For additional help or questions not covered in this guide, please contact your system administrator or SuperAdmin.