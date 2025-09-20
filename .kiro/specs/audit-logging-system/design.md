# Design Document

## Overview

The Audit and Logging System will provide comprehensive tracking and monitoring capabilities for the ShipSharkLtd application. The system will capture all critical user activities, system events, and data changes through a centralized audit framework. It will include an intuitive administrative interface for viewing, searching, filtering, and managing audit logs, with configurable retention policies and security monitoring features.

The design leverages Laravel's existing logging infrastructure while extending it with structured audit capabilities, database storage for searchable logs, and a dedicated administrative interface integrated into the existing sidebar navigation.

## Architecture

### Core Components

1. **Audit Event Capture Layer**
   - Model observers for automatic data change tracking
   - Middleware for HTTP request/response logging
   - Event listeners for authentication and authorization events
   - Service layer integration for business logic auditing

2. **Audit Storage Layer**
   - Dedicated `audit_logs` database table for structured storage
   - Optimized indexes for efficient querying and filtering
   - Configurable retention and archival policies

3. **Administrative Interface**
   - Livewire-based audit log management component
   - Advanced filtering and search capabilities
   - Export functionality for compliance reporting
   - Real-time log viewing with pagination

4. **Security Monitoring**
   - Suspicious activity detection algorithms
   - Automated alerting for security events
   - IP address and user agent tracking

## Components and Interfaces

### Database Schema

#### Audit Logs Table
```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(50) NOT NULL,
    auditable_type VARCHAR(255) NULL,
    auditable_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    url VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    additional_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_auditable (auditable_type, auditable_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### Audit Settings Table
```sql
CREATE TABLE audit_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Model Classes

#### AuditLog Model
- Eloquent model with relationships to User and polymorphic auditable models
- Scopes for filtering by event type, date range, user, and model type
- Accessor methods for formatted display of old/new values
- Static methods for creating audit entries

#### AuditSetting Model
- Configuration management for retention policies
- Alert thresholds and notification settings
- Export format preferences

### Service Classes

#### AuditService
- Central service for creating audit log entries
- Standardized methods for different event types
- Batch processing capabilities for bulk operations
- Integration with existing service layer

#### AuditRetentionService
- Automated cleanup of old audit logs based on policies
- Archival functionality for long-term storage
- Storage optimization and compression

#### SecurityMonitoringService
- Pattern detection for suspicious activities
- Alert generation and notification dispatch
- Risk scoring algorithms

### Observer Classes

#### Universal Audit Observer
- Generic observer that can be attached to any model
- Automatic detection of field changes with before/after values
- Configurable field exclusions (e.g., passwords, sensitive data)
- Integration with existing model observers

### Middleware

#### AuditMiddleware
- HTTP request/response logging
- Route and controller action tracking
- Performance metrics capture
- Error and exception logging

### Livewire Components

#### AuditLogManagement
- Main administrative interface component
- Advanced filtering and search functionality
- Real-time updates and pagination
- Export capabilities

#### AuditLogViewer
- Detailed view component for individual audit entries
- Diff visualization for data changes
- Related audit entries display

#### AuditSettings
- Configuration interface for retention policies
- Alert threshold management
- System health monitoring

## Data Models

### Event Types
- `authentication` - Login, logout, password changes
- `authorization` - Permission changes, role modifications
- `model_created` - New record creation
- `model_updated` - Record modifications
- `model_deleted` - Record deletions
- `model_restored` - Soft delete restorations
- `business_action` - Package consolidation, manifest operations
- `financial_transaction` - Payment processing, balance changes
- `system_event` - Backup operations, maintenance tasks
- `security_event` - Failed login attempts, unauthorized access

### Action Types
- `create`, `update`, `delete`, `restore`
- `login`, `logout`, `password_change`
- `role_change`, `permission_grant`, `permission_revoke`
- `consolidate`, `unconsolidate`, `manifest_lock`, `manifest_unlock`
- `payment_processed`, `balance_adjusted`, `fee_calculated`
- `backup_created`, `backup_restored`, `system_maintenance`
- `failed_login`, `unauthorized_access`, `suspicious_activity`

### Auditable Models
All critical models will implement auditing:
- User, Role, Profile
- Package, ConsolidatedPackage, Manifest
- CustomerTransaction, PackageDistribution
- Office, Address, Rate
- BroadcastMessage, Backup

## Error Handling

### Audit Failure Resilience
- Audit logging failures must not break application functionality
- Fallback to Laravel's standard logging when audit system is unavailable
- Queue-based processing for non-critical audit events
- Retry mechanisms for failed audit log creation

### Data Integrity
- Validation of audit data before storage
- Checksums for tamper detection
- Immutable audit records (no updates allowed)
- Backup and recovery procedures for audit data

### Performance Considerations
- Asynchronous processing for heavy audit operations
- Database connection pooling for high-volume logging
- Batch processing for bulk audit operations
- Caching for frequently accessed audit summaries

## Testing Strategy

### Unit Tests
- AuditService methods for all event types
- Model observer functionality
- Security monitoring algorithms
- Retention policy enforcement

### Feature Tests
- End-to-end audit trail creation
- Administrative interface functionality
- Export and reporting capabilities
- Security alert generation

### Integration Tests
- Audit system integration with existing observers
- Performance impact on normal operations
- Database transaction handling
- Queue processing reliability

### Browser Tests
- Audit log management interface
- Search and filtering functionality
- Export download processes
- Real-time updates and pagination

## Security Considerations

### Access Control
- Role-based access to audit logs (superadmin only)
- Audit log viewing permissions separate from modification rights
- IP address restrictions for sensitive audit operations
- Session timeout for audit management interfaces

### Data Protection
- Encryption of sensitive audit data at rest
- Secure transmission of audit logs
- PII masking in audit displays
- Compliance with data protection regulations

### Audit Trail Integrity
- Immutable audit records
- Digital signatures for critical audit events
- Tamper detection mechanisms
- Secure backup and archival procedures

## Performance Optimization

### Database Optimization
- Strategic indexing for common query patterns
- Partitioning for large audit log tables
- Archive tables for historical data
- Query optimization for complex filters

### Caching Strategy
- Redis caching for audit summaries
- Cached user activity statistics
- Precomputed security metrics
- Cache invalidation strategies

### Asynchronous Processing
- Queue-based audit log creation
- Background processing for heavy operations
- Batch processing for bulk audit events
- Rate limiting for high-volume scenarios

## Integration Points

### Existing System Integration
- Seamless integration with current model observers
- Extension of existing service layer patterns
- Compatibility with current authentication system
- Integration with existing notification system

### Administrative Interface Integration
- Addition to existing Administration menu in sidebar
- Consistent UI/UX with current admin interfaces
- Integration with existing role-based access control
- Responsive design matching current application theme

### Reporting Integration
- Export capabilities compatible with existing reporting
- Integration with backup system for audit data
- Compliance reporting templates
- Dashboard widgets for audit summaries