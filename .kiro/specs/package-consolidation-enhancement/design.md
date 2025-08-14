# Design Document

## Overview

The Package Consolidation Enhancement introduces a flexible system for grouping multiple packages under a single consolidated entry while preserving individual package tracking data. The design leverages the existing package management infrastructure and extends it with consolidation capabilities that integrate seamlessly with current workflows including distribution, notifications, and manifest management.

## Architecture

### Core Components

1. **ConsolidatedPackage Model** - New model to represent consolidated package groups
2. **PackageConsolidationService** - Service layer for consolidation operations
3. **Enhanced PackageDistributionService** - Extended to handle consolidated packages
4. **Enhanced PackageNotificationService** - Updated to send consolidated notifications
5. **ConsolidationToggle Component** - UI component for enabling/disabling consolidation mode
6. **Enhanced Package Livewire Components** - Updated to support consolidation features

### Database Design

#### New Tables

**consolidated_packages**
```sql
- id (primary key)
- consolidated_tracking_number (unique, generated)
- customer_id (foreign key to users)
- created_by (foreign key to users - admin who created consolidation)
- total_weight (calculated from individual packages)
- total_quantity (calculated from individual packages)
- total_freight_price (calculated)
- total_customs_duty (calculated)
- total_storage_fee (calculated)
- total_delivery_fee (calculated)
- status (mirrors individual package status)
- consolidated_at (timestamp)
- unconsolidated_at (nullable timestamp)
- is_active (boolean - true for active consolidations)
- notes (text, optional)
- created_at
- updated_at
```

#### Modified Tables

**packages**
```sql
- consolidated_package_id (nullable foreign key to consolidated_packages)
- is_consolidated (boolean, default false)
- consolidated_at (nullable timestamp)
```

### Relationships

- ConsolidatedPackage `hasMany` Package
- Package `belongsTo` ConsolidatedPackage (nullable)
- ConsolidatedPackage `belongsTo` User (customer)
- ConsolidatedPackage `belongsTo` User (created_by)

## Components and Interfaces

### Models

#### ConsolidatedPackage Model
```php
class ConsolidatedPackage extends Model
{
    // Relationships
    public function packages()
    public function customer()
    public function createdBy()
    
    // Calculated Properties
    public function getTotalWeightAttribute()
    public function getTotalQuantityAttribute()
    public function getTotalCostAttribute()
    public function getFormattedTrackingNumbersAttribute()
    
    // Business Logic
    public function calculateTotals()
    public function canBeUnconsolidated()
    public function generateConsolidatedTrackingNumber()
    
    // Status Management
    public function updateStatusFromPackages()
    public function syncPackageStatuses($newStatus)
}
```

#### Enhanced Package Model
```php
class Package extends Model
{
    // New Methods
    public function isConsolidated()
    public function getConsolidatedGroup()
    public function canBeConsolidated()
    
    // Enhanced Scopes
    public function scopeConsolidated($query)
    public function scopeIndividual($query)
    public function scopeAvailableForConsolidation($query)
}
```

### Services

#### PackageConsolidationService
```php
class PackageConsolidationService
{
    public function consolidatePackages(array $packageIds, User $admin, array $options = [])
    public function unconsolidatePackages(ConsolidatedPackage $consolidatedPackage, User $admin)
    public function validateConsolidation(array $packageIds)
    public function calculateConsolidatedTotals(array $packages)
    public function generateConsolidatedTrackingNumber()
    public function updateConsolidatedStatus(ConsolidatedPackage $consolidatedPackage, PackageStatus $newStatus, User $user)
    public function getConsolidationHistory(ConsolidatedPackage $consolidatedPackage)
}
```

#### Enhanced PackageDistributionService
```php
class PackageDistributionService
{
    // New Methods
    public function distributeConsolidatedPackages(ConsolidatedPackage $consolidatedPackage, float $amountCollected, User $user, array $balanceOptions = [], array $options = [])
    public function generateConsolidatedReceipt(ConsolidatedPackage $consolidatedPackage, PackageDistribution $distribution)
    
    // Enhanced Methods
    public function distributePackages() // Updated to handle both individual and consolidated packages
    public function calculatePackageTotals() // Updated to work with consolidated packages
}
```

#### Enhanced PackageNotificationService
```php
class PackageNotificationService
{
    // New Methods
    public function sendConsolidatedStatusNotification(ConsolidatedPackage $consolidatedPackage, PackageStatus $newStatus)
    public function sendConsolidationNotification(ConsolidatedPackage $consolidatedPackage)
    public function sendUnconsolidationNotification(array $packages, User $customer)
    
    // Enhanced Methods
    public function sendStatusNotification() // Updated to check for consolidation
}
```

### Livewire Components

#### ConsolidationToggle Component
```php
class ConsolidationToggle extends Component
{
    public $consolidationMode = false;
    
    public function toggleConsolidationMode()
    public function render()
}
```

#### Enhanced Package Component
```php
class Package extends Component
{
    // New Properties
    public $consolidationMode = false;
    public $selectedPackagesForConsolidation = [];
    public $showConsolidatedView = false;
    
    // New Methods
    public function togglePackageSelection($packageId)
    public function consolidateSelectedPackages()
    public function unconsolidatePackage($consolidatedPackageId)
    public function toggleConsolidatedView()
    
    // Enhanced Methods
    public function render() // Updated to handle consolidated packages
}
```

#### Enhanced PackageDistribution Component
```php
class PackageDistribution extends Component
{
    // Enhanced Methods
    public function distributePackages() // Updated to handle consolidated packages
    public function calculateTotals() // Updated for consolidated packages
}
```

## Data Models

### ConsolidatedPackage Entity
```php
[
    'id' => 'integer',
    'consolidated_tracking_number' => 'string', // Format: CONS-YYYYMMDD-XXXX
    'customer_id' => 'integer',
    'created_by' => 'integer',
    'total_weight' => 'decimal(15,2)',
    'total_quantity' => 'integer',
    'total_freight_price' => 'decimal(15,2)',
    'total_customs_duty' => 'decimal(15,2)',
    'total_storage_fee' => 'decimal(15,2)',
    'total_delivery_fee' => 'decimal(15,2)',
    'status' => 'string', // Uses PackageStatus enum
    'consolidated_at' => 'timestamp',
    'unconsolidated_at' => 'timestamp|null',
    'is_active' => 'boolean',
    'notes' => 'text|null',
    'created_at' => 'timestamp',
    'updated_at' => 'timestamp'
]
```

### Enhanced Package Entity
```php
// Existing fields plus:
[
    'consolidated_package_id' => 'integer|null',
    'is_consolidated' => 'boolean',
    'consolidated_at' => 'timestamp|null'
]
```

### Consolidation History Entity
```php
[
    'id' => 'integer',
    'consolidated_package_id' => 'integer',
    'action' => 'string', // 'consolidated', 'unconsolidated', 'status_changed'
    'performed_by' => 'integer',
    'details' => 'json', // Additional context data
    'performed_at' => 'timestamp'
]
```

## Error Handling

### Validation Rules

1. **Consolidation Validation**
   - All packages must belong to the same customer
   - Packages must not already be consolidated
   - Packages must be in compatible statuses
   - Minimum 2 packages required for consolidation

2. **Unconsolidation Validation**
   - Consolidated package must be active
   - User must have appropriate permissions
   - Cannot unconsolidate if distribution is in progress

3. **Status Update Validation**
   - Status transitions must be valid for all packages in consolidation
   - Cannot change status if packages have conflicting states

### Error Recovery

1. **Transaction Rollback** - All consolidation operations wrapped in database transactions
2. **Audit Logging** - All actions logged for troubleshooting
3. **Data Integrity Checks** - Validation before and after operations
4. **Graceful Degradation** - System continues to work if consolidation features fail

## Testing Strategy

### Unit Tests

1. **Model Tests**
   - ConsolidatedPackage model relationships and calculations
   - Package model consolidation methods
   - Data validation and constraints

2. **Service Tests**
   - PackageConsolidationService operations
   - Enhanced PackageDistributionService with consolidated packages
   - Enhanced PackageNotificationService with consolidated notifications

3. **Component Tests**
   - ConsolidationToggle component behavior
   - Enhanced Package component consolidation features
   - Enhanced PackageDistribution component

### Integration Tests

1. **Consolidation Workflow**
   - End-to-end consolidation process
   - Unconsolidation process
   - Status synchronization

2. **Distribution Integration**
   - Consolidated package distribution
   - Receipt generation for consolidated packages
   - Email notifications for consolidated packages

3. **Notification Integration**
   - Consolidated status notifications
   - Consolidation/unconsolidation notifications
   - Email template rendering

### Feature Tests

1. **UI Interaction Tests**
   - Consolidation mode toggle
   - Package selection and consolidation
   - Consolidated package management

2. **Business Logic Tests**
   - Calculation accuracy
   - Status transition validation
   - Permission enforcement

## Performance Considerations

### Database Optimization

1. **Indexing Strategy**
   - Index on `consolidated_package_id` in packages table
   - Index on `customer_id` in consolidated_packages table
   - Index on `is_active` for active consolidations

2. **Query Optimization**
   - Eager loading of consolidated packages with their individual packages
   - Efficient calculation of totals using database aggregations
   - Pagination for large consolidation lists

### Caching Strategy

1. **Consolidation Totals** - Cache calculated totals to avoid repeated calculations
2. **Customer Consolidations** - Cache active consolidations per customer
3. **Status Counts** - Cache consolidation status counts for dashboard metrics

### Memory Management

1. **Batch Processing** - Process large consolidations in batches
2. **Lazy Loading** - Load package details only when needed
3. **Resource Cleanup** - Proper cleanup of temporary consolidation data

## Security Considerations

### Access Control

1. **Permission-Based Access** - Only authorized users can create/manage consolidations
2. **Customer Data Isolation** - Users can only consolidate their own packages
3. **Audit Trail** - All consolidation actions logged with user attribution

### Data Protection

1. **Input Validation** - Strict validation of all consolidation inputs
2. **SQL Injection Prevention** - Parameterized queries for all database operations
3. **Data Integrity** - Foreign key constraints and validation rules

### Privacy Compliance

1. **Data Retention** - Consolidation history follows data retention policies
2. **Customer Consent** - Consolidation actions logged for transparency
3. **Data Export** - Consolidated data included in customer data exports