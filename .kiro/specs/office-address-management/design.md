# Design Document

## Overview

This design implements comprehensive management interfaces for Offices and Shipping Addresses in the ShipSharkLtd system. The solution follows the existing Laravel + Livewire architecture pattern used throughout the application, providing consistent user experience and maintainable code structure.

The design leverages the existing Office and Address models while creating new controllers, Livewire components, and views to provide full CRUD functionality accessible through the sidebar navigation.

## Architecture

### MVC + Livewire Pattern
Following the established application pattern:
- **Controllers**: Handle route definitions and authorization
- **Livewire Components**: Manage interactive UI logic, search, filtering, and CRUD operations
- **Models**: Existing Office and Address models with potential enhancements
- **Views**: Blade templates with Tailwind CSS styling consistent with existing UI

### Route Structure
```
/admin/offices
├── GET /admin/offices (index)
├── GET /admin/offices/create (create form)
├── GET /admin/offices/{office} (show)
├── GET /admin/offices/{office}/edit (edit form)
└── POST/PUT/DELETE handled by Livewire components

/admin/addresses  
├── GET /admin/addresses (index)
├── GET /admin/addresses/create (create form)
├── GET /admin/addresses/{address} (show)
├── GET /admin/addresses/{address}/edit (edit form)
└── POST/PUT/DELETE handled by Livewire components
```

## Components and Interfaces

### Controllers

#### OfficeController
- Extends base Controller with authentication middleware
- Implements resource controller pattern
- Methods: index(), create(), show(), edit()
- Authorization using policies for each action

#### AddressController  
- Extends base Controller with authentication middleware
- Implements resource controller pattern
- Methods: index(), create(), show(), edit()
- Authorization using policies for each action

### Livewire Components

#### Admin\OfficeManagement
- **Purpose**: Main office listing with search, filtering, and pagination
- **Features**:
  - Search by office name or address
  - Pagination with 15 items per page
  - Delete confirmation modals
  - Relationship validation before deletion
  - Success/error flash messages
- **Properties**: `$searchTerm`, `$showDeleteModal`, `$selectedOffice`
- **Methods**: `updatedSearchTerm()`, `confirmDelete()`, `deleteOffice()`, `cancelDelete()`

#### Admin\OfficeForm
- **Purpose**: Create and edit office forms
- **Features**:
  - Real-time validation
  - Form submission handling
  - Redirect after successful operations
- **Properties**: `$office`, `$name`, `$address`, `$isEditing`
- **Methods**: `save()`, `mount()`, validation rules

#### Admin\AddressManagement
- **Purpose**: Main address listing with search, filtering, and pagination
- **Features**:
  - Search across all address fields
  - Primary address indicators
  - Pagination with 15 items per page
  - Delete confirmation modals
- **Properties**: `$searchTerm`, `$showDeleteModal`, `$selectedAddress`
- **Methods**: `updatedSearchTerm()`, `confirmDelete()`, `deleteAddress()`, `togglePrimary()`

#### Admin\AddressForm
- **Purpose**: Create and edit address forms
- **Features**:
  - Comprehensive address validation
  - Primary address management
  - Country/state selection
- **Properties**: `$address`, `$street_address`, `$city`, `$state`, `$zip_code`, `$country`, `$is_primary`, `$isEditing`
- **Methods**: `save()`, `mount()`, validation rules

### Policies

#### OfficePolicy
- **Methods**: `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- **Logic**: Admin and superadmin access, with delete restrictions for offices with dependencies

#### AddressPolicy
- **Methods**: `viewAny()`, `view()`, `create()`, `update()`, `delete()`
- **Logic**: Admin and superadmin access for all operations

## Data Models

### Office Model Enhancements
```php
// Add to existing Office model
protected $fillable = [
    'name',
    'address',
];

// Add relationship counts
public function getManifestCountAttribute()
{
    return $this->manifests()->count();
}

public function getPackageCountAttribute()
{
    return $this->packages()->count();
}

public function getProfileCountAttribute()
{
    return $this->profiles()->count();
}

// Add scope for search
public function scopeSearch($query, $term)
{
    return $query->where('name', 'like', "%{$term}%")
                 ->orWhere('address', 'like', "%{$term}%");
}
```

### Address Model Enhancements
```php
// Add to existing Address model
protected $fillable = [
    'street_address',
    'city', 
    'state',
    'zip_code',
    'country',
    'is_primary',
];

protected $casts = [
    'is_primary' => 'boolean',
];

// Add scope for search
public function scopeSearch($query, $term)
{
    return $query->where('street_address', 'like', "%{$term}%")
                 ->orWhere('city', 'like', "%{$term}%")
                 ->orWhere('state', 'like', "%{$term}%")
                 ->orWhere('zip_code', 'like', "%{$term}%")
                 ->orWhere('country', 'like', "%{$term}%");
}

// Ensure only one primary address
public static function boot()
{
    parent::boot();
    
    static::saving(function ($address) {
        if ($address->is_primary) {
            static::where('is_primary', true)
                  ->where('id', '!=', $address->id)
                  ->update(['is_primary' => false]);
        }
    });
}
```

## Error Handling

### Validation Rules
- **Office**: name (required, max:255), address (required, max:500)
- **Address**: street_address (required, max:255), city (required, max:100), state (required, max:100), zip_code (required, max:20), country (required, max:100), is_primary (boolean)

### Error Scenarios
1. **Validation Errors**: Display field-specific error messages using Laravel validation
2. **Deletion Constraints**: Prevent office deletion if relationships exist, show detailed error message
3. **Database Errors**: Catch exceptions and display user-friendly messages
4. **Authorization Errors**: Redirect with appropriate error messages for unauthorized access

### Success Messages
- Create: "Office/Address created successfully"
- Update: "Office/Address updated successfully"  
- Delete: "Office/Address deleted successfully"

## Testing Strategy

### Unit Tests
- **Model Tests**: Validation, relationships, scopes, and business logic
- **Policy Tests**: Authorization logic for different user roles
- **Service Tests**: Any business logic services

### Feature Tests
- **CRUD Operations**: Complete create, read, update, delete workflows
- **Search Functionality**: Search and filtering behavior
- **Authorization**: Access control for different user roles
- **Validation**: Form validation and error handling
- **Relationship Constraints**: Office deletion with dependencies

### Browser Tests (Optional)
- **UI Interactions**: Modal behavior, form submissions
- **Search and Filter**: Real-time search functionality
- **Navigation**: Sidebar navigation highlighting

## UI/UX Design

### Layout Structure
- Consistent with existing admin pages
- Sidebar navigation integration
- Breadcrumb navigation
- Responsive design with Tailwind CSS

### Component Styling
- Table layouts matching existing patterns
- Search bars with consistent styling
- Modal dialogs for confirmations
- Form layouts with proper validation display
- Success/error flash message styling

### Navigation Integration
- Update sidebar-nav.blade.php with proper route links
- Active state highlighting for office/address sections
- Consistent iconography (building-office, map-pin)

### Accessibility
- Proper ARIA labels
- Keyboard navigation support
- Screen reader compatibility
- Focus management in modals