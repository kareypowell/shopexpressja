# Design Document

## Overview

This design document outlines the enhancement of the Manifest Packages and Package Workflow pages to improve user experience through better content organization and more relevant summary information. The solution implements a tabbed interface to consolidate the Consolidated Packages and Individual Packages sections, reducing page length and improving navigation. Additionally, the summary section will be enhanced to display appropriate metrics based on manifest type - weight information for Air manifests and volume information for Sea manifests.

The enhancement builds upon the existing Laravel/Livewire architecture and utilizes DaisyUI components for consistent styling and responsive behavior. The solution maintains all existing functionality while improving the user interface organization and information display.

## Architecture

### Tabbed Interface System
The tabbed interface will be implemented using DaisyUI's tab components with Livewire for state management:
- **Tab Container**: DaisyUI tabs component with proper accessibility attributes
- **Tab Content Management**: Livewire components to handle content switching and state preservation
- **URL State Management**: Browser history integration for bookmarkable tab states
- **Responsive Design**: Mobile-first approach with touch-friendly tab navigation

### Summary Enhancement System
The summary section will be enhanced with conditional display logic:
- **Manifest Type Detection**: Service to determine Air vs Sea manifest type
- **Metric Calculation Service**: Separate services for weight and volume calculations
- **Unit Conversion Utilities**: Helper functions for weight (lbs/kg) and volume conversions
- **Dynamic Summary Component**: Livewire component that renders appropriate metrics

### State Management
The system will maintain state across tab switches and page refreshes:
- **Browser History Integration**: URL parameters to track active tabs
- **Session Storage**: Preserve selections, filters, and pagination within tabs
- **Livewire State Persistence**: Maintain component state during tab switches

## Components and Interfaces

### 1. Tabbed Interface Components

#### ManifestTabsContainer Component
- **File**: `app/Http/Livewire/Manifests/ManifestTabsContainer.php`
- **Purpose**: Main container component managing tab state and content
- **Methods**:
  - `mount(string $activeTab = 'consolidated')`: Initialize with default or URL-specified tab
  - `switchTab(string $tabName)`: Handle tab switching with state preservation
  - `updateUrl()`: Update browser URL to reflect current tab state
  - `preserveTabState()`: Maintain filters, selections, and pagination per tab

#### ConsolidatedPackagesTab Component
- **File**: `app/Http/Livewire/Manifests/ConsolidatedPackagesTab.php`
- **Purpose**: Handles consolidated packages view with all existing functionality
- **Features**:
  - Package grouping and consolidation logic
  - Bulk operations for consolidated packages
  - Filtering and search within consolidated view
  - Pagination specific to consolidated packages

#### IndividualPackagesTab Component
- **File**: `app/Http/Livewire/Manifests/IndividualPackagesTab.php`
- **Purpose**: Handles individual packages view with all existing functionality
- **Features**:
  - Individual package listing and management
  - Package-level operations and status updates
  - Filtering and search within individual view
  - Pagination specific to individual packages

### 2. Enhanced Summary Components

#### ManifestSummaryService
- **File**: `app/Services/ManifestSummaryService.php`
- **Purpose**: Calculate and format summary metrics based on manifest type
- **Methods**:
  - `calculateAirManifestSummary(Manifest $manifest): array`
  - `calculateSeaManifestSummary(Manifest $manifest): array`
  - `getManifestType(Manifest $manifest): string`
  - `formatWeightDisplay(float $weightLbs): array`
  - `formatVolumeDisplay(float $cubicFeet): string`

#### WeightCalculationService
- **File**: `app/Services/WeightCalculationService.php`
- **Purpose**: Handle weight calculations and conversions
- **Methods**:
  - `calculateTotalWeight(Collection $packages): float`
  - `convertLbsToKg(float $lbs): float`
  - `formatWeightUnits(float $lbs, float $kg): array`
  - `validateWeightData(Collection $packages): array`

#### VolumeCalculationService
- **File**: `app/Services/VolumeCalculationService.php`
- **Purpose**: Handle volume calculations for sea manifests
- **Methods**:
  - `calculateTotalVolume(Collection $packages): float`
  - `formatVolumeDisplay(float $cubicFeet): string`
  - `validateVolumeData(Collection $packages): array`
  - `estimateVolumeFromDimensions(Package $package): float`

#### EnhancedManifestSummary Component
- **File**: `app/Http/Livewire/Manifests/EnhancedManifestSummary.php`
- **Purpose**: Display appropriate summary metrics based on manifest type
- **Features**:
  - Automatic manifest type detection
  - Conditional metric display (weight for Air, volume for Sea)
  - Real-time updates when packages change
  - Data validation and incomplete data indicators

### 3. View Templates

#### Tabbed Interface Template
- **File**: `resources/views/livewire/manifests/manifest-tabs-container.blade.php`
- **Structure**:
```html
<div class="manifest-tabs-container">
    <!-- DaisyUI Tabs Navigation -->
    <div role="tablist" class="tabs tabs-lift tabs-lg w-full">
        <button role="tab" 
                class="tab {{ $activeTab === 'consolidated' ? 'tab-active' : '' }}"
                wire:click="switchTab('consolidated')"
                aria-label="Consolidated Packages">
            Consolidated Packages
        </button>
        <button role="tab" 
                class="tab {{ $activeTab === 'individual' ? 'tab-active' : '' }}"
                wire:click="switchTab('individual')"
                aria-label="Individual Packages">
            Individual Packages
        </button>
    </div>
    
    <!-- Tab Content -->
    <div class="tab-content-container mt-6">
        @if($activeTab === 'consolidated')
            <livewire:manifests.consolidated-packages-tab :manifest="$manifest" />
        @elseif($activeTab === 'individual')
            <livewire:manifests.individual-packages-tab :manifest="$manifest" />
        @endif
    </div>
</div>
```

#### Enhanced Summary Template
- **File**: `resources/views/livewire/manifests/enhanced-manifest-summary.blade.php`
- **Structure**:
```html
<div class="manifest-summary bg-base-100 p-6 rounded-lg shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Manifest Summary</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Package Count -->
        <div class="stat">
            <div class="stat-title">Total Packages</div>
            <div class="stat-value">{{ $summary['package_count'] }}</div>
        </div>
        
        <!-- Conditional Metrics Based on Manifest Type -->
        @if($manifestType === 'air')
            <!-- Weight Display for Air Manifests -->
            <div class="stat">
                <div class="stat-title">Total Weight</div>
                <div class="stat-value text-primary">
                    {{ $summary['weight']['lbs'] }} lbs
                </div>
                <div class="stat-desc">
                    ({{ $summary['weight']['kg'] }} kg)
                </div>
            </div>
        @elseif($manifestType === 'sea')
            <!-- Volume Display for Sea Manifests -->
            <div class="stat">
                <div class="stat-title">Total Volume</div>
                <div class="stat-value text-secondary">
                    {{ $summary['volume'] }} cubic feet
                </div>
            </div>
        @endif
        
        <!-- Total Value -->
        <div class="stat">
            <div class="stat-title">Total Value</div>
            <div class="stat-value">${{ number_format($summary['total_value'], 2) }}</div>
        </div>
    </div>
    
    <!-- Data Completeness Indicators -->
    @if($summary['incomplete_data'])
        <div class="alert alert-warning mt-4">
            <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <span>Some packages are missing {{ $manifestType === 'air' ? 'weight' : 'volume' }} information. Totals shown are for available data only.</span>
        </div>
    @endif
</div>
```

### 4. Data Models and Services

#### Manifest Model Enhancement
- **File**: `app/Models/Manifest.php`
- **New Methods**:
  - `getType(): string` - Determine if manifest is Air or Sea
  - `getTotalWeight(): float` - Calculate total weight for Air manifests
  - `getTotalVolume(): float` - Calculate total volume for Sea manifests
  - `hasCompleteWeightData(): bool` - Check if all packages have weight data
  - `hasCompleteVolumeData(): bool` - Check if all packages have volume data

#### Package Model Enhancement
- **File**: `app/Models/Package.php`
- **New Methods**:
  - `getWeightInLbs(): float` - Get package weight in pounds
  - `getWeightInKg(): float` - Get package weight in kilograms
  - `getVolumeInCubicFeet(): float` - Get package volume in cubic feet
  - `hasWeightData(): bool` - Check if package has weight information
  - `hasVolumeData(): bool` - Check if package has volume information

## Error Handling

### Tab State Management
- Handle invalid tab names by defaulting to 'consolidated'
- Preserve user selections when tab switching fails
- Graceful degradation if JavaScript is disabled
- Error recovery for corrupted session state

### Summary Calculation Errors
- Handle missing weight/volume data gracefully
- Display appropriate warnings for incomplete data
- Fallback calculations when primary data is unavailable
- Validation of calculation results before display

### Responsive Design Failures
- Ensure tabs remain functional on all screen sizes
- Fallback layouts for very small screens
- Touch interaction handling for mobile devices
- Keyboard navigation support for accessibility

## Testing Strategy

### Unit Tests
- **Tab State Management Tests**: Validate tab switching logic and state preservation
- **Summary Calculation Tests**: Test weight and volume calculation accuracy
- **Service Layer Tests**: Validate all calculation services with various data scenarios
- **Model Enhancement Tests**: Test new model methods and data validation

### Feature Tests
- **Tabbed Interface Tests**: End-to-end tab functionality testing
- **Summary Display Tests**: Verify correct metrics display based on manifest type
- **State Persistence Tests**: Ensure tab state survives page refreshes and navigation
- **Data Validation Tests**: Test handling of incomplete or invalid data

### Browser Tests
- **Responsive Design Tests**: Verify tab functionality across different screen sizes
- **Accessibility Tests**: Ensure keyboard navigation and screen reader compatibility
- **Touch Interaction Tests**: Validate mobile touch interactions
- **URL State Tests**: Test bookmarking and sharing of tab-specific URLs

## Database Considerations

### Existing Schema Compatibility
The enhancement will work with existing database schema without requiring migrations:
- Manifest type determination based on existing fields
- Weight and volume data from existing package fields
- No new database tables or columns required

### Performance Optimization
- Add database indexes for frequently queried fields
- Optimize weight and volume calculation queries
- Implement caching for summary calculations
- Use eager loading to reduce N+1 query problems

### Data Validation
- Ensure weight data is in consistent units (pounds)
- Validate volume data format and units
- Handle null or missing measurement data gracefully
- Implement data integrity checks for calculations

## Security Considerations

### Input Validation
- Validate tab names to prevent XSS attacks
- Sanitize all user inputs in tab content
- Implement CSRF protection for tab switching
- Validate calculation inputs to prevent manipulation

### Access Control
- Maintain existing permission checks for tab content
- Ensure tab switching doesn't bypass authorization
- Validate user access to manifest data
- Log tab access for audit purposes

### Data Integrity
- Validate calculation results before display
- Prevent manipulation of summary calculations
- Ensure consistent data across tab switches
- Implement checksums for critical calculations

## Performance Considerations

### Client-Side Performance
- Lazy load tab content to improve initial page load
- Implement efficient DOM updates for tab switching
- Use CSS transitions for smooth tab animations
- Optimize JavaScript bundle size for tab functionality

### Server-Side Performance
- Cache summary calculations for frequently accessed manifests
- Optimize database queries for weight and volume calculations
- Implement pagination within tabs to handle large datasets
- Use background jobs for complex calculations when needed

### Memory Management
- Efficient state management to prevent memory leaks
- Proper cleanup of event listeners and observers
- Optimize Livewire component lifecycle
- Monitor memory usage during tab operations

## Accessibility Features

### Keyboard Navigation
- Full keyboard support for tab navigation using arrow keys
- Proper focus management when switching tabs
- Skip links for screen reader users
- Consistent tab order throughout the interface

### Screen Reader Support
- Proper ARIA labels and roles for tab components
- Announcements when tab content changes
- Descriptive labels for summary metrics
- Alternative text for visual indicators

### Visual Accessibility
- High contrast colors for tab states
- Clear visual indicators for active tabs
- Scalable text and interface elements
- Support for reduced motion preferences

## Mobile Responsiveness

### Touch Interface
- Touch-friendly tab buttons with adequate spacing
- Swipe gestures for tab navigation (optional enhancement)
- Proper touch feedback for tab interactions
- Optimized layout for portrait and landscape orientations

### Small Screen Adaptations
- Horizontal scrolling for tabs when necessary
- Stacked layout for very small screens
- Condensed summary display for mobile
- Optimized content density for mobile viewing

### Performance on Mobile
- Optimized asset loading for mobile connections
- Efficient touch event handling
- Minimal JavaScript execution for tab operations
- Progressive enhancement for slower devices