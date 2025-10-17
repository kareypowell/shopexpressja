# Write-Off Balance Calculation Fix

## Problem
Write-offs were inflating the "outstanding balance" calculations in reports and charts. Write-offs represent forgiven debt and should **reduce** the amount owed, not be ignored in the calculation.

## Root Cause
The outstanding balance was calculated as:
```
outstanding_balance = total_owed - total_collected
```

Where:
- `total_owed` = sum of all package charges (freight + clearance + storage + delivery)
- `total_collected` = sum from package_distribution_items (actual payments)

Write-offs were not included in either calculation, so they didn't reduce the outstanding amount.

## Solution Implemented

### 1. BusinessReportService.php - calculateCollectedForManifest()
**Changed:** Write-offs are now added to the collected amount since they reduce what's owed.

```php
// Before: Only counted actual collections
$collected = DB::table('package_distribution_items')...->sum('pdi.total_cost');

// After: Include write-offs as they reduce outstanding balance
$collected = DB::table('package_distribution_items')...->sum('pdi.total_cost');
$writeOffs = DB::table('customer_transactions')
    ->where('type', 'write_off')
    ->where('manifest_id', $manifestId)
    ->sum('amount');
return $collected + $writeOffs;
```

### 2. BusinessReportService.php - getCollectionsData()
**Added:** Daily write-off tracking for charts and reports.

```php
// Added query to get daily write-offs
$dailyWriteOffs = DB::table('customer_transactions')
    ->where('type', 'write_off')
    ->whereBetween('created_at', [$dateFrom, $dateTo])
    ->selectRaw('DATE(created_at) as write_off_date, SUM(amount) as daily_write_off_amount')
    ->groupBy('write_off_date')
    ->get();

// Added to return data
return [
    'daily_collections' => $dailyCollections,
    'daily_write_offs' => $dailyWriteOffData,  // NEW
    'total_write_offs' => $totalWriteOffs,      // NEW
    ...
];
```

### 3. ReportDashboard.php - getSalesChartData()
**Enhanced:** Chart now displays both daily collections and daily write-offs.

```php
// Before: Only showed collections
datasets: [{ label: 'Daily Collections ($)', data: [...] }]

// After: Shows both collections and write-offs
datasets: [
    { label: 'Daily Collections ($)', data: [...], color: blue },
    { label: 'Daily Write-Offs ($)', data: [...], color: red }
]
```

### 4. report-dashboard.blade.php - Table Structure
**Added:** "Written Off" column to show write-offs separately from collections.

```html
<!-- Before: 7 columns -->
MANIFEST | TYPE | PACKAGES | TOTAL OWED | COLLECTED | OUTSTANDING | RATE

<!-- After: 8 columns -->
MANIFEST | TYPE | PACKAGES | TOTAL OWED | COLLECTED | WRITTEN OFF | OUTSTANDING | RATE
```

### 5. BusinessReportService.php - Manifest Data
**Enhanced:** Each manifest now includes separate tracking of collections vs write-offs.

```php
return [
    'total_collected' => $actualCollected,      // Only actual payments
    'total_write_offs' => $totalWriteOffs,      // Forgiven debt
    'outstanding_balance' => $totalOwed - ($actualCollected + $totalWriteOffs),
    ...
];
```

## Impact

### Reports
- **Manifest Performance Report:** Outstanding balances now accurately reflect write-offs
- **Financial Summary:** Total outstanding amounts are no longer overstated
- **Customer Analytics:** Debt calculations properly account for forgiven amounts

### Charts
- **Sales Dashboard:** Now shows both daily collections and daily write-offs as separate lines
- **Visual Clarity:** Users can see the impact of write-offs on revenue collection

### Calculations
- Outstanding Balance = Total Owed - (Collections + Write-Offs)
- Collection Rate = (Collections + Write-Offs) / Total Owed × 100%

## Files Modified
1. `app/Services/BusinessReportService.php`
   - `calculateCollectedForManifest()` - Include write-offs in collected amount
   - `getCollectionsData()` - Add daily write-off tracking
   - Manifest data mapping - Separate collections from write-offs

2. `app/Http/Livewire/Reports/ReportDashboard.php`
   - `getSalesChartData()` - Add write-offs dataset to chart

3. `resources/views/livewire/reports/report-dashboard.blade.php`
   - Added "Written Off" column to manifest table
   - Updated colspan for empty state

## Testing Recommendations
1. Verify outstanding balances match expected values after write-offs
2. Check that charts display both collections and write-offs correctly
3. Confirm collection rates are accurate when write-offs are present
4. Test with manifests that have no write-offs (should work as before)
5. Test with manifests that have only write-offs (no actual collections)
