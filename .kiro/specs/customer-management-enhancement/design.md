# Design Document

## Overview

This design document outlines the architecture for enhancing the customer management system in the Laravel/Livewire application. The enhancement will transform the current table-only view into a comprehensive customer management system with detailed profiles, editing capabilities, ad-hoc creation, and soft deletion functionality.

The design follows the existing application patterns using Laravel models, Livewire components, and Blade templates while maintaining consistency with the current UI/UX design language.

## Architecture

### Component Structure

The customer management enhancement will follow a modular Livewire component architecture:

```
app/Http/Livewire/Customers/
├── AdminCustomer.php (main container - enhanced)
├── AdminCustomersTable.php (existing - enhanced)
├── CustomerProfile.php (new - detailed view)
├── CustomerEdit.php (new - edit form)
├── CustomerCreate.php (new - creation form)
└── CustomerPackagesTable.php (existing - enhanced)

app/Services/
└── AccountNumberService.php (extracted from Register.php)
```

### Database Schema Changes

The existing User and Profile models will be enhanced with soft delete functionality:

```sql
-- Add soft deletes to users table
ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL;

-- Add indexes for performance
CREATE INDEX idx_users_deleted_at ON users(deleted_at);
CREATE INDEX idx_users_role_deleted ON users(role_id, deleted_at);
```

### Route Structure

New routes will be added to support the enhanced functionality:

```php
// Customer management routes (admin only)
Route::get('/admin/customers', AdminCustomer::class)->name('admin.customers');
Route::get('/admin/customers/{user}', CustomerProfile::class)->name('admin.customers.profile');
Route::get('/admin/customers/{user}/edit', CustomerEdit::class)->name('admin.customers.edit');
Route::get('/admin/customers/create', CustomerCreate::class)->name('admin.customers.create');
```

## Components and Interfaces

### 1. Enhanced AdminCustomer Component

**Purpose:** Main container component for customer management
**Responsibilities:**
- Route handling and navigation
- State management for customer operations
- Integration with child components

**Key Methods:**
```php
public function mount()
public function render()
public function showCustomer($customerId)
public function createCustomer()
```

### 2. Enhanced AdminCustomersTable Component

**Purpose:** Enhanced table view with additional actions
**Enhancements:**
- Add "View Profile" action column
- Add "Edit" action column  
- Add "Delete" action with confirmation
- Add filter for active/deleted customers
- Add bulk actions for customer management

**Key Methods:**
```php
public function viewCustomer($customerId)
public function editCustomer($customerId)
public function deleteCustomer($customerId)
public function restoreCustomer($customerId)
public function bulkDelete(array $customerIds)
```

### 3. CustomerProfile Component (New)

**Purpose:** Comprehensive customer profile display
**Features:**
- Personal information display
- Account details and statistics
- Package history with pagination
- Financial summary with breakdowns
- Activity timeline

**Key Properties:**
```php
public User $customer;
public $packageStats = [];
public $financialSummary = [];
public $recentPackages = [];
public $showAllPackages = false;
```

**Key Methods:**
```php
public function mount(User $customer)
public function loadPackageStats()
public function loadFinancialSummary()
public function togglePackageView()
public function exportCustomerData()
```

### 4. CustomerEdit Component (New)

**Purpose:** Customer information editing form
**Features:**
- Form validation with real-time feedback
- Profile photo upload
- Address management
- Account settings

**Key Properties:**
```php
public User $customer;
public $firstName;
public $lastName;
public $email;
public $telephoneNumber;
public $taxNumber;
public $streetAddress;
public $cityTown;
public $parish;
public $country;
public $pickupLocation;
```

**Key Methods:**
```php
public function mount(User $customer)
public function save()
public function cancel()
public function validateField($field)
```

### 5. CustomerCreate Component (New)

**Purpose:** Ad-hoc customer creation form
**Features:**
- Complete customer registration form
- Automatic account number generation
- Welcome email sending
- Role assignment

**Key Properties:**
```php
public $firstName;
public $lastName;
public $email;
public $password;
public $telephoneNumber;
public $taxNumber;
public $streetAddress;
public $cityTown;
public $parish;
public $country = 'Jamaica';
public $pickupLocation;
public $sendWelcomeEmail = true;
```

**Key Methods:**
```php
public function create()
public function sendWelcomeEmail(User $user)
public function cancel()
```

**Dependencies:**
- AccountNumberService for unique account number generation

## Data Models

### Enhanced User Model

**New Methods:**
```php
public function getTotalSpentAttribute(): float
public function getPackageCountAttribute(): int
public function getAveragePackageValueAttribute(): float
public function getLastShipmentDateAttribute(): ?Carbon
public function getFinancialSummary(): array
public function getPackageStats(): array
```

**Soft Delete Implementation:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    
    // Override existing scopes to handle soft deletes
    public function scopeCustomers($query)
    {
        return $query->where('role_id', 3);
    }
    
    public function scopeActiveCustomers($query)
    {
        return $query->customers()->whereNull('deleted_at');
    }
    
    public function scopeDeletedCustomers($query)
    {
        return $query->customers()->whereNotNull('deleted_at');
    }
}
```

### Enhanced Profile Model

**New Methods:**
```php
public function getFullAddressAttribute(): string
public function getFormattedPhoneAttribute(): string
public function isComplete(): bool
```

## Error Handling

### Validation Rules

**Customer Creation/Edit Validation:**
```php
protected $rules = [
    'firstName' => 'required|string|max:255',
    'lastName' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'telephoneNumber' => 'required|string|max:20',
    'taxNumber' => 'nullable|string|max:20',
    'streetAddress' => 'required|string|max:500',
    'cityTown' => 'required|string|max:100',
    'parish' => 'required|string|max:50',
    'country' => 'required|string|max:50',
    'pickupLocation' => 'required|string|max:100',
];
```

**Error Handling Strategy:**
- Form validation with real-time feedback
- Database constraint error handling
- Network error handling for email sending
- File upload error handling for profile photos
- Graceful degradation for missing data

### Exception Handling

```php
// Custom exceptions for customer operations
class CustomerNotFoundException extends Exception {}
class CustomerCreationException extends Exception {}
class CustomerUpdateException extends Exception {}
class CustomerDeletionException extends Exception {}
```

## Testing Strategy

### Unit Tests

**Model Tests:**
- User model enhancements (financial calculations, statistics)
- Profile model enhancements (address formatting, completeness)
- Soft delete functionality
- Relationship integrity

**Service Tests:**
- Customer creation service
- Email notification service
- Account number generation
- Financial calculation service

### Feature Tests

**Component Tests:**
- CustomerProfile component rendering and data loading
- CustomerEdit component form handling and validation
- CustomerCreate component creation flow
- AdminCustomersTable component filtering and actions

**Integration Tests:**
- Complete customer creation workflow
- Customer editing and updating workflow
- Soft delete and restore workflow
- Email notification integration
- File upload integration

### Browser Tests

**End-to-End Tests:**
- Admin customer management workflow
- Customer profile navigation
- Form validation and error handling
- Bulk operations
- Search and filtering functionality

## Security Considerations

### Authorization

**Policy Implementation:**
```php
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'superadmin']);
    }
    
    public function view(User $user, User $customer): bool
    {
        return $user->hasRole(['admin', 'superadmin']);
    }
    
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'superadmin']);
    }
    
    public function update(User $user, User $customer): bool
    {
        return $user->hasRole(['admin', 'superadmin']);
    }
    
    public function delete(User $user, User $customer): bool
    {
        return $user->hasRole(['admin', 'superadmin']) && !$customer->isSuperAdmin();
    }
}
```

### Data Protection

- Sensitive data encryption for stored customer information
- Secure file upload handling for profile photos
- Input sanitization and validation
- CSRF protection for all forms
- Rate limiting for customer operations

### Audit Trail

**Customer Activity Logging:**
- Customer creation events
- Profile modification events
- Soft delete/restore events
- Admin access to customer profiles
- Bulk operation events

## Performance Considerations

### Database Optimization

**Indexing Strategy:**
```sql
-- Existing indexes enhancement
CREATE INDEX idx_users_role_deleted ON users(role_id, deleted_at);
CREATE INDEX idx_users_email_deleted ON users(email, deleted_at);
CREATE INDEX idx_profiles_user_id ON profiles(user_id);
CREATE INDEX idx_packages_user_status ON packages(user_id, status);
```

**Query Optimization:**
- Eager loading for customer relationships
- Pagination for large datasets
- Caching for frequently accessed customer statistics
- Database query optimization for financial calculations

### Caching Strategy

```php
// Customer statistics caching
Cache::remember("customer_stats_{$customerId}", 3600, function() use ($customerId) {
    return $this->calculateCustomerStats($customerId);
});

// Financial summary caching
Cache::remember("customer_financial_{$customerId}", 1800, function() use ($customerId) {
    return $this->calculateFinancialSummary($customerId);
});
```

### Frontend Performance

- Lazy loading for customer profile components
- Pagination for package history
- Debounced search functionality
- Optimized image loading for profile photos
- Progressive enhancement for advanced features