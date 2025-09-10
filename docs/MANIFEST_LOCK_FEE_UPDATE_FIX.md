# Manifest Lock Fee Update Fix

## Issue Description

The "Update Fees" functionality was not properly respecting the manifest lock status. Users could still access fee update options even when a manifest was closed/locked, which violated the manifest locking enhancement requirements.

## Root Cause

The fee update methods in both `IndividualPackagesTab` and `ConsolidatedPackagesTab` components were missing permission checks to verify if the manifest was open before allowing fee modifications.

## Changes Made

### 1. IndividualPackagesTab Component

**File:** `app/Http/Livewire/Manifests/IndividualPackagesTab.php`

- Added permission check to `showFeeEntryModal()` method
- Added permission check to `processFeeUpdate()` method
- Both methods now return early with error message if manifest is closed

**File:** `resources/views/livewire/manifests/individual-packages-tab.blade.php`

- Wrapped "Update Fees" button in `@if($canEdit)` conditional
- Button is now hidden when manifest is closed

### 2. ConsolidatedPackagesTab Component

**File:** `app/Http/Livewire/Manifests/ConsolidatedPackagesTab.php`

- Added permission check to `showConsolidatedFeeEntryModal()` method
- Added permission check to `processConsolidatedFeeUpdate()` method
- Both methods now return early with error message if manifest is closed

### 3. Permission Check Logic

All fee update methods now use the existing `canEditManifest` property which:
- Checks if the user has proper permissions
- Uses `ManifestLockService` to verify if the manifest is open
- Returns `false` for closed manifests

### 4. User Experience

When attempting to update fees on a closed manifest:
- User sees error toast: "Cannot update package fees on a closed manifest."
- Fee modal does not open
- No database changes are made
- User is clearly informed why the action was blocked

## Testing

**File:** `tests/Feature/ManifestLockFeeUpdateTest.php`

Comprehensive test suite covering:
- ✅ Prevents showing fee entry modal on closed manifest
- ✅ Prevents processing fee updates on closed manifest  
- ✅ Allows fee updates on open manifest
- ✅ Uses read-only template for closed manifest
- ✅ Uses editable template for open manifest

## Requirements Satisfied

This fix ensures that:
- **Requirement 3.1**: Package editing is properly restricted when manifest is closed
- **Requirement 3.2**: Fee updates respect manifest lock status
- **Requirement 4.1**: Clear error messages inform users why actions are blocked
- **Requirement 4.2**: No unauthorized modifications can be made to closed manifests

## Security Impact

- Prevents unauthorized fee modifications on closed manifests
- Maintains data integrity by enforcing business rules
- Provides clear audit trail through error messages
- Consistent with other manifest locking functionality

## Backward Compatibility

- No breaking changes to existing functionality
- Fee updates continue to work normally on open manifests
- Error handling is graceful and user-friendly
- Template changes are conditional and don't affect open manifests