# Implementation Plan

- [x] 1. Set up controllers and routes
  - Create OfficeController and AddressController with resource methods
  - Define routes in web.php for both office and address management
  - Implement authentication middleware and basic authorization
  - _Requirements: 3.1, 3.2_

- [x] 2. Create authorization policies
- [x] 2.1 Implement OfficePolicy with access control methods
  - Write OfficePolicy class with viewAny, view, create, update, delete methods
  - Implement admin/superadmin authorization logic
  - Add relationship dependency checks for delete operations
  - _Requirements: 4.1, 4.3_

- [x] 2.2 Implement AddressPolicy with access control methods
  - Write AddressPolicy class with viewAny, view, create, update, delete methods
  - Implement admin/superadmin authorization logic for all operations
  - _Requirements: 4.1, 4.3_

- [x] 3. Enhance existing models with required functionality
- [x] 3.1 Add search scopes and relationship counts to Office model
  - Implement search scope for name and address fields
  - Add computed attributes for manifest, package, and profile counts
  - Write unit tests for new model functionality
  - _Requirements: 5.1, 6.1_

- [x] 3.2 Add search scopes and primary address logic to Address model
  - Implement search scope across all address fields
  - Add boot method to ensure single primary address constraint
  - Add proper casting for is_primary boolean field
  - Write unit tests for address model enhancements
  - _Requirements: 5.2, 2.6_

- [ ] 4. Create Office management Livewire components
- [ ] 4.1 Implement OfficeManagement component for listing and search
  - Create Livewire component with pagination, search, and delete functionality
  - Implement search filtering with real-time updates
  - Add delete confirmation modal with relationship validation
  - Write component tests for search and delete operations
  - _Requirements: 1.1, 1.6, 1.7, 5.1_

- [ ] 4.2 Implement OfficeForm component for create and edit operations
  - Create Livewire component for office creation and editing
  - Implement form validation and submission handling
  - Add success/error message handling and redirects
  - Write component tests for form validation and submission
  - _Requirements: 1.2, 1.3, 1.5, 4.1, 4.5_

- [ ] 5. Create Address management Livewire components
- [ ] 5.1 Implement AddressManagement component for listing and search
  - Create Livewire component with pagination, search, and primary address indicators
  - Implement comprehensive search across all address fields
  - Add delete confirmation modal functionality
  - Write component tests for search and delete operations
  - _Requirements: 2.1, 2.7, 5.2_

- [ ] 5.2 Implement AddressForm component for create and edit operations
  - Create Livewire component for address creation and editing
  - Implement comprehensive address validation
  - Add primary address toggle functionality with constraint enforcement
  - Write component tests for form validation and primary address logic
  - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.6, 4.2, 4.5_

- [ ] 6. Create view templates for office management
- [ ] 6.1 Create office index view with search and table layout
  - Build Blade template for office listing page
  - Implement responsive table with search bar and pagination
  - Add delete confirmation modal with relationship warnings
  - Style with Tailwind CSS matching existing admin pages
  - _Requirements: 1.1, 1.6, 1.7, 6.1_

- [ ] 6.2 Create office create and edit form views
  - Build Blade templates for office creation and editing forms
  - Implement form layouts with validation error display
  - Add breadcrumb navigation and consistent styling
  - Include success/error flash message display
  - _Requirements: 1.2, 1.3, 1.5, 4.1, 4.5_

- [ ] 6.3 Create office show view with relationship details
  - Build Blade template for office detail page
  - Display office information with associated record counts
  - Add links to view related manifests, packages, and profiles
  - Implement consistent styling and navigation
  - _Requirements: 1.4, 6.1, 6.5_

- [ ] 7. Create view templates for address management
- [ ] 7.1 Create address index view with search and table layout
  - Build Blade template for address listing page
  - Implement responsive table with comprehensive search functionality
  - Add primary address indicators and delete confirmation modals
  - Style with Tailwind CSS matching existing admin pages
  - _Requirements: 2.1, 2.7, 5.2_

- [ ] 7.2 Create address create and edit form views
  - Build Blade templates for address creation and editing forms
  - Implement comprehensive address form layouts with validation
  - Add primary address checkbox with clear labeling
  - Include success/error flash message display
  - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.6, 4.2, 4.5_

- [ ] 7.3 Create address show view with complete address details
  - Build Blade template for address detail page
  - Display complete address information including primary status
  - Add usage information display if applicable
  - Implement consistent styling and navigation
  - _Requirements: 2.4, 6.2, 6.4_

- [ ] 8. Update sidebar navigation with working links
  - Modify sidebar-nav.blade.php to include proper route links for offices and addresses
  - Implement active state highlighting for office and address sections
  - Ensure consistent styling with existing navigation items
  - Test navigation functionality and active state behavior
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 9. Write comprehensive tests for office management
- [ ] 9.1 Create unit tests for Office model enhancements
  - Write tests for search scope functionality
  - Test relationship count attributes
  - Verify model validation and business logic
  - _Requirements: 5.1, 6.1_

- [ ] 9.2 Create feature tests for office CRUD operations
  - Write tests for complete office creation, reading, updating, deletion workflows
  - Test authorization and access control
  - Verify search and filtering functionality
  - Test relationship constraint validation on deletion
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 4.1, 4.3, 5.1_

- [ ] 10. Write comprehensive tests for address management
- [ ] 10.1 Create unit tests for Address model enhancements
  - Write tests for search scope across all fields
  - Test primary address constraint enforcement
  - Verify model validation and casting
  - _Requirements: 5.2, 2.6_

- [ ] 10.2 Create feature tests for address CRUD operations
  - Write tests for complete address creation, reading, updating, deletion workflows
  - Test authorization and access control
  - Verify search functionality across all address fields
  - Test primary address toggle and constraint enforcement
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 4.2, 4.5, 5.2_

- [ ] 11. Integration testing and final validation
  - Test complete user workflows from navigation to CRUD operations
  - Verify all error handling and validation scenarios
  - Test responsive design and accessibility features
  - Validate integration with existing authentication and authorization systems
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 4.1, 4.2, 4.3, 4.4, 4.5_