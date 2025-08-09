# Package Fee Management and Customer Account Balance System

## Overview

This document describes the enhanced package workflow system that includes fee entry modals for transitioning packages to "ready" status, customer account balance tracking with credit management, and toast notifications instead of session messages.

## Features Implemented

### 1. Fee Entry Modal for Ready Status

When updating a package to "Ready for Pickup" status, users are now prompted to enter additional fees:

- **Customs Duty**: Import duties and taxes
- **Storage Fee**: Warehouse storage charges
- **Delivery Fee**: Final delivery charges

#### Key Components:
- `PackageFeeService`: Handles fee validation, calculation, and updates
- Fee entry modal in `PackageWorkflow` component
- Real-time cost preview with balance impact calculation

#### Usage:
1. Select a package in the workflow
2. Click to advance to "Ready" status
3. Fee modal opens automatically
4. Enter customs duty, storage fee, and delivery fee
5. Optionally apply customer credit balance
6. Review cost summary and impact
7. Confirm to update fees and set status to ready

### 2. Customer Account Balance System

#### Database Schema:
- `users.account_balance`: Main account balance (can be negative)
- `users.credit_balance`: Available credit from overpayments
- `customer_transactions`: Detailed transaction history

#### Transaction Types:
- `payment`: Customer payments (increases account balance)
- `charge`: Service charges (decreases account balance)
- `credit`: Credit adjustments (increases credit balance)
- `debit`: Debit adjustments (decreases balances)
- `distribution`: Package distribution charges
- `adjustment`: Manual balance adjustments

#### Balance Calculations:
- **Account Balance**: Running balance of charges and payments
- **Credit Balance**: Available credit from overpayments
- **Total Available**: Account balance + credit balance

### 3. Credit Balance Application

#### In Fee Updates:
- Option to apply available credit when setting packages to ready
- Credit is applied automatically up to the total cost
- Remaining cost is charged to account balance

#### In Package Distribution:
- Option to apply credit balance during distribution
- Credit reduces the cash amount needed from customer
- Detailed breakdown shows credit applied vs cash collected

### 4. Toast Notifications

Replaced all session flash messages with browser toast notifications:
- Success messages for successful operations
- Error messages for failures and validation issues
- Warning messages for business rule violations
- Real-time feedback without page refresh

## API Reference

### PackageFeeService Methods

#### `updatePackageFeesAndSetReady(Package $package, array $fees, User $updatedBy, bool $applyCreditBalance = false)`
Updates package fees and transitions to ready status.

**Parameters:**
- `$package`: Package to update
- `$fees`: Array with keys: customs_duty, storage_fee, delivery_fee
- `$updatedBy`: User performing the update
- `$applyCreditBalance`: Whether to apply customer credit

**Returns:** Array with success status, message, and updated package data

#### `calculateBalanceImpact(Package $package, array $fees, bool $applyCreditBalance = false)`
Calculates the financial impact of fee updates.

**Returns:** Array with cost breakdown and balance changes

#### `getFeeUpdatePreview(Package $package, array $fees, bool $applyCreditBalance = false)`
Generates a preview of fee updates before applying.

**Returns:** Array with validation status and detailed preview data

### User Model Balance Methods

#### `addCredit($amount, $description, $createdBy = null, ...)`
Adds credit to customer account with transaction logging.

#### `deductBalance($amount, $description, $createdBy = null, ...)`
Deducts amount from account balance with transaction logging.

#### `applyCreditBalance($amount, $description, $createdBy = null, ...)`
Applies credit balance to a charge, returns amount applied.

#### `recordPayment($amount, $description, $createdBy = null, ...)`
Records a customer payment with transaction logging.

#### `getAccountBalanceSummary()`
Returns comprehensive account balance information.

## Usage Examples

### Setting Package to Ready with Fees

```php
$feeService = app(PackageFeeService::class);

$fees = [
    'customs_duty' => 25.50,
    'storage_fee' => 10.00,
    'delivery_fee' => 5.00,
];

$result = $feeService->updatePackageFeesAndSetReady(
    $package,
    $fees,
    Auth::user(),
    true // Apply credit balance
);

if ($result['success']) {
    // Package updated successfully
    $totalCost = $result['total_cost'];
    $creditApplied = $result['credit_applied'];
    $netCharge = $result['net_charge'];
}
```

### Checking Customer Balance

```php
$customer = User::find($customerId);

// Get balance summary
$summary = $customer->getAccountBalanceSummary();

// Check if customer has sufficient balance
if ($customer->hasSufficientBalance($requiredAmount)) {
    // Proceed with transaction
}

// Apply credit to a charge
$creditApplied = $customer->applyCreditBalance(
    $chargeAmount,
    "Credit applied to package distribution",
    Auth::id()
);
```

### Package Distribution with Credit

```php
$distributionService = app(PackageDistributionService::class);

$result = $distributionService->distributePackages(
    $packageIds,
    $amountCollected,
    Auth::user(),
    true // Apply credit balance
);
```

## Frontend Components

### Fee Entry Modal
- Real-time cost calculation
- Credit balance application option
- Validation and error handling
- Cost breakdown preview

### Customer Account Balance Widget
- Account balance display
- Credit balance display
- Total available balance
- Recent transaction history
- Toggle to show/hide transactions

### Distribution Interface
- Credit application checkbox
- Updated cost summary with credit breakdown
- Payment status calculation including credit

## Business Rules

1. **Fee Entry Required**: Packages cannot transition to "Ready" status without fee entry
2. **Credit Application**: Credit is applied before charging account balance
3. **Balance Tracking**: All financial transactions are logged with full audit trail
4. **Distribution Validation**: Only "Ready" packages can be distributed
5. **Payment Status**: Calculated based on total received (cash + credit) vs total cost

## Security Considerations

- All balance changes require authenticated user
- Transaction logging includes user who made the change
- Validation prevents negative fees
- Credit application is optional and explicit
- Balance calculations are atomic with database transactions

## Testing

Comprehensive test coverage includes:
- Fee validation and calculation
- Balance impact calculations
- Credit application logic
- Transaction logging
- Integration with package workflow

Run tests with:
```bash
php artisan test tests/Unit/PackageFeeServiceTest.php
```

## Migration Notes

When deploying this feature:

1. Run migrations to add balance columns and transaction table
2. Existing customers will have zero balances initially
3. Historical package costs are preserved
4. No breaking changes to existing workflow

## Future Enhancements

- Automated credit application based on customer preferences
- Balance alerts and notifications
- Payment integration for online balance top-ups
- Detailed financial reporting and analytics
- Bulk balance adjustments for administrative purposes