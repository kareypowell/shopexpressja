# Project Structure & Architecture

## Laravel Directory Structure

### Core Application (`app/`)
- **Models/**: Eloquent models with comprehensive relationships and scopes
  - Key models: `User`, `Package`, `ConsolidatedPackage`, `Manifest`, `Profile`
  - Rich model methods for financial calculations and statistics
  - Extensive query scopes for performance optimization
- **Http/**: Controllers, Middleware, and Livewire components
  - **Livewire/**: Full-stack components for interactive UI
  - **Controllers/**: Traditional controllers for API endpoints
  - **Middleware/**: Custom middleware for authentication and authorization
- **Services/**: Business logic and complex operations
  - Service classes for consolidation, distribution, notifications
  - Cache services for performance optimization
  - Query optimization services
- **Policies/**: Authorization logic for different user roles
- **Observers/**: Model event handlers for cache invalidation
- **Notifications/**: Email and system notifications
- **Mail/**: Mailable classes for customer communications
- **Rules/**: Custom validation rules for business logic
- **Enums/**: Type-safe enumerations (e.g., `PackageStatus`)

### Database (`database/`)
- **migrations/**: Comprehensive schema with performance indexes
- **factories/**: Model factories for testing and seeding
- **seeders/**: Data seeders including test data generators

### Frontend (`resources/`)
- **views/**: Blade templates organized by feature
  - **livewire/**: Component-specific templates
  - **layouts/**: Shared layout templates
  - **components/**: Reusable UI components
- **js/**: JavaScript assets including Alpine.js components
- **css/**: Sass stylesheets with Tailwind integration

### Testing (`tests/`)
- **Feature/**: Integration tests for complete workflows
- **Unit/**: Isolated unit tests for individual components
- **Browser/**: Dusk browser tests for UI interactions

## Architecture Patterns

### Service Layer Pattern
- Business logic encapsulated in service classes
- Services handle complex operations like package consolidation
- Clear separation between controllers and business logic

### Repository Pattern (Implicit)
- Eloquent models act as repositories with rich query scopes
- Optimized queries through dedicated scopes and methods
- Caching layer integrated at the service level

### Observer Pattern
- Model observers for automatic cache invalidation
- Event-driven architecture for notifications
- Audit trail logging through observers

### Policy-Based Authorization
- Comprehensive policy classes for fine-grained permissions
- Role-based access control (customer, admin, superadmin)
- Gate-based authorization in services

## Naming Conventions

### Models
- Singular PascalCase: `Package`, `ConsolidatedPackage`
- Rich relationship methods with descriptive names
- Accessor/mutator methods follow Laravel conventions

### Livewire Components
- Organized by feature in subdirectories
- PascalCase class names: `ManifestTabsContainer`
- Kebab-case view names: `manifest-tabs-container`

### Services
- Descriptive names ending in "Service": `PackageConsolidationService`
- Methods use camelCase with clear action verbs
- Return arrays with consistent structure (success/error patterns)

### Database
- Snake_case table and column names
- Descriptive foreign key names: `consolidated_package_id`
- Comprehensive indexing strategy for performance

## Key Architectural Decisions

### Livewire-First Frontend
- Minimal JavaScript with Alpine.js for interactions
- Server-side rendering with reactive components
- Real-time updates without complex JavaScript frameworks

### Comprehensive Caching Strategy
- Multi-level caching (customer stats, financial data, package metrics)
- Automatic cache invalidation through model observers
- Performance-critical queries cached with appropriate TTL

### Service-Oriented Business Logic
- Complex operations handled by dedicated service classes
- Database transactions managed at service level
- Consistent error handling and logging patterns

### Extensive Testing Coverage
- Feature tests for complete user workflows
- Unit tests for individual components and calculations
- Browser tests for critical UI interactions

## File Organization Principles

1. **Feature-based grouping**: Related functionality grouped together
2. **Clear separation of concerns**: Models, services, and views have distinct responsibilities
3. **Consistent naming**: Predictable file and class naming across the application
4. **Performance-first**: Optimized queries and caching built into the architecture
5. **Testability**: Structure supports comprehensive testing at all levels