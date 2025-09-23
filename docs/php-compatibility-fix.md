# PHP Compatibility Fix

## Issue
The application was experiencing a `ParseError: syntax error, unexpected '??' (T_COALESCE), expecting :: (T_PAAMAYIM_NEKUDOTAYIM)` error when accessing the package distribution features.

## Root Cause
The null coalescing operator (`??`) was introduced in PHP 7.0. The error indicates the application is running on a PHP version older than 7.0 that doesn't support this operator.

## Files Fixed
The following files contained null coalescing operators that were replaced with PHP 5.6+ compatible syntax:

### 1. `app/Models/PackageDistribution.php`
**Before:**
```php
$totalReceived = $this->amount_collected + ($this->credit_applied ?? 0);
```

**After:**
```php
$creditApplied = $this->credit_applied ? $this->credit_applied : 0;
$totalReceived = $this->amount_collected + $creditApplied;
```

### 2. `app/Services/PackageFeeService.php`
**Before:**
```php
'clearance_fee' => $fees['clearance_fee'] ?? 0,
'storage_fee' => $fees['storage_fee'] ?? 0,
'delivery_fee' => $fees['delivery_fee'] ?? 0,
```

**After:**
```php
'clearance_fee' => isset($fees['clearance_fee']) ? $fees['clearance_fee'] : 0,
'storage_fee' => isset($fees['storage_fee']) ? $fees['storage_fee'] : 0,
'delivery_fee' => isset($fees['delivery_fee']) ? $fees['delivery_fee'] : 0,
```

### 3. `app/Services/PackageDistributionService.php`
**Before:**
```php
'freight_price' => $package->freight_price ?? 0,
'clearance_fee' => $package->clearance_fee ?? 0,
```

**After:**
```php
'freight_price' => $package->freight_price ? $package->freight_price : 0,
'clearance_fee' => $package->clearance_fee ? $package->clearance_fee : 0,
```

## Replacement Patterns Used

1. **For array keys:**
   - `$array['key'] ?? 'default'` → `isset($array['key']) ? $array['key'] : 'default'`

2. **For object properties:**
   - `$object->property ?? 'default'` → `$object->property ? $object->property : 'default'`

3. **For complex expressions:**
   - `($expression ?? 0)` → `($expression ? $expression : 0)`

## Testing
After applying the fixes:
- ✅ All syntax errors resolved
- ✅ Application loads successfully
- ✅ Package distribution route accessible
- ✅ Unit tests pass
- ✅ Fee service functionality works correctly

## Recommendation
While this fix ensures compatibility with older PHP versions, it's recommended to upgrade to PHP 7.0+ to take advantage of:
- Null coalescing operator (`??`)
- Null coalescing assignment operator (`??=`) in PHP 7.4+
- Better performance and security
- Modern language features

## Future Considerations
If upgrading PHP is not possible, consider:
1. Creating helper functions for common null checks
2. Using a code linter to catch PHP version incompatibilities
3. Setting up CI/CD with the target PHP version for testing

## Alternative Helper Function
For cleaner code, you could create a helper function:

```php
if (!function_exists('null_coalesce')) {
    function null_coalesce($value, $default = null) {
        return $value !== null ? $value : $default;
    }
}

// Usage:
$result = null_coalesce($variable, 'default_value');
```

This would make the code more readable while maintaining compatibility.