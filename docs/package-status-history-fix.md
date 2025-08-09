# Package Status History Fix

## Issue
When updating package fees and transitioning to "Ready" status, the application was throwing a database error:

```
SQLSTATE[HY000]: General error: 1364 Field 'old_status' doesn't have a default value
```

## Root Cause
The `PackageFeeService` was attempting to create a status history record but was not providing all the required fields that the `package_status_histories` table expects.

### Required Fields
The `package_status_histories` table requires:
- `package_id` (foreign key)
- `old_status` (string, no default value)
- `new_status` (string, no default value) 
- `changed_by` (foreign key to users)
- `changed_at` (timestamp)
- `notes` (nullable text)

### Original Problematic Code
```php
// This was missing required fields
$package->statusHistory()->create([
    'status' => PackageStatus::READY, // Wrong field name
    'changed_by' => $updatedBy->id,
    'notes' => 'Package fees updated and set to ready for pickup',
    'metadata' => [...], // Field doesn't exist in table
]);
```

## Solution
Fixed the status history creation to provide all required fields with correct names:

```php
// Store the old status before updating the package
$oldStatus = $package->status;

// Update package fees and status
$package->update([...]);

// Create proper status history record
$package->statusHistory()->create([
    'old_status' => $oldStatus->value,
    'new_status' => PackageStatus::READY,
    'changed_by' => $updatedBy->id,
    'changed_at' => now(),
    'notes' => 'Package fees updated and set to ready for pickup',
]);
```

## Key Changes Made

1. **Capture Old Status**: Store the original status before updating the package
2. **Correct Field Names**: Use `old_status` and `new_status` instead of just `status`
3. **Provide All Required Fields**: Include `changed_at` timestamp
4. **Remove Invalid Fields**: Removed `metadata` field that doesn't exist in the table
5. **Fix Notification Call**: Corrected the method name and parameters for status notifications

## Files Modified
- `app/Services/PackageFeeService.php` - Fixed status history creation
- `tests/Unit/PackageFeeServiceTest.php` - Added comprehensive test for status history

## Testing
Added a new test case that verifies:
- Package fees are updated correctly
- Package status transitions to READY
- Status history record is created with proper old/new status values
- All required fields are populated

## Verification
After the fix:
- ✅ Package fee updates work without database errors
- ✅ Status history is properly recorded
- ✅ Email notifications are sent correctly
- ✅ All tests pass (5/5)

## Prevention
To prevent similar issues in the future:
1. Always check the database schema before creating records
2. Use the model's `$fillable` array as a reference for allowed fields
3. Add comprehensive tests that verify database operations
4. Consider using factory methods or dedicated services for complex record creation

## Related Components
- `PackageStatusService` - Handles standard status transitions correctly
- `PackageStatusHistory` model - Defines the expected fields and relationships
- `PackageWorkflow` component - Uses this service for fee entry modal