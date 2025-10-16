# Transaction-Manifest Backfill Process

## Overview

This document describes the process for backfilling existing transactions with manifest links in the ShipSharkLtd system. The backfill process analyzes existing transactions and links them to appropriate manifests based on their package or distribution references.

## Commands Available

### 1. Analysis Command
```bash
php artisan analyze:transaction-manifest-links [options]
```

**Purpose**: Analyze the current state of transaction-manifest linking and identify opportunities for backfilling.

**Options**:
- `--detailed`: Show detailed breakdown by customer and manifest
- `--export=filename.csv`: Export results to CSV file

**What it shows**:
- Overall transaction statistics
- Number of transactions that could be linked to manifests
- Manifest coverage analysis
- Recommendations for next steps

### 2. Backfill Command
```bash
php artisan backfill:transaction-manifest-links [options]
```

**Purpose**: Actually perform the backfill process to link transactions to manifests.

**Options**:
- `--dry-run`: Show what would be updated without making changes
- `--batch-size=100`: Number of transactions to process per batch
- `--force`: Skip confirmation prompt

**What it does**:
- Identifies transactions that can be linked to manifests
- Updates transaction records to link them to appropriate manifests
- Preserves original reference information in metadata
- Provides detailed progress reporting

## Database Schema Enhancement (Optional)

For better performance, you can add a direct `manifest_id` column to the `customer_transactions` table:

```bash
php artisan migrate
```

This migration adds:
- `manifest_id` column for direct manifest reference
- Foreign key constraint to manifests table
- Indexes for improved query performance

The system automatically detects and uses this column when available, falling back to the reference fields for backward compatibility.

## Backfill Process Flow

### Step 1: Analysis
Run the analysis command to understand the current state:

```bash
php artisan analyze:transaction-manifest-links --detailed
```

This will show you:
- How many transactions exist
- How many are already linked to manifests
- How many could potentially be linked
- Which manifests have transactions vs. packages

### Step 2: Dry Run
Test the backfill process without making changes:

```bash
php artisan backfill:transaction-manifest-links --dry-run
```

This will show you exactly what would be updated without making any changes.

### Step 3: Execute Backfill
Run the actual backfill process:

```bash
php artisan backfill:transaction-manifest-links
```

Or with custom batch size:

```bash
php artisan backfill:transaction-manifest-links --batch-size=50
```

## Linking Logic

The backfill process uses the following logic to determine manifest links:

### 1. Direct Package Reference
If `reference_type = 'App\Models\Package'` and `reference_id` exists:
- Find the package by ID
- Use the package's `manifest_id`

### 2. Package Distribution Reference
If `reference_type` is a distribution type and `reference_id` exists:
- Find the distribution by ID
- Get all packages in the distribution
- If all packages belong to the same manifest, use that manifest

### 3. Metadata Package IDs
If transaction metadata contains `package_ids`:
- Find all packages by IDs
- If all packages belong to the same manifest, use that manifest

### 4. Metadata Distribution ID
If transaction metadata contains `distribution_id`:
- Find the distribution
- Get all packages in the distribution
- If all packages belong to the same manifest, use that manifest

### 5. Metadata Manifest IDs
If transaction metadata already contains `manifest_ids`:
- If only one manifest ID, use that manifest

## Safety Features

### Dry Run Mode
Always test with `--dry-run` first to see what would be changed.

### Batch Processing
Processes transactions in configurable batches to avoid memory issues and allow for monitoring progress.

### Metadata Preservation
Original reference information is preserved in the transaction metadata:

```json
{
  "backfill_info": {
    "original_reference_type": "App\\Models\\Package",
    "original_reference_id": 123,
    "backfilled_at": "2024-10-16T13:30:00.000Z",
    "manifest_id": 5,
    "manifest_name": "AIR-2024-001"
  }
}
```

### Error Handling
- Individual transaction errors don't stop the entire process
- Detailed error reporting for failed transactions
- Transaction rollback on critical errors

## Performance Considerations

### Database Indexes
The optional migration adds several indexes for better performance:
- `manifest_id` index
- `(user_id, manifest_id)` composite index
- `(manifest_id, type)` composite index

### Batch Size
Default batch size is 100 transactions. Adjust based on your system:
- Smaller batches (50) for systems with limited memory
- Larger batches (500) for powerful systems with lots of RAM

### Memory Usage
The process loads transactions in batches to manage memory usage efficiently.

## Monitoring and Validation

### Progress Monitoring
The backfill command provides real-time progress updates:
```
✓ Updated transaction #1234
✓ Updated transaction #1235
✗ Error processing transaction #1236: Package not found
```

### Post-Backfill Validation
After backfill, run the analysis command again to verify results:

```bash
php artisan analyze:transaction-manifest-links
```

### Query Examples
Verify the backfill worked correctly:

```php
// Count transactions linked to manifests
$linkedCount = CustomerTransaction::linkedToManifests()->count();

// Get transactions for a specific manifest
$manifestTransactions = CustomerTransaction::forManifest(1)->get();

// Get manifest financial summary
$manifest = Manifest::find(1);
$summary = $manifest->getFinancialSummary();
```

## Troubleshooting

### Common Issues

**Issue**: "No transactions found that can be linked to manifests"
- **Cause**: All transactions are already linked or don't have package/distribution references
- **Solution**: Check if transactions have proper reference_type and reference_id values

**Issue**: "Package not found" errors
- **Cause**: Transaction references a package that no longer exists
- **Solution**: Clean up orphaned transaction references or skip these transactions

**Issue**: "Multiple manifests found for packages"
- **Cause**: Packages in a distribution belong to different manifests
- **Solution**: These transactions cannot be automatically linked and need manual review

### Recovery
If something goes wrong during backfill:

1. **Check the metadata**: Original reference information is preserved
2. **Restore from backup**: If you have database backups
3. **Manual correction**: Use the preserved metadata to manually correct links

## Best Practices

### Before Running Backfill
1. **Backup your database**
2. **Run analysis command** to understand the scope
3. **Test with dry-run** to verify the process
4. **Run during low-traffic periods**

### After Running Backfill
1. **Verify results** with analysis command
2. **Test transaction filtering** in the UI
3. **Check manifest financial summaries**
4. **Monitor for any issues** in transaction reporting

### Ongoing Maintenance
1. **New transactions** are automatically linked during distribution
2. **Monitor manifest coverage** regularly
3. **Run analysis periodically** to catch any missed transactions

## Example Workflow

```bash
# 1. Analyze current state
php artisan analyze:transaction-manifest-links --detailed --export=analysis.csv

# 2. Test backfill process
php artisan backfill:transaction-manifest-links --dry-run

# 3. Run actual backfill
php artisan backfill:transaction-manifest-links --batch-size=100

# 4. Verify results
php artisan analyze:transaction-manifest-links

# 5. Test in application
# - Use transaction management interface
# - Check manifest financial summaries
# - Verify filtering works correctly
```

This process ensures that all existing transactions are properly linked to manifests, enabling better financial tracking and reporting capabilities.