# Requirements Document

## Introduction

This feature enhances the existing manifest system to provide specialized functionality for sea shipments. Currently, the system treats air and sea manifests identically, using flight-specific terminology and lacking container-specific features. The enhancement will add vessel information for sea manifests, introduce container types (box, barrel, pallet), enable multiple items per container, capture dimensional data for cubic feet calculations, and implement cubic feet-based pricing for sea shipments.

## Requirements

### Requirement 1

**User Story:** As a shipping administrator, I want to capture vessel information when creating sea manifests, so that I can properly track and manage sea shipments with accurate vessel details.

#### Acceptance Criteria

1. WHEN creating a new manifest AND selecting "sea" as the type THEN the system SHALL display vessel-specific fields instead of flight fields
2. WHEN creating a sea manifest THEN the system SHALL require vessel name, voyage number, and departure port fields
3. WHEN creating a sea manifest THEN the system SHALL allow optional fields for arrival port and estimated arrival date
4. WHEN editing an existing sea manifest THEN the system SHALL display and allow modification of vessel information
5. WHEN viewing a sea manifest THEN the system SHALL display vessel information instead of flight information

### Requirement 2

**User Story:** As a shipping administrator, I want to specify container types when adding packages to sea manifests, so that I can accurately categorize and price different shipping containers.

#### Acceptance Criteria

1. WHEN adding a package to a sea manifest THEN the system SHALL provide container type options: box, barrel, and pallet
2. WHEN selecting a container type THEN the system SHALL be required and cannot be left empty
3. WHEN a container type is selected THEN the system SHALL store this information with the package record
4. WHEN viewing package details THEN the system SHALL display the selected container type
5. WHEN editing a package in a sea manifest THEN the system SHALL allow modification of the container type

### Requirement 3

**User Story:** As a shipping administrator, I want to add multiple items within a single container, so that I can accurately represent the contents of boxes, barrels, and pallets.

#### Acceptance Criteria

1. WHEN adding a package to a sea manifest THEN the system SHALL allow adding multiple items to the container
2. WHEN adding items THEN the system SHALL require item description and quantity for each item
3. WHEN adding items THEN the system SHALL allow optional weight per item
4. WHEN saving a package THEN the system SHALL store all items associated with that container
5. WHEN viewing package details THEN the system SHALL display all items within the container
6. WHEN editing a package THEN the system SHALL allow adding, removing, and modifying items

### Requirement 4

**User Story:** As a shipping administrator, I want to capture container dimensions, so that I can calculate cubic feet for accurate sea shipping pricing.

#### Acceptance Criteria

1. WHEN adding a package to a sea manifest THEN the system SHALL require length, width, and height dimensions in inches
2. WHEN dimensions are entered THEN the system SHALL automatically calculate and display cubic feet (length × width × height ÷ 1728)
3. WHEN dimensions are modified THEN the system SHALL recalculate cubic feet in real-time
4. WHEN saving a package THEN the system SHALL store both dimensions and calculated cubic feet
5. WHEN viewing package details THEN the system SHALL display dimensions and calculated cubic feet

### Requirement 5

**User Story:** As a shipping administrator, I want sea shipments to be priced per cubic feet, so that pricing accurately reflects the space-based cost structure of sea freight.

#### Acceptance Criteria

1. WHEN calculating freight price for sea packages THEN the system SHALL use cubic feet instead of weight for rate lookup
2. WHEN a sea rate table exists THEN the system SHALL look up rates based on cubic feet ranges
3. WHEN calculating sea freight price THEN the system SHALL multiply the rate by cubic feet and exchange rate
4. WHEN no matching rate is found THEN the system SHALL use a default rate or display an error message
5. WHEN viewing package pricing THEN the system SHALL show the cubic feet-based calculation breakdown

### Requirement 6

**User Story:** As a shipping administrator, I want to manage sea-specific rates, so that I can set appropriate pricing for cubic feet-based sea shipments.

#### Acceptance Criteria

1. WHEN managing rates THEN the system SHALL support both weight-based (air) and cubic feet-based (sea) rate types
2. WHEN creating sea rates THEN the system SHALL allow setting rates per cubic feet range
3. WHEN creating sea rates THEN the system SHALL require minimum and maximum cubic feet values
4. WHEN creating sea rates THEN the system SHALL require price per cubic feet and processing fee
5. WHEN viewing rates THEN the system SHALL clearly distinguish between air (weight-based) and sea (cubic feet-based) rates

### Requirement 7

**User Story:** As a system user, I want the interface to adapt based on manifest type, so that I see relevant fields and terminology for air versus sea shipments.

#### Acceptance Criteria

1. WHEN viewing an air manifest THEN the system SHALL display flight-related terminology and fields
2. WHEN viewing a sea manifest THEN the system SHALL display vessel-related terminology and fields
3. WHEN switching between manifest types THEN the system SHALL dynamically update field labels and options
4. WHEN adding packages THEN the system SHALL show container types only for sea manifests
5. WHEN calculating pricing THEN the system SHALL use weight-based calculation for air and cubic feet-based for sea