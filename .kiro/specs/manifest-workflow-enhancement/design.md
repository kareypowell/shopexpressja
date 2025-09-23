# Design Document

## Overview

This design document outlines the enhancement of the manifest management system to include improved navigation structure, a comprehensive package workflow system, and normalized package status across the entire system. The solution builds upon the existing Laravel/Livewire architecture and follows established patterns from the customer management system.

The enhancement includes:
- Updating sidebar navigation to use expandable menu structure similar to customers
- Implementing package status workflow management (pending → processing → shipped → customs → ready → delivered)
- Creating package distribution functionality with receipt generation
- Implementing comprehensive transaction logging
- Adding automated email notifications with receipt delivery
- Normalizing all package status values using a centralized enum system
- Creating database seeders to migrate existing inconsistent status values
- Updating all components to use consistent status terminology and styling

## Architecture

### Navigation Structure
The manifest navigation will follow the same expandable pattern as the customer management section:
- Main "Manifests" menu item with expand/collapse functionality
- Sub-items: "All Manifests" and "Create Manifest"
- Route highlighting for active manifest pages
- Mobile-responsive design matching existing patterns

### Package Workflow System
The package workflow will be implemented as a state machine with the following components:
- **Package Status Enum**: Defines valid status values and transitions with normalization support
- **Package Status Service**: Handles status transitions, validation, and normalization
- **Package Workflow Component**: Livewire component for status management UI
- **Status History Model**: Tracks all status changes with audit trail
- **Status Normalization Seeder**: Migrates existing status values to normalized format

### Status Normalization System
The status normalization system ensures consistency across the entire application:
- **Centralized Status Management**: Single source of truth for all status values
- **Legacy Status Mapping**: Maps existing inconsistent status values to normalized format
- **Database Migration**: Updates all existing package records to use normalized status
- **Component Updates**: Ensures all UI components display consistent status information

### Distribution System
The package distribution system will include:
- **Distribution Service**: Handles package distribution logic
- **Receipt Generator**: Creates PDF receipts with package and cost details
- **Email Service**: Sends receipts to customers with proper logging
- **Transaction Logger**: Records all distribution activities

## Components and Interfaces

### 1. Navigation Components

#### Sidebar Navigation Update
- **File**: `resources/views/components/sidebar-nav.blade.php`
- **Changes**: Replace single manifest link with expandable menu structure
- **Routes**: 
  - `admin.manifests.index` → All Manifests
  - `admin.manifests.create` → Create Manifest

### 2. Package Status Management

#### Package Status Enum
```php
enum PackageStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case CUSTOMS = 'customs';
    case READY = 'ready';
    case DELIVERED = 'delivered';
    case DELAYED = 'delayed';
    
    // Status normalization methods
    public static function fromLegacyStatus(string $legacyStatus): self
    {
        return match(strtolower(trim($legacyStatus))) {
            'pending', 'new', 'created' => self::PENDING,
            'processing', 'in_process', 'in process' => self::PROCESSING,
            'shipped', 'in_transit', 'in transit' => self::SHIPPED,
            'customs', 'at_customs', 'at customs' => self::CUSTOMS,
            'ready', 'ready_for_pickup', 'ready for pickup' => self::READY,
            'delivered', 'completed', 'done' => self::DELIVERED,
            default => self::PENDING, // Default fallback
        };
    }
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::CUSTOMS => 'At Customs',
            self::READY => 'Ready for Pickup',
            self::DELIVERED => 'Delivered',
            self::DELAYED => 'Delayed',
        };
    }
    
    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'default',
            self::PROCESSING => 'primary',
            self::SHIPPED => 'shs',
            self::CUSTOMS => 'warning',
            self::READY => 'success',
            self::DELIVERED => 'success',
            self::DELAYED => 'danger',
        };
    }
}
```

#### Package Status Service
- **File**: `app/Services/PackageStatusService.php`
- **Methods**:
  - `updateStatus(Package $package, PackageStatus $newStatus, User $user): bool`
  - `getValidTransitions(PackageStatus $currentStatus): array`
  - `canTransitionTo(PackageStatus $from, PackageStatus $to): bool`
  - `logStatusChange(Package $package, PackageStatus $oldStatus, PackageStatus $newStatus, User $user): void`

#### Package Workflow Livewire Component
- **File**: `app/Http/Livewire/Manifests/PackageWorkflow.php`
- **Functionality**:
  - Display packages with current status using normalized values
  - Bulk status updates with validation
  - Status transition validation
  - Real-time status updates

#### Package Status Normalization Seeder
- **File**: `database/seeders/PackageStatusNormalizationSeeder.php`
- **Methods**:
  - `run()`: Main seeder execution method
  - `identifyLegacyStatuses()`: Finds all unique status values in packages table
  - `mapLegacyStatuses()`: Maps legacy statuses to normalized values
  - `updatePackageStatuses()`: Updates database records with normalized statuses
  - `generateReport()`: Creates summary report of changes made
  - `logUnmappableStatuses()`: Logs any statuses that couldn't be mapped

### 3. Distribution System

#### Package Distribution Service
- **File**: `app/Services/PackageDistributionService.php`
- **Methods**:
  - `distributePackages(array $packageIds, float $amountCollected, User $user): DistributionResult`
  - `generateReceipt(array $packages, float $amountCollected, User $customer): Receipt`
  - `sendReceiptEmail(Receipt $receipt, User $customer): EmailResult`
  - `logDistribution(array $packages, Receipt $receipt, float $amountCollected, User $user): void`
  - `calculatePackageTotals(array $packages): float`
  - `validatePaymentAmount(float $totalCost, float $amountCollected): bool`

#### Receipt Generator
- **File**: `app/Services/ReceiptGeneratorService.php`
- **Methods**:
  - `generatePDF(array $packages, User $customer): string`
  - `calculateTotals(array $packages): array`
  - `formatReceiptData(array $packages, User $customer): array`

#### Distribution Email Service
- **File**: `app/Services/DistributionEmailService.php`
- **Methods**:
  - `sendReceiptEmail(Receipt $receipt, User $customer): array`
  - `retryFailedReceipt(string $receiptId): array`
  - `checkReceiptDeliveryStatus(string $deliveryId): array`

### 4. Data Models

#### Package Model Updates
- **File**: `app/Models/Package.php`
- **Changes**:
  - Add status casting to PackageStatus enum
  - Update getStatusBadgeClassAttribute to use enum method
  - Add status validation in model mutators
  - Update relationships to handle status history

#### Package Status History Model
- **File**: `app/Models/PackageStatusHistory.php`
- **Fields**:
  - `package_id` (foreign key)
  - `old_status` (enum)
  - `new_status` (enum)
  - `changed_by` (user_id foreign key)
  - `changed_at` (timestamp)
  - `notes` (text, nullable)

#### Package Distribution Model
- **File**: `app/Models/PackageDistribution.php`
- **Fields**:
  - `id` (primary key)
  - `receipt_number` (unique string)
  - `customer_id` (foreign key)
  - `distributed_by` (user_id foreign key)
  - `distributed_at` (timestamp)
  - `total_amount` (decimal) - calculated total of all package costs
  - `amount_collected` (decimal) - actual amount collected from customer
  - `payment_status` (enum: 'paid', 'partial', 'unpaid')
  - `receipt_path` (string)
  - `email_sent` (boolean)
  - `email_sent_at` (timestamp, nullable)

#### Package Distribution Items Model
- **File**: `app/Models/PackageDistributionItem.php`
- **Fields**:
  - `distribution_id` (foreign key)
  - `package_id` (foreign key)
  - `freight_price` (decimal)
  - `clearance_fee` (decimal)
  - `storage_fee` (decimal)
  - `delivery_fee` (decimal)
  - `total_cost` (decimal)

### 5. Email Templates

#### Receipt Email Template
- **File**: `app/Mail/PackageReceiptEmail.php`
- **Template**: `resources/views/emails/packages/receipt.blade.php`
- **Features**:
  - Professional formatting with company branding
  - Package details table
  - Cost breakdown
  - PDF receipt attachment
  - Customer information

## Error Handling

### Status Transition Validation
- Validate status transitions using enum-based rules
- Prevent invalid status changes (e.g., pending → delivered)
- Return descriptive error messages for invalid transitions
- Log all validation failures for audit purposes

### Distribution Error Handling
- Validate package eligibility for distribution (must be "ready" status)
- Handle PDF generation failures gracefully
- Implement email delivery retry mechanism
- Log all distribution errors with context

### Email Delivery Error Handling
- Queue email delivery for reliability
- Implement retry logic for failed deliveries
- Log email delivery status and failures
- Provide manual retry functionality for failed emails

## Testing Strategy

### Unit Tests
- **Package Status Enum Tests**: Validate status normalization, transitions, and legacy mapping
- **Package Status Service Tests**: Validate status transitions and business logic
- **Status Normalization Seeder Tests**: Test legacy status mapping and database updates
- **Receipt Generator Tests**: Test PDF generation and data formatting
- **Email Service Tests**: Mock email delivery and test error handling
- **Model Tests**: Validate relationships, data integrity, and status casting

### Feature Tests
- **Navigation Tests**: Verify expandable menu functionality and route highlighting
- **Package Workflow Tests**: Test bulk status updates and validation with normalized statuses
- **Status Normalization Tests**: Test complete status migration workflow
- **Distribution Process Tests**: End-to-end distribution workflow testing
- **Email Integration Tests**: Test receipt email delivery with attachments

### Browser Tests
- **Navigation UI Tests**: Test expandable menu behavior across devices
- **Package Management Tests**: Test package selection and status updates with normalized display
- **Status Consistency Tests**: Verify consistent status display across all components
- **Distribution UI Tests**: Test distribution interface and confirmation flows
- **Receipt Generation Tests**: Verify PDF generation and download functionality

## Database Migrations

### Package Status Normalization Migration
```sql
-- Migration to ensure status column supports all normalized values
ALTER TABLE packages MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending';

-- Add index for better status filtering performance
CREATE INDEX idx_packages_status ON packages(status);

-- Add constraint to ensure only valid status values (optional, handled by enum)
-- ALTER TABLE packages ADD CONSTRAINT chk_package_status 
-- CHECK (status IN ('pending', 'processing', 'shipped', 'customs', 'ready', 'delivered'));
```

### Package Status History Table
```sql
CREATE TABLE package_status_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(20) NOT NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    changed_at TIMESTAMP NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_package_status_history (package_id, changed_at)
);
```

### Package Distributions Table
```sql
CREATE TABLE package_distributions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    distributed_by BIGINT UNSIGNED NOT NULL,
    distributed_at TIMESTAMP NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    amount_collected DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid', 'partial', 'unpaid') NOT NULL DEFAULT 'unpaid',
    receipt_path VARCHAR(255) NOT NULL,
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (distributed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_customer_distributions (customer_id, distributed_at),
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_payment_status (payment_status)
);
```

### Package Distribution Items Table
```sql
CREATE TABLE package_distribution_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    distribution_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NOT NULL,
    freight_price DECIMAL(8,2) NOT NULL DEFAULT 0,
    clearance_fee DECIMAL(8,2) NOT NULL DEFAULT 0,
    storage_fee DECIMAL(8,2) NOT NULL DEFAULT 0,
    delivery_fee DECIMAL(8,2) NOT NULL DEFAULT 0,
    total_cost DECIMAL(8,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (distribution_id) REFERENCES package_distributions(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_distribution_package (distribution_id, package_id)
);
```

## Security Considerations

### Authorization
- Implement role-based access control for package status updates
- Restrict distribution functionality to authorized users only
- Validate user permissions before allowing status changes
- Log all user actions for audit trail

### Data Validation
- Validate all input data before processing
- Sanitize user inputs to prevent injection attacks
- Implement CSRF protection on all forms
- Validate file uploads and PDF generation inputs

### Email Security
- Validate email addresses before sending
- Implement rate limiting for email sending
- Use secure email templates to prevent XSS
- Log email delivery attempts for monitoring

## Performance Considerations

### Database Optimization
- Add appropriate indexes for status history queries
- Optimize package queries with eager loading
- Implement pagination for large package lists
- Use database transactions for distribution operations

### Email Queue Management
- Use queue workers for email processing
- Implement email batching for bulk operations
- Monitor queue performance and failures
- Set appropriate retry limits and delays

### PDF Generation
- Optimize PDF generation for large receipts
- Cache receipt templates for performance
- Implement file cleanup for old receipts
- Monitor PDF generation memory usage