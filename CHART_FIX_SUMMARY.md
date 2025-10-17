# Chart and Write-Off Display - Status and Fix

## Current Status

### ✅ What's Working
1. **"WRITTEN OFF" column added** - Shows in the table between COLLECTED and OUTSTANDING
2. **Chart code updated** - Now configured to show both Daily Collections and Daily Write-Offs lines
3. **Outstanding balance calculation fixed** - Properly accounts for write-offs

### ❌ Why You're Seeing Zeros
**Your database has 0 write-off transactions.**

Transaction types in your database:
- `charge`: 14 (amounts owed)
- `credit`: 14 (includes discounts)
- `payment`: 10 (actual payments)
- `write_off`: **0** ← This is why the column shows $0.00

## Why the Chart Shows Only One Line

The chart is configured to show both lines, but since there are no write-off transactions:
- **Blue line (Daily Collections)**: Shows actual payment data ✅
- **Red line (Daily Write-Offs)**: Shows as a flat line at $0 (may not be visible if all values are zero)

## How to Fix

### Option 1: Start Recording Write-Offs (Recommended)
When you need to forgive debt or give discounts, use:

```php
$customer->recordWriteOff(
    amount: 100.00,
    description: 'Reason for write-off',
    createdBy: auth()->id(),
    referenceType: 'App\\Models\\Manifest',
    referenceId: $manifestId
);
```

### Option 2: Convert Existing Discount Credits to Write-Offs
If your existing "discount" credits should be write-offs:

```bash
php artisan tinker
```

```php
// Find discount credits
$discounts = \App\Models\CustomerTransaction::where('type', 'credit')
    ->where('description', 'like', '%discount%')
    ->get();

echo "Found " . $discounts->count() . " discount transactions\n";

// Convert to write_off type
foreach ($discounts as $transaction) {
    $transaction->update(['type' => 'write_off']);
    echo "Converted transaction #{$transaction->id}\n";
}

echo "Done! Refresh your report to see write-offs.\n";
```

### Option 3: Test with Sample Data
Create a test write-off to verify the display:

```php
$customer = \App\Models\User::customers()->first();
$manifest = \App\Models\Manifest::first();

if ($customer && $manifest) {
    $customer->recordWriteOff(
        100.00,
        'Test write-off for chart display',
        auth()->id(),
        'App\\Models\\Manifest',
        $manifest->id
    );
    echo "Test write-off created! Refresh the report.\n";
}
```

## After Creating Write-Offs

Once you have write-off transactions, you'll see:
1. **Table**: "WRITTEN OFF" column will show actual amounts (not $0.00)
2. **Chart**: Red line for "Daily Write-Offs ($)" will show the forgiven amounts
3. **Outstanding**: Will be correctly reduced by write-offs

## Deployment Checklist

- [x] Code updated to show both chart lines
- [x] "WRITTEN OFF" column added to table
- [x] Outstanding balance calculation fixed
- [ ] **Action needed**: Record write-off transactions or convert existing discounts
- [ ] Clear cache: `php artisan cache:clear && php artisan view:clear`
- [ ] Refresh browser with hard reload (Cmd+Shift+R)

## Expected Result After Write-Offs Exist

### Chart
```
Daily Collections ($) ─────── (blue line with actual data)
Daily Write-Offs ($)  ─ ─ ─ ─ (red line with write-off data)
```

### Table
```
MANIFEST | TYPE | PACKAGES | TOTAL OWED | COLLECTED | WRITTEN OFF | OUTSTANDING | RATE
Manifest1|  Air |    5     |  $1,000.00 |   $800.00 |    $100.00  |   $100.00   | 90%
```

Where:
- COLLECTED = Actual payments received
- WRITTEN OFF = Forgiven debt
- OUTSTANDING = TOTAL OWED - (COLLECTED + WRITTEN OFF)
- RATE = (COLLECTED + WRITTEN OFF) / TOTAL OWED × 100%
