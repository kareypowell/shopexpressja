# Deployment Instructions - Write-Off Balance Fix

## Changes Summary
Fixed outstanding balance calculations to properly account for write-offs. Write-offs now reduce the outstanding amount instead of being ignored.

## What Changed

### 1. Chart Enhancement
- **Before:** Only showed "Daily Collections ($)"
- **After:** Shows both "Daily Collections ($)" (blue) and "Daily Write-Offs ($)" (red)

### 2. Table Enhancement
- **Before:** 7 columns (MANIFEST | TYPE | PACKAGES | TOTAL OWED | COLLECTED | OUTSTANDING | RATE)
- **After:** 8 columns - Added "WRITTEN OFF" column between COLLECTED and OUTSTANDING

### 3. Calculation Fix
- **Before:** Outstanding = Total Owed - Collections (write-offs ignored)
- **After:** Outstanding = Total Owed - (Collections + Write-Offs)

## Files Changed
1. `app/Services/BusinessReportService.php`
2. `app/Http/Livewire/Reports/ReportDashboard.php`
3. `resources/views/livewire/reports/report-dashboard.blade.php`

## Deployment Steps

### 1. Deploy Code
```bash
git pull origin main
```

### 2. Clear Caches
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### 3. Verify Changes
1. Navigate to Reports & Analytics > Sales & Collections
2. Check that the chart shows two lines:
   - Blue line: Daily Collections ($)
   - Red line: Daily Write-Offs ($)
3. Check that the "Recent Manifests" table has 8 columns including "WRITTEN OFF"
4. Verify that outstanding balances are lower than before (if write-offs exist)

## Expected Results

### For Manifests with Write-Offs:
- **COLLECTED:** Shows only actual payments received
- **WRITTEN OFF:** Shows forgiven debt amount
- **OUTSTANDING:** Total Owed - (Collected + Written Off)
- **RATE:** (Collected + Written Off) / Total Owed × 100%

### For Manifests without Write-Offs:
- **COLLECTED:** Shows actual payments
- **WRITTEN OFF:** Shows $0.00
- **OUTSTANDING:** Total Owed - Collected (same as before)
- **RATE:** Collected / Total Owed × 100% (same as before)

## Rollback Plan
If issues occur, revert the three files:
```bash
git checkout HEAD~1 app/Services/BusinessReportService.php
git checkout HEAD~1 app/Http/Livewire/Reports/ReportDashboard.php
git checkout HEAD~1 resources/views/livewire/reports/report-dashboard.blade.php
php artisan cache:clear
php artisan view:clear
```

## Testing Checklist
- [ ] Chart displays both collections and write-offs
- [ ] Table has "WRITTEN OFF" column
- [ ] Outstanding balances are accurate
- [ ] Collection rates include write-offs
- [ ] Manifests without write-offs still display correctly
- [ ] No PHP errors in logs
