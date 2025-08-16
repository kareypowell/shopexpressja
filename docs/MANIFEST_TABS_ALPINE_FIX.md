# Manifest Tabs Alpine.js Initialization Fix

## Problem

The manifest tabs interface was experiencing an issue where dropdown actions (like "Toggle Details", "Update Fees", "Unconsolidate") were not working on initial page load. These actions would only become functional after switching between tabs.

## Root Cause

The issue was caused by the tab container conditionally rendering Livewire components using Blade `@if` statements:

```php
@if($activeTab === 'consolidated')
    @livewire('manifests.consolidated-packages-tab', ...)
@elseif($activeTab === 'individual')
    @livewire('manifests.individual-packages-tab', ...)
@endif
```

This approach meant that:
1. Only the active tab's component was rendered in the DOM on initial load
2. Alpine.js components (`x-data="{ open: false }"`) in inactive tabs were not initialized
3. When users switched tabs, the newly rendered components would get their Alpine.js initialized
4. But on initial load, only one set of components was functional

## Solution

Changed the tab container to render both components but control visibility with CSS classes instead:

```php
<!-- Consolidated Packages Tab Content -->
<div class="consolidated-packages-content {{ $activeTab !== 'consolidated' ? 'hidden' : '' }}"
     id="tabpanel-consolidated"
     role="tabpanel"
     aria-labelledby="tab-consolidated"
     aria-label="Consolidated packages view"
     aria-hidden="{{ $activeTab !== 'consolidated' ? 'true' : 'false' }}">
    @livewire('manifests.consolidated-packages-tab', ['manifest' => $manifest], key('consolidated-'.$manifest->id))
</div>

<!-- Individual Packages Tab Content -->
<div class="individual-packages-content {{ $activeTab !== 'individual' ? 'hidden' : '' }}"
     id="tabpanel-individual"
     role="tabpanel"
     aria-labelledby="tab-individual"
     aria-label="Individual packages view"
     aria-hidden="{{ $activeTab !== 'individual' ? 'true' : 'false' }}">
    @livewire('manifests.individual-packages-tab', ['manifest' => $manifest], key('individual-'.$manifest->id))
</div>
```

## Additional Improvements

1. **JavaScript Tab Visibility Management**: Added `updateTabVisibility()` method to handle showing/hiding tabs dynamically
2. **Alpine.js Re-initialization**: Added hooks to ensure Alpine.js components are properly initialized after Livewire updates
3. **Accessibility**: Added proper `aria-hidden` attributes for screen readers
4. **CSS Transitions**: Added smooth transitions between tab switches

## Benefits

- ✅ Dropdown actions work immediately on page load
- ✅ Both tab components are fully functional from the start
- ✅ Maintains all existing functionality
- ✅ Improves accessibility with proper ARIA attributes
- ✅ Better user experience with immediate interactivity

## Files Modified

- `resources/views/livewire/manifests/manifest-tabs-container.blade.php`
- `tests/Feature/ManifestTabsContainerIntegrationTest.php` (test fixes)
- `tests/Browser/ManifestTabsInitialLoadTest.php` (new browser test)

## Testing

Added browser tests to verify that dropdown functionality works on initial page load and after tab switches.