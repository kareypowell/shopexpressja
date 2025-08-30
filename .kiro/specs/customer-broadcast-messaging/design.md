# Design Document

## Overview

The Customer Broadcast Messaging feature will enable administrators to compose and send email communications to customers through an intuitive web interface. The system will leverage Laravel's existing email infrastructure, queue system, and Livewire components to provide a seamless user experience with WYSIWYG editing capabilities, recipient selection, message scheduling, and delivery tracking.

## Architecture

### High-Level Architecture
The feature follows Laravel's MVC pattern with Livewire components for reactive UI interactions:

- **Models**: BroadcastMessage, BroadcastRecipient, BroadcastDelivery
- **Controllers**: BroadcastMessageController for route handling
- **Livewire Components**: BroadcastComposer for the main interface
- **Mail Classes**: CustomerBroadcastEmail for email delivery
- **Jobs**: SendBroadcastMessageJob for queued processing
- **Services**: BroadcastMessageService for business logic

### Database Design
```sql
-- Broadcast messages table
broadcast_messages:
- id (primary key)
- subject (string)
- content (text, HTML content)
- sender_id (foreign key to users)
- recipient_type (enum: 'all', 'selected')
- recipient_count (integer)
- status (enum: 'draft', 'scheduled', 'sending', 'sent', 'failed')
- scheduled_at (timestamp, nullable)
- sent_at (timestamp, nullable)
- created_at, updated_at

-- Broadcast recipients table (for selected recipients)
broadcast_recipients:
- id (primary key)
- broadcast_message_id (foreign key)
- customer_id (foreign key to users)
- created_at, updated_at

-- Broadcast deliveries table (tracking individual deliveries)
broadcast_deliveries:
- id (primary key)
- broadcast_message_id (foreign key)
- customer_id (foreign key to users)
- email (string)
- status (enum: 'pending', 'sent', 'failed', 'bounced')
- sent_at (timestamp, nullable)
- failed_at (timestamp, nullable)
- error_message (text, nullable)
- created_at, updated_at
```

## Components and Interfaces

### 1. Livewire Components

#### BroadcastComposer Component
**Location**: `app/Http/Livewire/Admin/BroadcastComposer.php`

**Properties**:
- `$subject` - Email subject line
- `$content` - HTML email content
- `$recipientType` - 'all' or 'selected'
- `$selectedCustomers` - Array of selected customer IDs
- `$customerSearch` - Search term for customer selection
- `$availableCustomers` - Paginated customer list
- `$isScheduled` - Boolean for scheduling
- `$scheduledDate` - Date for scheduled sending
- `$scheduledTime` - Time for scheduled sending
- `$showPreview` - Boolean for preview modal
- `$isDraft` - Boolean indicating if saving as draft

**Methods**:
- `mount()` - Initialize component
- `searchCustomers()` - Filter customers based on search
- `toggleCustomer($customerId)` - Add/remove customer from selection
- `selectAllCustomers()` - Select all filtered customers
- `clearSelection()` - Clear all selected customers
- `saveDraft()` - Save message as draft
- `showPreview()` - Display preview modal
- `sendNow()` - Send message immediately
- `scheduleMessage()` - Schedule message for later
- `validateScheduleTime()` - Ensure scheduled time is in future

#### BroadcastHistory Component
**Location**: `app/Http/Livewire/Admin/BroadcastHistory.php`

**Properties**:
- `$broadcasts` - Paginated broadcast messages
- `$selectedBroadcast` - Currently selected broadcast for details
- `$showDetails` - Boolean for details modal

**Methods**:
- `mount()` - Initialize component
- `showBroadcastDetails($broadcastId)` - Display broadcast details
- `cancelScheduledBroadcast($broadcastId)` - Cancel scheduled message
- `resendBroadcast($broadcastId)` - Resend failed broadcast

### 2. Models

#### BroadcastMessage Model
**Location**: `app/Models/BroadcastMessage.php`

**Relationships**:
- `belongsTo(User::class, 'sender_id')` - Message sender
- `hasMany(BroadcastRecipient::class)` - Selected recipients
- `hasMany(BroadcastDelivery::class)` - Delivery tracking

**Scopes**:
- `scopeDrafts()` - Filter draft messages
- `scopeScheduled()` - Filter scheduled messages
- `scopeSent()` - Filter sent messages

**Methods**:
- `getRecipientEmails()` - Get all recipient email addresses
- `markAsSending()` - Update status to sending
- `markAsSent()` - Update status to sent and set sent_at
- `markAsFailed()` - Update status to failed

#### BroadcastRecipient Model
**Location**: `app/Models/BroadcastRecipient.php`

**Relationships**:
- `belongsTo(BroadcastMessage::class)`
- `belongsTo(User::class, 'customer_id')`

#### BroadcastDelivery Model
**Location**: `app/Models/BroadcastDelivery.php`

**Relationships**:
- `belongsTo(BroadcastMessage::class)`
- `belongsTo(User::class, 'customer_id')`

**Methods**:
- `markAsSent()` - Update delivery status to sent
- `markAsFailed($errorMessage)` - Update delivery status to failed

### 3. Services

#### BroadcastMessageService
**Location**: `app/Services/BroadcastMessageService.php`

**Methods**:
- `createBroadcast($data)` - Create new broadcast message
- `saveDraft($data)` - Save message as draft
- `scheduleBroadcast($broadcastId, $scheduledAt)` - Schedule message
- `sendBroadcast($broadcastId)` - Queue broadcast for sending
- `getRecipients($broadcastMessage)` - Get recipient list based on type
- `createDeliveryRecords($broadcastMessage, $recipients)` - Create delivery tracking
- `processScheduledBroadcasts()` - Process due scheduled messages

### 4. Jobs

#### SendBroadcastMessageJob
**Location**: `app/Jobs/SendBroadcastMessageJob.php`

**Properties**:
- `$broadcastMessage` - The broadcast message to send
- `$tries = 3` - Number of retry attempts
- `$timeout = 300` - Job timeout in seconds

**Methods**:
- `handle()` - Process the broadcast sending
- `failed()` - Handle job failure

#### SendBroadcastEmailJob
**Location**: `app/Jobs/SendBroadcastEmailJob.php`

**Properties**:
- `$broadcastDelivery` - Individual delivery record
- `$tries = 3`
- `$timeout = 60`

**Methods**:
- `handle()` - Send individual email
- `failed()` - Handle individual email failure

### 5. Mail Classes

#### CustomerBroadcastEmail
**Location**: `app/Mail/CustomerBroadcastEmail.php`

**Properties**:
- `$broadcastMessage` - The broadcast message
- `$customer` - The recipient customer

**Methods**:
- `build()` - Build the email message
- `failed()` - Handle email failure

## Data Models

### BroadcastMessage Entity
```php
class BroadcastMessage extends Model
{
    protected $fillable = [
        'subject',
        'content',
        'sender_id',
        'recipient_type',
        'recipient_count',
        'status',
        'scheduled_at',
        'sent_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    const RECIPIENT_TYPE_ALL = 'all';
    const RECIPIENT_TYPE_SELECTED = 'selected';
}
```

### Customer Selection Logic
The system will use the existing User model's customer scopes:
- `User::customers()` - Get all customers
- `User::activeCustomers()` - Get active customers only
- Customer search using existing `scopeSearch()` method

## Error Handling

### Validation Rules
- Subject: required, max 255 characters
- Content: required, min 10 characters
- Recipients: required when recipient_type is 'selected'
- Scheduled time: must be in future when scheduling
- Customer selection: must exist and be active customers

### Error Scenarios
1. **Email Delivery Failures**: Individual email failures are logged in broadcast_deliveries table
2. **Queue Failures**: Job failures are retried up to 3 times with exponential backoff
3. **Validation Errors**: Real-time validation feedback in Livewire component
4. **Permission Errors**: Admin role verification before access
5. **Scheduled Message Errors**: Failed scheduled messages are marked as failed with error logging

### Logging Strategy
- Broadcast creation/sending events logged at INFO level
- Individual email failures logged at WARNING level
- System errors logged at ERROR level
- All logs include broadcast_message_id for traceability

## Testing Strategy

### Unit Tests
- **Models**: Test relationships, scopes, and methods
- **Services**: Test business logic and edge cases
- **Jobs**: Test email sending and failure handling
- **Mail Classes**: Test email content and structure

### Feature Tests
- **Broadcast Creation**: Test full broadcast creation workflow
- **Customer Selection**: Test recipient selection logic
- **Email Sending**: Test queued email processing
- **Scheduling**: Test scheduled message processing
- **Permission Handling**: Test admin access controls

### Integration Tests
- **End-to-End Workflow**: Test complete broadcast sending process
- **Email Delivery**: Test actual email delivery (using testing mail driver)
- **Queue Processing**: Test job queue processing
- **Database Integrity**: Test data consistency across related tables

### Browser Tests
- **UI Interactions**: Test Livewire component interactions
- **WYSIWYG Editor**: Test rich text editing functionality
- **Customer Selection**: Test search and selection interface
- **Preview Functionality**: Test message preview modal
- **Responsive Design**: Test mobile and desktop layouts

## Security Considerations

### Authentication & Authorization
- Admin role required for all broadcast functionality
- CSRF protection on all forms
- Rate limiting on broadcast sending to prevent abuse

### Input Sanitization
- HTML content sanitized to prevent XSS attacks
- Email addresses validated before sending
- File upload restrictions if attachments are added later

### Data Protection
- Customer email addresses encrypted in delivery logs
- Broadcast content stored securely
- Audit trail for all broadcast activities

## Performance Considerations

### Database Optimization
- Indexes on frequently queried columns (status, scheduled_at, sender_id)
- Pagination for large customer lists
- Efficient queries using existing User model scopes

### Queue Management
- Separate queue for broadcast emails to prevent blocking other jobs
- Batch processing for large recipient lists
- Queue monitoring and failure handling

### Caching Strategy
- Cache customer counts for recipient selection
- Cache frequently accessed broadcast statistics
- Use existing cache infrastructure

### Memory Management
- Process large broadcasts in chunks to prevent memory exhaustion
- Lazy loading for customer relationships
- Efficient pagination for large datasets