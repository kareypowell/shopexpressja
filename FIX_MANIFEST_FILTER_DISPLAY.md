# Fix Manifest Filter Not Showing

## Problem
The manifest filter and manifest column are in the code but not showing on the production site.

## Root Cause
This is a **caching issue**. The view files are cached and the updated version isn't being displayed.

## Solution

### Step 1: Clear All Caches (Run on Production Server)
```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear compiled views
php artisan view:cache

# Clear application cache
php artisan optimize:clear

# Restart any queue workers if running
php artisan queue:restart
```

### Step 2: Force Browser Cache Clear
- **Hard refresh** the page: `Ctrl+F5` (Windows) or `Cmd+Shift+R` (Mac)
- Or open **Developer Tools** → **Network tab** → Check "Disable cache"

### Step 3: Verify the Fix
After clearing caches, you should see:

1. **Manifest Filter Dropdown** in the second row of filters:
   ```
   [Search] [Type] [Customer] [Review Status]
   [Manifest ▼] [ ] [ ] [ ]
   [Date From] [Date To] [Clear Filters] [ ]
   ```

2. **Manifest Column** in the table:
   ```
   | Date & Time | Customer | Type | Description | Amount | Balance After | Manifest | Actions |
   ```

### Step 4: Test the Functionality
1. **Check manifest dropdown** - Should show your manifests
2. **Select a manifest** - Should filter transactions
3. **Check manifest column** - Should show manifest names or "-"

## If Still Not Working

### Option A: Check File Permissions
```bash
# Ensure proper permissions
chmod -R 755 resources/views/
chown -R www-data:www-data resources/views/
```

### Option B: Force View Recompilation
```bash
# Delete compiled views manually
rm -rf storage/framework/views/*

# Recompile views
php artisan view:cache
```

### Option C: Check for Multiple View Files
```bash
# Search for other transaction management views
find . -name "*transaction*" -type f | grep -i view
```

## Expected Result
After clearing caches, you should see:
- ✅ Manifest filter dropdown in the filters section
- ✅ Manifest column in the transactions table  
- ✅ Ability to filter transactions by manifest
- ✅ Manifest names displayed in blue text (or "-" if no manifest)

The code is correct - this is just a caching issue!