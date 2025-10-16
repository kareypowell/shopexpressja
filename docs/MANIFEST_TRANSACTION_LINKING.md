# Manifest-Transaction Linking Feature

## Overview

This feature enables linking customer transactions to specific manifests, providing better tracking and filtering capabilities for financial operations in the ShipSharkLtd system.

## Key Features

### 1. Transaction-Manifest Relationships

- **Automatic Linking**: Transactions created during package distribution are automatically linked to the manifest(s) containing the packages
- **Manual Linking**: Transactions can be manually linked to manifests when created through the transaction management interface
- **Flexible References**: Uses the existing `reference_type` and `reference_id` fields in the `customer_transactions` table

### 2. Enhanced Transaction Model

#### New Relationships
```php
// Get the manifest if this transaction is linked to one
$transaction->manifest;

// Get the package if this transaction is linked to one
$transaction->package;

// Get the package distribution if this transaction is linked to one
$transaction->packageDistribution;
```

#### New Methods
```php
// Link transactions to different entities
$transaction->linkToManifest($manifest);
$transaction->linkToPackage($package);
$transaction->linkToPackageDistribution($distribution);

// Check what the transaction is linked to
$transaction->isLinkedToManifest();
$transaction->isLinkedToPackage();
$transaction->isLinkedToPackageDistribution();
```

#### New Scopes
```php
// Filter transactions by manifest
CustomerTransaction::forManifest($manifestId)->get();

// Filter transactions by package
CustomerTransaction::forPackage($packageId)->get();

// Get all transactions linked to manifests
CustomerTransaction::linkedToManifests()->get();
```

### 3. Enhanced Manifest Model

#### New Relationships and Methods
```php
// Get all transactions linked to this manifest
$manifest->transactions;

// Get financial summary for the manifest
$manifest->getFinancialSummary();
// Returns: total_owed, total_collected, total_write_off, outstanding_balance, collection_rate

// Get specific amounts
$manifest->getTotalCollectedAmount();
$manifest->getTotalChargedAmount();
$manifest->getTotalWriteOffAmount();
```

### 4. Enhanced User Model

#### New Convenience Methods
```php
// Record payment linked to a manifest
$user->recordPaymentForManifest($amount, $description, $manifest, $createdBy, $metadata);

// Record charge linked to a manifest
$user->recordChargeForManifest($amount, $description, $manifest, $createdBy, $metadata);
```

### 5. Transaction Management Interface

A new Livewire component (`TransactionManagement`) provides:

- **Advanced Filtering**: Filter transactions by customer, manifest, type, date range, and search terms
- **Manifest Linking**: Create new transactions with optional manifest linking
- **Comprehensive View**: See all transaction details including linked manifests
- **Real-time Updates**: Live filtering and pagination

#### Usage
```php
// Include in your routes or admin panel
Route::get('/admin/transactions', TransactionManagement::class)->name('admin.transactions');
```

### 6. Automatic Linking During Distribution

The `PackageDistributionService` has been enhanced to automatically link transactions to manifests when:

- Packages are distributed individually or as consolidated packages
- All packages in the distribution belong to the same manifest
- Transactions are created for charges, payments, credits, or write-offs

## Implementation Details

### Database Structure

The feature uses existing database fields:
- `reference_type`: Stores the model class name (e.g., 'App\\Models\\Manifest')
- `reference_id`: Stores the ID of the referenced model
- `metadata`: Stores additional context including manifest IDs for multi-manifest distributions

### Transaction Types Supported

All transaction types can be linked to manifests:
- `payment`: Customer payments
- `charge`: Service charges
- `credit`: Account credits
- `debit`: Account debits
- `write_off`: Debt forgiveness/discounts
- `adjustment`: Balance adjustments

### Filtering Capabilities

The transaction management interface supports filtering by:
- **Customer**: Select specific customer
- **Manifest**: Select specific manifest
- **Transaction Type**: Filter by transaction type
- **Date Range**: From/to date filtering
- **Search**: Text search across descriptions, amounts, customer names, and manifest names

## Usage Examples

### Creating Transactions with Manifest Links

```php
// Manual transaction creation with manifest link
$customer = User::find(1);
$manifest = Manifest::find(5);

$transaction = $customer->recordPaymentForManifest(
    100.00,
    'Payment for manifest services',
    $manifest,
    Auth::id()
);

// Or create and link separately
$transaction = $customer->recordPayment(100.00, 'Payment for services', Auth::id());
$transaction->linkToManifest($manifest);
```

### Querying Transactions by Manifest

```php
// Get all transactions for a specific manifest
$manifestTransactions = CustomerTransaction::forManifest($manifestId)->get();

// Get financial summary for a manifest
$manifest = Manifest::find(1);
$summary = $manifest->getFinancialSummary();
echo "Total Collected: $" . $summary['total_collected'];
echo "Outstanding: $" . $summary['outstanding_balance'];
```

### Using the Transaction Management Component

```blade
<!-- In your Blade template -->
<livewire:transaction-management />
```

## Benefits

1. **Better Financial Tracking**: Link transactions to specific manifests for detailed financial analysis
2. **Improved Reporting**: Generate manifest-specific financial reports
3. **Enhanced Filtering**: Quickly find transactions related to specific manifests
4. **Automatic Linking**: Reduces manual work by automatically linking transactions during distribution
5. **Audit Trail**: Better tracking of which transactions relate to which shipments

## Migration Notes

- **Backward Compatible**: Existing transactions without manifest links continue to work normally
- **Gradual Adoption**: New transactions can be linked to manifests while old ones remain unlinked
- **No Database Changes**: Uses existing `reference_type` and `reference_id` fields

## Future Enhancements

- **Bulk Linking**: Tools to retroactively link existing transactions to manifests
- **Advanced Reporting**: Manifest-specific financial reports and analytics
- **API Endpoints**: REST API endpoints for transaction-manifest operations
- **Export Features**: Export manifest financial data with linked transactions