# Implementation Plan

- [x] 1. Create database migrations for sea manifest enhancements
  - Create migration to add vessel information fields to manifests table
  - Create migration to add container and dimensional fields to packages table
  - Create migration to create package_items table for multiple items per container
  - Create migration to modify rates table to support cubic feet ranges for sea rates
  - _Requirements: 1.1, 1.2, 2.1, 3.1, 4.1, 6.1_

- [x] 2. Update Package model with container and dimensional functionality
  - Add fillable fields for container_type, dimensions, and cubic_feet to Package model
  - Implement calculateCubicFeet() method in Package model
  - Add isSeaPackage() method to determine if package belongs to sea manifest
  - Create relationship method for package items
  - Add cubic_feet cast to decimal with 3 precision
  - _Requirements: 2.2, 4.2, 4.4_

- [x] 3. Create PackageItem model for managing multiple items per container
  - Create PackageItem model with fillable fields for description, quantity, weight_per_item
  - Add relationship method to Package model
  - Implement getTotalWeightAttribute() accessor for calculated total weight
  - Add proper casting for quantity and weight_per_item fields
  - _Requirements: 3.2, 3.4, 3.5_

- [x] 4. Extend Manifest model with vessel information support
  - Add vessel-related fillable fields to Manifest model
  - Implement isSeaManifest() method to check manifest type
  - Create getTransportInfoAttribute() accessor for type-specific transport details
  - Update model to handle conditional field requirements based on type
  - _Requirements: 1.1, 1.4, 7.1, 7.2_

- [x] 5. Update Rate model to support cubic feet-based sea rates
  - Add fillable fields for min_cubic_feet and max_cubic_feet to Rate model
  - Create scopeForSeaShipment() query scope for cubic feet range lookup
  - Create scopeForAirShipment() query scope for weight-based lookup
  - Update existing rate seeder to include sample sea rates with cubic feet ranges
  - _Requirements: 6.2, 6.3, 6.4, 5.2_

- [x] 6. Create SeaRateCalculator service for cubic feet-based pricing
  - Create SeaRateCalculator service class in app/Services directory
  - Implement calculateFreightPrice() method for sea packages using cubic feet
  - Add error handling for missing rates and fallback logic
  - Include exchange rate multiplication in price calculation
  - Write unit tests for SeaRateCalculator with various cubic feet scenarios
  - _Requirements: 5.1, 5.3, 5.4_

- [x] 7. Enhance ManifestPackage Livewire component for sea functionality
  - Add container_type, dimensions, and items properties to ManifestPackage component
  - Implement calculateCubicFeet() method with real-time calculation
  - Add addItem() and removeItem() methods for managing package items
  - Create isSeaManifest() method to determine manifest type
  - Update validation rules to include sea-specific fields conditionally
  - _Requirements: 2.1, 2.2, 3.1, 3.6, 4.1, 4.3_

- [x] 8. Update ManifestPackage store() method for sea packages
  - Modify store() method to handle container type and dimensions for sea packages
  - Add logic to save package items when creating sea packages
  - Update freight price calculation to use SeaRateCalculator for sea packages
  - Maintain existing air package functionality unchanged
  - Add proper validation for sea-specific fields
  - _Requirements: 2.4, 3.4, 4.4, 5.1, 5.5_

- [x] 9. Create enhanced package creation view for sea manifests
  - Update manifest-package create view to show container type selection for sea manifests
  - Add dimensional input fields (length, width, height) with real-time cubic feet display
  - Create dynamic items management interface with add/remove functionality
  - Implement conditional field visibility based on manifest type
  - Add JavaScript for real-time cubic feet calculation
  - _Requirements: 2.1, 2.5, 3.1, 3.6, 4.1, 4.3, 7.4_

- [x] 10. Enhance Manifest Livewire component with vessel information
  - Add vessel-related properties to Manifest component (vessel_name, voyage_number, etc.)
  - Update validation rules to conditionally require vessel or flight information
  - Modify store() method to save vessel information for sea manifests
  - Add resetInputFields() updates to include new vessel fields
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 11. Update manifest creation view with conditional vessel/flight fields
  - Modify manifest create view to show vessel fields when type is 'sea'
  - Show flight fields when type is 'air' (existing functionality)
  - Add JavaScript to toggle field visibility based on selected manifest type
  - Update field labels and placeholders to be type-appropriate
  - _Requirements: 1.1, 7.1, 7.2, 7.3_

- [x] 12. Enhance EditManifest component for vessel information
  - Add vessel-related properties to EditManifest component
  - Update mount() method to load vessel information for sea manifests
  - Modify validation rules to handle both vessel and flight information conditionally
  - Update update() method to save vessel information changes
  - _Requirements: 1.4, 1.5_

- [x] 13. Update manifest edit view with vessel information support
  - Modify edit-manifest view to display vessel fields for sea manifests
  - Maintain flight field display for air manifests
  - Add conditional rendering logic based on manifest type
  - Ensure proper form field population from existing data
  - _Requirements: 1.4, 1.5, 7.1, 7.2_

- [x] 14. Create EditManifestPackage component enhancements for sea packages
  - Add container and dimensional properties to EditManifestPackage component
  - Implement items management in edit mode (load, add, remove, modify)
  - Update mount() method to load existing package items and container information
  - Modify update() method to handle container type, dimensions, and items changes
  - Add real-time cubic feet recalculation on dimension changes
  - _Requirements: 2.5, 3.6, 4.5_

- [x] 15. Update package edit view for sea package features
  - Modify edit-manifest-package view to show container type and dimensions for sea packages
  - Add items management interface in edit mode
  - Display current cubic feet and allow dimension modifications
  - Maintain air package edit functionality unchanged
  - _Requirements: 2.5, 3.6, 4.5, 7.4_

- [x] 16. Create database seeders for sea rates and sample data
  - Update RatesTableSeeder to include cubic feet-based sea rates
  - Create sample sea rates with various cubic feet ranges and pricing
  - Add sample sea manifest data to ManifestsTableSeeder
  - Ensure sea rates cover common cubic feet ranges for testing
  - _Requirements: 6.2, 6.3, 6.4, 6.5_

- [x] 17. Update package display views to show sea-specific information
  - Modify package listing views to display container type for sea packages
  - Show cubic feet and dimensions in package details
  - Display package items list for sea packages
  - Add conditional rendering based on package manifest type
  - _Requirements: 2.4, 3.5, 4.5, 7.4_

- [x] 18. Create comprehensive test suite for sea manifest functionality
  - Write unit tests for Package model cubic feet calculations
  - Create tests for PackageItem model relationships and calculations
  - Test SeaRateCalculator service with various scenarios
  - Write feature tests for sea manifest creation workflow
  - Test package creation with container types and items
  - Create tests for pricing calculations and rate lookups
  - _Requirements: All requirements validation through testing_

- [x] 19. Update freight price calculation in existing ManifestPackage component
  - Modify calculateFreightPrice() method to detect manifest type
  - Use SeaRateCalculator for sea packages and existing logic for air packages
  - Update rate lookup to use appropriate rate type (cubic feet vs weight)
  - Ensure backward compatibility with existing air package pricing
  - _Requirements: 5.1, 5.3, 7.5_

- [x] 20. Add validation and error handling for sea manifest features
  - Implement comprehensive validation rules for vessel information
  - Add validation for container dimensions and positive values
  - Create validation for package items (minimum one item required)
  - Add error handling for missing sea rates
  - Implement user-friendly error messages for all validation scenarios
  - _Requirements: 1.2, 2.2, 3.2, 4.1, 5.4_