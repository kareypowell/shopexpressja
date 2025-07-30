# Sea Manifest Validation and Error Handling

This document outlines the comprehensive validation and error handling implemented for sea manifest features.

## Overview

The sea manifest enhancement includes robust validation and error handling across all components to ensure data integrity and provide user-friendly feedback.

## Validation Rules

### 1. Vessel Information Validation

**Components:** `Manifest`, `EditManifest`

**Required Fields for Sea Manifests:**
- `vessel_name`: Required, minimum 2 characters, maximum 255 characters
- `voyage_number`: Required, minimum 1 character, maximum 255 characters  
- `departure_port`: Required, minimum 2 characters, maximum 255 characters

**Optional Fields:**
- `arrival_port`: Optional, minimum 2 characters if provided, maximum 255 characters
- `estimated_arrival_date`: Optional, must be after shipment date if provided

**Custom Rule:** `ValidVesselInformation`
- Only validates vessel fields for sea manifests
- Ignores vessel validation for air manifests
- Provides specific error messages for each field

### 2. Container Dimensions Validation

**Components:** `ManifestPackage`, `EditManifestPackage`

**Required Fields for Sea Packages:**
- `length_inches`: Required, numeric, minimum 0.1, maximum 1000
- `width_inches`: Required, numeric, minimum 0.1, maximum 1000
- `height_inches`: Required, numeric, minimum 0.1, maximum 1000

**Custom Rule:** `ValidContainerDimensions`
- Validates positive numeric values within reasonable limits
- Only applies to sea packages
- Prevents unrealistic dimensions (negative or extremely large values)

**Additional Validation:**
- Cubic feet calculation validation (must be > 0 and < 10,000)
- Real-time calculation updates when dimensions change

### 3. Package Items Validation

**Components:** `ManifestPackage`, `EditManifestPackage`

**Required for Sea Packages:**
- `items`: Required array with minimum 1 item
- `items.*.description`: Required, minimum 2 characters, maximum 255 characters
- `items.*.quantity`: Required, integer, minimum 1, maximum 10,000
- `items.*.weight_per_item`: Optional, numeric, minimum 0, maximum 1000

**Custom Rule:** `ValidPackageItems`
- Ensures at least one item exists for sea packages
- Validates each item has required fields
- Checks quantity is positive integer
- Validates weight per item if provided

### 4. Container Type Validation

**Required for Sea Packages:**
- `container_type`: Required, must be one of: 'box', 'barrel', 'pallet'

## Error Handling

### 1. Sea Rate Calculation Errors

**Custom Exception:** `SeaRateNotFoundException`
- Thrown when no appropriate sea rate is found for given cubic feet
- Provides user-friendly error messages
- Includes specific cubic feet value in error message
- Suggests contacting support for assistance

**Fallback Logic:**
1. First tries exact cubic feet range match
2. Falls back to closest higher range
3. Falls back to highest available range
4. Throws exception if no sea rates exist

### 2. Validation Error Messages

**Comprehensive Custom Messages:**
- Field-specific error messages for all validation rules
- Context-aware messages (different for sea vs air manifests)
- User-friendly language avoiding technical jargon
- Specific guidance on acceptable values and formats

### 3. Component-Level Error Handling

**ManifestPackage & EditManifestPackage:**
- Validates all fields before processing
- Provides immediate feedback for invalid dimensions
- Handles rate calculation failures gracefully
- Continues package creation even if pricing fails (with warning)

**Manifest & EditManifest:**
- Conditional validation based on manifest type
- Clears irrelevant fields when switching types
- Validates date relationships (arrival after departure)

## Validation Flow

### Sea Package Creation Flow:
1. **Basic Field Validation** - Required fields, data types, formats
2. **Sea-Specific Validation** - Container type, dimensions, items
3. **Laravel Validation** - All standard validation rules applied
4. **Custom Rule Validation** - Custom validation rules executed
5. **Cubic Feet Validation** - Calculated value must be reasonable
6. **Rate Lookup** - Attempts to find appropriate sea rate
7. **Error Handling** - Graceful handling of rate calculation failures

### Sea Manifest Creation Flow:
1. **Basic Field Validation** - Name, dates, exchange rate
2. **Type-Specific Validation** - Vessel info for sea, flight info for air
3. **Custom Rule Validation** - Vessel information validation
4. **Date Validation** - Arrival date after departure date
5. **Data Persistence** - Save with appropriate fields based on type

## Testing Coverage

### Unit Tests:
- `ValidVesselInformation` rule testing
- `ValidContainerDimensions` rule testing  
- `ValidPackageItems` rule testing
- `SeaRateNotFoundException` exception testing

### Feature Tests:
- Vessel information validation scenarios
- Container dimension validation scenarios
- Package items validation scenarios
- Error message accuracy testing
- Type-specific validation (sea vs air)

### Integration Tests:
- End-to-end validation workflows
- Component interaction testing
- Error handling in real scenarios

## User Experience

### Error Feedback:
- **Immediate Validation** - Real-time feedback as user types
- **Clear Messages** - Specific, actionable error messages
- **Visual Indicators** - Form field highlighting for errors
- **Toast Notifications** - Success/error notifications for actions

### Progressive Enhancement:
- **Type Detection** - Automatic field showing/hiding based on manifest type
- **Real-time Calculations** - Cubic feet updates as dimensions change
- **Smart Defaults** - Reasonable default values where appropriate

## Security Considerations

### Input Validation:
- All user inputs validated server-side
- SQL injection prevention through Eloquent ORM
- XSS prevention through proper output escaping
- CSRF protection on all forms

### Data Integrity:
- Foreign key constraints in database
- Cascade deletion for related records
- Transaction handling for multi-step operations
- Validation at multiple layers (client, server, database)

## Performance Considerations

### Validation Efficiency:
- Custom rules only execute when needed (type-specific)
- Database queries optimized for rate lookups
- Caching of manifest type detection
- Minimal validation overhead for air manifests

### Error Handling Performance:
- Exception handling doesn't block normal operations
- Graceful degradation when rate calculation fails
- Logging for debugging without impacting user experience

## Maintenance and Extensibility

### Adding New Validation Rules:
1. Create custom rule class in `app/Rules/`
2. Add rule to appropriate component validation
3. Add custom error messages
4. Create unit tests for the rule
5. Update documentation

### Modifying Existing Validation:
1. Update validation rules in components
2. Update custom error messages
3. Update tests to match new requirements
4. Test backward compatibility

This comprehensive validation and error handling system ensures data integrity while providing an excellent user experience for sea manifest management.