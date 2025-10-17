# Write-Off Calculation Update

## Problem
The "WRITTEN OFF" column was showing $0.00 for all manifests because:
1. No transactions with `type = 'write_off'` exist in the database
2. The code was only looking for explicit write-off transactions

## Solution
Updated the code to treat certain **credit transactions as write-offs** when they match discount patterns.

## What Now Counts as a Write-Off

### Primary: Explicit Write-Offs
```sql
type = 'write_off' AND manifest_id = [manifest_id]
```

### Fallback: Discount Credits
If no explicit write-offs exist, the system now includes credits with descriptions containing:
- "discount"
- "write"
- "forgiv"
- "waive"

For customers who have packages in the manifest.

## Example
For "Shernett Johnson" manifest:
- Customer has credit: "family and friends discount" - $1,200.00
- This will now show in the "WRITTEN OFF" column
- Outstanding balance will be reduced by this amount

## Changes Made

### 1. BusinessReportService.php - Manifest Data
```php
// Before: Only looked for write_off type
$totalWriteOffs = DB::table('customer_transactions')
    ->where('type', 'write_off')
    ->where('manifest_id', $manifest->id)
    ->sum('amount');

// After: Falls back to discount credits if no write-offs
if ($totalWriteOffs == 0) {
    $customerIds = $packages->pluck('user_id')->unique();
    $totalWriteOffs = DB::table('customer_transactions')
        ->whereIn('user_id', $customerIds)
        ->where('type', 'credit')
        ->where(function($q) {
            $q->where('description', 'like', '%discount%')
              ->orWhere('description', 'like', '%write%')
              ->orWhere('description', 'like', '%forgiv%')
              ->orWhere('description', 'like', '%waive%');
        })
        ->sum('amount');
}
```

### 2. BusinessReportService.php - Daily Write-Offs
```php
// Now includes both write_off type AND discount credits
$writeOffQuery = DB::table('customer_transactions')
    ->where(function($q) {
        $q->where('type', 'write_off')
          ->orWhere(function($q2) {
              $q2->where('type', 'credit')
                 ->where(function($q3) {
                     $q3->where('description', 'like', '%discount%')
                        ->orWhere('description', 'like', '%write%')
                        ->orWhere('description', 'like', '%forgiv%')
                        ->orWhere('description', 'like', '%waive%');
                 });
          });
    });
```

## Expected Results After Deployment

### Table
Manifests with customers who have discount credits will now show:
```
MANIFEST         | COLLECTED | WRITTEN OFF | OUTSTANDING
Shernett Johnson | $13,863.20| $1,200.00   | (reduced)
```

### Chart
The "Daily Write-Offs ($)" line will now show data for dates when discount credits were recorded.

## Important Notes

### This is a Heuristic Approach
The system now **guesses** which credits are write-offs based on description keywords. This may:
- ✅ Include legitimate discounts/write-offs
- ❌ Include credits that aren't actually write-offs
- ❌ Miss write-offs with different descriptions

### Better Long-Term Solution
For accurate tracking, use the proper transaction type:
```php
// Instead of:
$customer->addCredit(100, 'family discount', ...);

// Use:
$customer->recordWriteOff(100, 'family discount', ...);
```

## Deployment Steps

1. **Deploy code**
   ```bash
   git pull origin main
   ```

2. **Clear caches**
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Verify**
   - Navigate to Reports & Analytics > Sales & Collections
   - Check "WRITTEN OFF" column shows amounts (not all $0.00)
   - Check chart shows "Daily Write-Offs ($)" line with data

## Rollback
If this causes issues (e.g., wrong credits being counted as write-offs):
```bash
git checkout HEAD~1 app/Services/BusinessReportService.php
php artisan cache:clear
```

Then properly categorize transactions as `write_off` type instead of `credit`.
