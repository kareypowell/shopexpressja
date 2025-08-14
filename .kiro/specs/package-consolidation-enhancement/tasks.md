# Implementation Plan

- [x] 1. Create database foundation for package consolidation
  - Create migration for consolidated_packages table with all required fields
  - Create migration to add consolidation fields to packages table
  - Create migration for consolidation_history table for audit trail
  - _Requirements: 1.5, 1.6, 9.1, 9.2_

- [x] 2. Implement ConsolidatedPackage model with core functionality
  - Create ConsolidatedPackage model with fillable fields and relationships
  - Implement calculated properties for totals (weight, quantity, costs)
  - Add methods for generating consolidated tracking numbers
  - Add status management methods for synchronizing with individual packages
  - Write unit tests for ConsolidatedPackage model methods and relationships
  - _Requirements: 1.1, 1.4, 3.1, 3.2, 3.3, 4.1, 4.2_

- [x] 3. Enhance Package model with consolidation support
  - Add consolidation-related fields to Package model fillable array
  - Implement consolidation relationship methods (belongsTo ConsolidatedPackage)
  - Add consolidation status check methods (isConsolidated, canBeConsolidated)
  - Add scopes for filtering consolidated vs individual packages
  - Write unit tests for enhanced Package model consolidation methods
  - _Requirements: 1.5, 1.6, 3.1, 3.4, 3.5_

- [x] 4. Create PackageConsolidationService with core operations
  - Implement consolidatePackages method with validation and transaction handling
  - Implement unconsolidatePackages method with proper data restoration
  - Add validation methods for consolidation eligibility (same customer, compatible status)
  - Implement consolidated totals calculation methods
  - Add consolidated tracking number generation logic
  - Write unit tests for PackageConsolidationService methods
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 4.1, 4.2, 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 5. Enhance PackageDistributionService for consolidated packages
  - Add distributeConsolidatedPackages method for handling consolidated package distribution
  - Modify existing distributePackages method to detect and handle consolidated packages
  - Update calculatePackageTotals method to work with consolidated package totals
  - Implement consolidated receipt generation with itemized individual package details
  - Write unit tests for enhanced PackageDistributionService with consolidated packages
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 6. Enhance PackageNotificationService for consolidated notifications
  - Implement sendConsolidatedStatusNotification method for consolidated package status changes
  - Add sendConsolidationNotification method for when packages are consolidated
  - Add sendUnconsolidationNotification method for when packages are separated
  - Modify existing sendStatusNotification to check for consolidation and send appropriate notifications
  - Create consolidated notification templates with individual package details
  - Write unit tests for enhanced PackageNotificationService consolidated notification methods
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 7. Create ConsolidationToggle Livewire component
  - Create ConsolidationToggle component with toggle state management
  - Implement toggleConsolidationMode method with session persistence
  - Create Blade template for consolidation toggle UI with clear visual indicators
  - Add JavaScript for smooth toggle interactions and state updates
  - Write feature tests for ConsolidationToggle component behavior
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [ ] 8. Enhance Package Livewire component with consolidation features
  - Add consolidation mode properties and selected packages tracking
  - Implement package selection methods for consolidation (togglePackageSelection)
  - Add consolidateSelectedPackages method with validation and service integration
  - Implement unconsolidatePackage method for separating consolidated packages
  - Add consolidated view toggle for switching between individual and consolidated display
  - Update render method to handle both individual and consolidated package display
  - Write feature tests for enhanced Package component consolidation functionality
  - _Requirements: 1.1, 1.2, 1.6, 2.1, 2.2, 5.1, 5.2, 5.3, 5.4, 5.5, 10.4_

- [ ] 9. Update Package Blade templates for consolidation UI
  - Modify package listing template to show consolidation toggle and selection checkboxes
  - Create consolidated package display template with expandable individual package details
  - Add consolidation action buttons (consolidate selected, unconsolidate)
  - Implement visual indicators for consolidated packages vs individual packages
  - Add consolidated package summary cards with totals and individual package counts
  - Write browser tests for consolidation UI interactions and visual elements
  - _Requirements: 1.6, 2.5, 3.5, 3.6, 10.4_

- [ ] 10. Enhance PackageDistribution component for consolidated packages
  - Update package selection logic to handle consolidated packages in distribution
  - Modify distribution calculation methods to work with consolidated package totals
  - Update distribution UI to show consolidated package details with individual breakdowns
  - Implement consolidated receipt preview with itemized individual package information
  - Write feature tests for consolidated package distribution workflow
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 11. Create consolidated package notification templates
  - Create email templates for consolidated package status notifications
  - Design consolidated package consolidation notification template
  - Create unconsolidation notification template
  - Implement consolidated receipt email template with individual package itemization
  - Add consolidated package ready for pickup notification template
  - Write tests for consolidated notification template rendering and content
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 12. Implement consolidated package search and filtering
  - Update package search functionality to search within consolidated packages by individual tracking numbers
  - Enhance package filtering to include consolidated packages based on combined criteria
  - Add search result highlighting for consolidated packages showing which individual packages matched
  - Implement consolidated package filtering in admin dashboard and customer views
  - Write feature tests for consolidated package search and filtering functionality
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 13. Integrate consolidation with manifest workflows
  - Update manifest package addition to handle consolidated packages as single entries
  - Modify manifest totals calculation to use consolidated package totals
  - Add consolidated package status update functionality in manifest workflows
  - Implement expandable consolidated package details in manifest views
  - Ensure consolidated package status synchronization during manifest processing
  - Write integration tests for consolidated packages in manifest workflows
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 14. Add consolidation audit logging and history tracking
  - Implement consolidation action logging in PackageConsolidationService
  - Create consolidation history tracking for all consolidation/unconsolidation events
  - Add consolidated package status change logging with individual package details
  - Implement consolidation history display in package management interface
  - Create audit trail export functionality for consolidated package operations
  - Write tests for consolidation audit logging and history tracking
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 15. Create database seeders and test data for consolidation
  - Create ConsolidatedPackageFactory for generating test consolidated packages
  - Implement database seeder for consolidated package test scenarios
  - Create test data scenarios covering various consolidation states and workflows
  - Add consolidated package test data to existing test suites
  - Write comprehensive integration tests using seeded consolidated package data
  - _Requirements: 9.4, 9.5_

- [ ] 16. Implement consolidation performance optimizations
  - Add database indexes for consolidated package queries (consolidated_package_id, customer_id, is_active)
  - Implement eager loading for consolidated packages with their individual packages
  - Add caching for consolidated package totals and customer consolidation lists
  - Optimize consolidated package search queries with proper indexing
  - Write performance tests for consolidation operations with large datasets
  - _Requirements: 1.4, 4.1, 4.2, 10.1, 10.2_

- [ ] 17. Add consolidation permissions and access control
  - Implement consolidation permission checks in PackageConsolidationService
  - Add role-based access control for consolidation operations
  - Ensure customer data isolation for consolidation operations
  - Add consolidation action authorization in Livewire components
  - Write security tests for consolidation access control and permissions
  - _Requirements: 1.1, 1.2, 5.1, 5.2, 9.1_

- [ ] 18. Create comprehensive consolidation feature tests
  - Write end-to-end tests for complete consolidation workflow (create, distribute, notify)
  - Create integration tests for consolidation with existing package management features
  - Implement browser tests for consolidation UI interactions and user experience
  - Add performance tests for consolidation operations under load
  - Create regression tests to ensure consolidation doesn't break existing functionality
  - _Requirements: All requirements - comprehensive testing_