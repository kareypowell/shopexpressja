# Write-off Enhancement: Fixed Amount and Percentage Support

## Overview
Enhanced the package distribution system to support both fixed dollar amounts and percentage-based write-offs, providing more flexibility for customer discounts and adjustments.

## Features Added

### 1. Write-off Type Selection
- **Fixed Amount**: Enter a specific dollar amount (existing functionality)
- **Percentage**: Enter a percentage of the total cost (new functionality)

### 2. Dynamic Calculation
- Percentage write-offs are calculated in real-time based on the total package cost
- Visual feedback shows the calculated amount for percentage-based write-offs
- Automatic validation ensures percentages stay within 0-100% range

### 3. Enhanced UI
- Radio button selection between "Fixed Amount" and "Percentage"
- Contextual input fields based on selected type
- Real-time calculation display for percentage write-offs
- Consistent validation and error handling

## Usage Examples

### Fixed Amount Write-off
```
Total Cost: $1,000.00
Write-off Type: Fixed Amount
Write-off Amount: $150.00
Net Total: $850.00
```

### Percentage Write-off
```
Total Cost: $1,000.00
Write-off Type: Percentage
Write-off Percentage: 15%
Calculated Write-off: $150.00
Net Total: $850.00
```

## Technical Implementation

### Backend Changes
- Added `writeOffType` and `writeOffPercentage` properties to PackageDistribution Livewire component
- Implemented `getCalculatedWriteOffAmount()` method for dynamic calculation
- Updated validation rules to handle both write-off types
- Enhanced payment status calculation to use calculated write-off amounts

### Frontend Changes
- Updated package distribution form with radio button selection
- Added conditional input fields for fixed amount vs percentage
- Real-time calculation display for percentage write-offs
- Updated distribution summary to show calculated write-off amounts

### Database
- No database schema changes required
- The calculated write-off amount is stored in the existing `write_off_amount` field
- Write-off reason field captures the type and percentage for audit purposes

## Validation Rules
- Fixed amounts must be between $0 and the total package cost
- Percentages must be between 0% and 100%
- Write-off reason is required when any write-off amount > $0
- All existing validation rules remain intact

## Backward Compatibility
- Existing write-off functionality remains unchanged
- Default write-off type is "fixed" to maintain current behavior
- All existing tests continue to pass
- No migration required

## Receipt Enhancement

### Write-off Type Display
Receipts now clearly indicate whether a write-off was applied as a fixed amount or percentage:

**Fixed Amount Example:**
```
Discount/Write-off: -$25.00
Reason: Customer loyalty discount (Fixed amount discount)
```

**Percentage Example:**
```
Discount/Write-off: -$25.00
Reason: Customer loyalty discount (20% discount = $25.00)
```

### Database Storage
- Added `write_off_reason` column to `package_distributions` table
- Stores formatted reason with type information for audit purposes
- Maintains backward compatibility with existing records

### Receipt Templates Updated
- Individual package distribution receipts
- Consolidated package distribution receipts
- Email receipt templates
- PDF receipt generation

## Benefits
1. **Flexibility**: Support for both fixed and percentage-based discounts
2. **Accuracy**: Real-time calculation eliminates manual calculation errors
3. **User Experience**: Clear visual feedback and validation
4. **Audit Trail**: Write-off reasons capture the discount type and amount with full transparency
5. **Receipt Clarity**: Customers can see exactly how their discount was calculated
6. **Compliance**: Detailed documentation for accounting and audit purposes
7. **Scalability**: Easy to extend for additional write-off types in the future