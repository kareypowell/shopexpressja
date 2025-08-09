# Comprehensive Test Scenarios

## Overview
This document outlines all the comprehensive test scenarios created for the package distribution and customer balance system. The test data covers every possible scenario to ensure robust testing of all features.

## Customer Balance Scenarios

### 1. New Customer (Zero Balances)
- **Customer**: John NewCustomer (john.new@test.com)
- **Initial Balance**: Account: $0.00, Credit: $0.00
- **Packages**: 1 small package ($25.00 total)
- **Test Cases**: 
  - Exact payment ($25)
  - Overpayment ($30) → Should create $5 credit
  - Underpayment ($20) → Should show partial payment

### 2. Positive Balance Customer
- **Customer**: Sarah PositiveBalance (sarah.positive@test.com)
- **Initial Balance**: Account: $500.00, Credit: $0.00
- **Packages**: 1 medium package ($60.00 total)
- **Test Cases**:
  - No payment (charge to account) → Account becomes $440
  - Partial payment + account charge
  - Full payment → Account becomes $500 (no change)

### 3. Credit Only Customer
- **Customer**: Mike CreditOnly (mike.credit@test.com)
- **Initial Balance**: Account: $0.00, Credit: $125.75
- **Packages**: 2 packages ($72.00 total)
- **Test Cases**:
  - Apply credit only → Credit becomes $53.75
  - Apply credit + cash payment
  - Don't apply credit, pay cash → Credit unchanged

### 4. Negative Balance Customer (Owes Money)
- **Customer**: David OwesMoneyCustomer (david.owes@test.com)
- **Initial Balance**: Account: -$150.50, Credit: $0.00
- **Packages**: 1 high-value package ($118.00 total)
- **Test Cases**:
  - Large payment to cover debt + package
  - Partial payment → Increases debt
  - Overpayment → Pays off debt + creates credit

### 5. Mixed Balance Customer
- **Customer**: Lisa MixedBalance (lisa.mixed@test.com)
- **Initial Balance**: Account: $300.00, Credit: $75.25
- **Packages**: 2 packages (1 ready: $50.00, 1 in customs)
- **Test Cases**:
  - Apply credit + account charge
  - Don't apply credit, pay cash
  - Overpayment with credit application

### 6. High-Volume Customer
- **Customer**: Robert HighVolumeCustomer (robert.highvolume@test.com)
- **Initial Balance**: Account: $1000.00, Credit: $200.00
- **Packages**: 5 packages in various statuses (2 ready for pickup)
- **Test Cases**:
  - Bulk distribution of multiple packages
  - Large overpayment scenarios
  - Mixed payment methods

### 7. Transaction History Customer
- **Customer**: Emma TransactionHistory (emma.history@test.com)
- **Initial Balance**: Account: $250.00, Credit: $50.00
- **Packages**: 1 sea freight package + historical data
- **Test Cases**:
  - Verify transaction history display
  - Sea freight package distribution
  - Complex balance calculations

### 8. VIP Customer
- **Customer**: Simba Powell (existing user)
- **Initial Balance**: Account: $2000.00, Credit: $500.00
- **Packages**: 2 luxury packages ($358.00 total)
- **Test Cases**:
  - High-value package distributions
  - Large credit applications
  - Premium customer scenarios

### 9. Workflow Test Customer
- **Customer**: Workflow TestCustomer (workflow.test@test.com)
- **Initial Balance**: Account: $100.00, Credit: $25.00
- **Packages**: 6 packages in all workflow stages
- **Test Cases**:
  - Fee entry modal testing
  - Status transition testing
  - Workflow progression validation

## Package Scenarios

### Package Types Created
1. **Small Packages**: $15-30 total cost
2. **Medium Packages**: $40-70 total cost  
3. **High-Value Packages**: $100+ total cost
4. **Sea Freight Packages**: With dimensions and container info
5. **Multiple Packages**: Various combinations per customer
6. **Different Statuses**: Pending, Processing, Shipped, Customs, Ready, Delayed, Delivered

### Package Status Distribution
- **Ready for Pickup**: 12 packages (available for distribution)
- **In Customs**: 3 packages (awaiting fee entry)
- **Shipped/Processing**: 4 packages (in transit)
- **Delivered**: 1 package (historical)
- **Other Statuses**: 2 packages (pending, delayed)

## Test Scenarios by Feature

### 1. Balance Update Testing
```bash
# Test exact payment
php artisan demo:package-distribution "John NewCustomer" --amount=25

# Test overpayment
php artisan demo:package-distribution "John NewCustomer" --amount=30

# Test credit application
php artisan demo:package-distribution "Mike CreditOnly" --amount=50 --apply-credit

# Test negative balance recovery
php artisan demo:package-distribution "David OwesMoneyCustomer" --amount=300
```

### 2. Dashboard Display Testing
- Login as any customer to see balance display
- Verify all three balance types show correctly
- Test transaction history toggle
- Verify responsive design

### 3. Fee Entry Modal Testing
- Navigate to package workflow
- Select packages in customs status
- Test fee entry with various amounts
- Test credit application option

### 4. Distribution Process Testing
- Test single package distribution
- Test multiple package distribution
- Test mixed payment scenarios
- Test receipt generation

### 5. Transaction History Testing
- Verify all transactions are recorded
- Check transaction types and amounts
- Validate balance calculations
- Test audit trail completeness

## Automated Test Coverage

### Unit Tests
- `PackageDistributionBalanceTest`: 5 tests for balance scenarios
- `PackageDistributionOverpaymentTest`: 4 tests for overpayment handling
- `CustomerAccountBalanceTest`: 3 tests for dashboard component

### Test Commands
```bash
# Run all distribution tests
php artisan test tests/Unit/PackageDistribution*

# Run specific test suites
php artisan test tests/Unit/PackageDistributionBalanceTest.php
php artisan test tests/Unit/PackageDistributionOverpaymentTest.php
php artisan test tests/Unit/CustomerAccountBalanceTest.php
```

## Data Reset and Management

### Reset Test Data
```bash
# Reset with confirmation
php artisan reset:test-data

# Reset without confirmation (for automation)
php artisan reset:test-data --confirm
```

### Check Data Status
```bash
# View customer balances
php artisan tinker --execute="\App\Models\User::where('first_name', 'Sarah')->first()->getAccountBalanceSummary()"

# Count packages by status
php artisan tinker --execute="\App\Models\Package::groupBy('status')->selectRaw('status, count(*) as count')->get()"
```

## Expected Test Results

### Balance Calculations
All balance calculations should follow this logic:
1. **Charge**: Account balance decreases by net charge amount
2. **Payment**: Account balance increases by payment amount  
3. **Credit Application**: Credit balance decreases, reduces net charge
4. **Overpayment**: Excess goes to credit balance

### Transaction Recording
Every distribution should create:
- 1 charge transaction (for package cost minus credit)
- 1 payment transaction (for amount collected)
- 1 credit transaction (if overpayment occurs)
- 1 credit debit transaction (if credit applied)

### Dashboard Display
Customer dashboard should show:
- Account balance (can be negative)
- Credit balance (only if > 0)
- Total available balance
- Recent transaction history

## Edge Cases Covered

1. **Zero Amount Scenarios**: $0 payments, $0 packages
2. **Negative Balances**: Customers owing money
3. **Large Numbers**: High-value packages and payments
4. **Decimal Precision**: Amounts with cents
5. **Multiple Packages**: Bulk distributions
6. **Credit Exhaustion**: Using all available credit
7. **Historical Data**: Past distributions and transactions
8. **Workflow States**: Packages in all possible statuses

## Performance Considerations

The test data is designed to:
- Test system performance with realistic data volumes
- Validate database constraints and relationships
- Ensure UI responsiveness with various data sizes
- Test concurrent operations (multiple distributions)

## Maintenance

### Regular Tasks
1. Reset test data weekly: `php artisan reset:test-data --confirm`
2. Run full test suite: `php artisan test`
3. Verify dashboard displays correctly
4. Test new features with existing scenarios

### Adding New Scenarios
1. Update `ComprehensiveTestDataSeeder.php`
2. Add corresponding test cases
3. Update this documentation
4. Reset test data to apply changes

This comprehensive test data ensures that every aspect of the package distribution and balance system is thoroughly tested with realistic scenarios.