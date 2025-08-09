# Route Model Binding Fix

## Issue
The `EditManifestPackage` Livewire component was throwing a `TypeError: "Object of class App\Models\Manifest could not be converted to int"` when accessing the edit package route.

## Root Cause
The component was attempting to cast route parameters directly to integers:
```php
$this->manifest_id = request()->route('manifest') ? (int) request()->route('manifest') : null;
$this->package_id = request()->route('package') ? (int) request()->route('package') : null;
```

However, Laravel's route model binding was passing actual model instances instead of just IDs, causing the type conversion error.

## Solution
Updated the parameter handling to properly detect and handle both model instances and integer IDs:

```php
// Handle route model binding - extract IDs from model instances
$manifestParam = request()->route('manifest');
$this->manifest_id = $manifestParam instanceof Manifest ? $manifestParam->id : (int) $manifestParam;

$packageParam = request()->route('package');
$this->package_id = $packageParam instanceof Package ? $packageParam->id : (int) $packageParam;
```

## Benefits
- ✅ Handles Laravel route model binding correctly
- ✅ Maintains backward compatibility with integer IDs
- ✅ Prevents TypeError exceptions
- ✅ All existing tests continue to pass

## Files Modified
- `app/Http/Livewire/Manifests/Packages/EditManifestPackage.php`

## Tests Added
- `tests/Unit/EditManifestPackageModelBindingTest.php` - Verifies both model instance and integer ID handling

## Route Definition
The route continues to work as expected:
```php
Route::get('/{manifest}/packages/{package}/edit', EditManifestPackage::class)->name('packages.edit');
```

This fix ensures the component works correctly regardless of whether Laravel passes model instances or integer IDs through the route parameters.