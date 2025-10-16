# Manifest Filtering Guide

## Overview

The manifest filtering feature provides comprehensive capabilities to filter and analyze transactions based on their associated manifests. This feature is integrated throughout the ShipSharkLtd system and provides both UI components and backend query methods.

## Components Available

### 1. TransactionManagement Component
**File**: `app/Http/Livewire/TransactionManagement.php`

The main transaction management interface with full filtering capabilities including prominent manifest filtering.

**Features**:
- **Prominent Manifest Filter**: Highlighted with icon and visual indicators
- **Active Filter Display**: Shows currently applied filters with individual removal
- **Manifest Summary**: Displays financial summary when a manifest is selected
- **Real-time Updates**: Live filtering with pagination
- **Multiple Filter Types**: Search, customer, type, date range, and manifest

**Usage**:
```blade
<livewire:transaction-management />
```

### 2. ManifestTransactionFilter Component
**File**: `app/Http/Livewire/ManifestTransactionFilter.php`

A reusable component specifically for manifest-based filtering that can be embedded in other interfaces.

**Features**:
- **Compact Mode**: Space-efficient version for sidebars
- **Financial Summary**: Optional display of manifest financial data
- **Transaction Count**: Shows number of transactions per manifest
- **Event Emission**: Notifies other components when manifest selection changes

**Usage**:
```blade
<!-- Full version with summary -->
<livewire:manifest-transaction-filter />

<!-- Compact version -->
<livewire:manifest-transaction-filter :compact-mode="true" />

<!-- Without financial summary -->
<livewire:manifest-transaction-filter :show-summary="false" />
```

## Backend Query Methods

### CustomerTransaction Model Scopes

```php
// Filter transactions by specific manifest
CustomerTransaction::forManifest($manifestId)->get();

// Get all transactions linked to manifests
CustomerTransaction::linkedToManifests()->get();

// Filter by package
CustomerTransaction::forPackage($packageId)->get();

// Filter by distribution
CustomerTransaction::forPackageDistribution($distributionId)->get();
```

### Manifest Model Methods

```php
// Get all transactions for a manifest
$manifest->transactions;

// Get financial summary
$summary = $manifest->getFinancialSummary();
// Returns: total_owed, total_collected, total_write_off, outstanding_balance, collection_rate

// Get specific amounts
$manifest->getTotalCollectedAmount();
$manifest->getTotalChargedAmount();
$manifest->getTotalWriteOffAmount();
```

## UI Features

### Enhanced Filter Bar

The transaction management interface includes an enhanced filter bar with:

1. **Visual Manifest Filter**:
   - Icon indicator for easy identification
   - Highlighted when active (blue border and background)
   - Shows manifest type (Air/Sea) in dropdown
   - Displays selected manifest name below dropdown

2. **Active Filter Display**:
   - Shows all currently applied filters as removable tags
   - Color-coded by filter type (blue for manifest, green for customer, etc.)
   - Individual removal buttons (×) for each filter
   - Clear all filters button

3. **Results Counter**:
   - Shows total number of matching transactions
   - Updates in real-time as filters are applied

### Manifest Summary Panel

When a manifest is selected, a summary panel appears showing:

- **Manifest Information**: Name, type, reservation number
- **Financial Metrics**: Total owed, collected, outstanding, transaction count
- **Collection Rate**: Visual progress bar showing collection percentage
- **Quick Actions**: Clear filter button

## Integration Examples

### 1. Basic Transaction Filtering

```php
// In a controller
public function getTransactionsByManifest($manifestId)
{
    return CustomerTransaction::forManifest($manifestId)
        ->with(['user', 'manifest'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
}
```

### 2. Manifest Financial Dashboard

```php
// Get manifest with financial data
$manifest = Manifest::with('transactions')->find($manifestId);
$summary = $manifest->getFinancialSummary();

return view('manifest.dashboard', [
    'manifest' => $manifest,
    'summary' => $summary,
    'recent_transactions' => $manifest->transactions()->latest()->limit(10)->get(),
]);
```

### 3. Multi-Manifest Analysis

```php
// Compare multiple manifests
$manifests = Manifest::withCount('transactions')
    ->get()
    ->map(function ($manifest) {
        return [
            'manifest' => $manifest,
            'summary' => $manifest->getFinancialSummary(),
        ];
    });
```

## Advanced Filtering

### Combined Filters

The system supports combining multiple filters:

```php
$query = CustomerTransaction::query();

// Apply manifest filter
if ($manifestId) {
    $query->forManifest($manifestId);
}

// Apply customer filter
if ($customerId) {
    $query->where('user_id', $customerId);
}

// Apply date range
if ($dateFrom && $dateTo) {
    $query->whereBetween('created_at', [$dateFrom, $dateTo]);
}

// Apply transaction type
if ($type) {
    $query->where('type', $type);
}

$transactions = $query->with(['user', 'manifest'])->paginate(20);
```

### Search Integration

The search functionality includes manifest data:

```php
$query->where(function ($q) use ($search) {
    $q->where('description', 'like', '%' . $search . '%')
      ->orWhere('amount', 'like', '%' . $search . '%')
      ->orWhereHas('user', function ($userQuery) use ($search) {
          $userQuery->where('first_name', 'like', '%' . $search . '%')
                   ->orWhere('last_name', 'like', '%' . $search . '%');
      })
      ->orWhereHas('manifest', function ($manifestQuery) use ($search) {
          $manifestQuery->where('name', 'like', '%' . $search . '%')
                       ->orWhere('reservation_number', 'like', '%' . $search . '%');
      });
});
```

## Performance Considerations

### Database Optimization

1. **Indexes**: The optional migration adds optimized indexes:
   ```sql
   INDEX (manifest_id)
   INDEX (user_id, manifest_id)
   INDEX (manifest_id, type)
   ```

2. **Eager Loading**: Always load relationships to avoid N+1 queries:
   ```php
   CustomerTransaction::with(['user', 'manifest', 'createdBy'])
       ->forManifest($manifestId)
       ->get();
   ```

3. **Counting**: Use `withCount()` for efficient counting:
   ```php
   Manifest::withCount(['transactions', 'packages'])->get();
   ```

### Caching Strategies

For frequently accessed data, consider caching:

```php
// Cache manifest financial summaries
$summary = Cache::remember("manifest_summary_{$manifestId}", 3600, function () use ($manifest) {
    return $manifest->getFinancialSummary();
});

// Cache transaction counts
$count = Cache::remember("manifest_transactions_{$manifestId}", 1800, function () use ($manifestId) {
    return CustomerTransaction::forManifest($manifestId)->count();
});
```

## Event Handling

The ManifestTransactionFilter component emits events that other components can listen to:

```php
// In a Livewire component
protected $listeners = ['manifestFilterChanged' => 'handleManifestChange'];

public function handleManifestChange($manifestId)
{
    $this->selectedManifestId = $manifestId;
    $this->loadTransactions();
}
```

## Customization Options

### Styling

The components use Tailwind CSS classes that can be customized:

```blade
<!-- Custom styling for manifest filter -->
<div class="manifest-filter-custom">
    <livewire:manifest-transaction-filter 
        :compact-mode="true"
        class="custom-filter-style" 
    />
</div>
```

### Configuration

Component behavior can be configured via properties:

```php
// In the component
public $showSummary = true;        // Show/hide financial summary
public $compactMode = false;       // Compact or full layout
public $autoRefresh = false;       // Auto-refresh data
public $defaultManifest = null;    // Default selected manifest
```

## Best Practices

### 1. Performance
- Always use eager loading for relationships
- Implement caching for frequently accessed summaries
- Use pagination for large result sets
- Add appropriate database indexes

### 2. User Experience
- Provide clear visual feedback for active filters
- Show result counts and summaries
- Allow easy filter removal
- Implement real-time updates

### 3. Data Integrity
- Validate manifest IDs before filtering
- Handle cases where manifests are deleted
- Provide fallbacks for missing data
- Log filter usage for analytics

### 4. Security
- Authorize access to manifest data
- Validate user permissions for filtered results
- Sanitize search inputs
- Implement rate limiting for API endpoints

## Troubleshooting

### Common Issues

**Issue**: Manifest filter not showing results
- **Check**: Ensure transactions are properly linked to manifests
- **Solution**: Run the backfill command to link existing transactions

**Issue**: Performance problems with large datasets
- **Check**: Database indexes and query optimization
- **Solution**: Implement caching and pagination

**Issue**: Filter state not persisting
- **Check**: Query string configuration in Livewire components
- **Solution**: Verify `$queryString` property is properly configured

### Debugging

Enable query logging to debug performance:

```php
DB::enableQueryLog();
$transactions = CustomerTransaction::forManifest($manifestId)->get();
dd(DB::getQueryLog());
```

Monitor filter usage:

```php
Log::info('Manifest filter applied', [
    'manifest_id' => $manifestId,
    'user_id' => auth()->id(),
    'result_count' => $transactions->count(),
]);
```

This comprehensive filtering system provides powerful capabilities for analyzing and managing transactions based on their manifest associations, enabling better financial tracking and operational insights.