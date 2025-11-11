# Account Number Format Update

## Overview
Account numbers have been updated from the format `ALQS####` (random 4 digits) to `ALQS8149-###` (sequential 3 digits from 100-999).

## Changes Made

### 1. AccountNumberService
- **Location**: `app/Services/AccountNumberService.php`
- **Behavior**: Generates sequential account numbers starting from 100
- **Format**: `ALQS8149-100`, `ALQS8149-101`, `ALQS8149-102`, etc.
- **Limit**: Maximum 900 unique account numbers (100-999)

### 2. Migration
- **Location**: `database/migrations/2025_11_11_174448_update_account_numbers_to_new_format.php`
- **Purpose**: Updates existing account numbers from old format to new format
- **Behavior**: Converts all old-format account numbers sequentially

### 3. Seeder
- **Location**: `database/seeders/UpdateAccountNumbersSeeder.php`
- **Purpose**: Standalone seeder to update existing accounts
- **Usage**: Can be run independently without running all seeders

## How to Apply Changes

### Option 1: Using Migration (Recommended for Production)
```bash
php artisan migrate
```

This will automatically update all existing account numbers to the new format.

### Option 2: Using Seeder (For Manual Updates)
```bash
php artisan db:seed --class=UpdateAccountNumbersSeeder
```

This provides detailed output showing each account number conversion.

## Important Notes

1. **Sequential Numbering**: New account numbers are assigned sequentially, not randomly
2. **Starting Point**: If no accounts exist, numbering starts at 100
3. **Continuation**: If accounts already exist, numbering continues from the highest existing number
4. **Limit**: System supports up to 900 unique account numbers (ALQS8149-100 through ALQS8149-999)
5. **One-Way Migration**: The migration cannot be reversed as original random numbers are not stored

## Testing

All tests have been updated to reflect the new sequential behavior:
```bash
php artisan test --filter AccountNumberServiceTest
```

## Examples

### Before
- `ALQS1234`
- `ALQS5678`
- `ALQS9012`

### After
- `ALQS8149-100`
- `ALQS8149-101`
- `ALQS8149-102`
