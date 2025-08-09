# Package Email Notifications

This document describes the automated email notification system for package status transitions.

## Overview

The system automatically sends email notifications to customers whenever a package status changes. This ensures customers are always informed about their package's progress through the shipping workflow.

## Status Transitions and Notifications

| Status | Email Template | Cost Information Included | Manual Update Allowed |
|--------|---------------|---------------------------|----------------------|
| `pending` | No email sent | N/A | ✅ Yes |
| `processing` | `package-processing.blade.php` | No | ✅ Yes |
| `shipped` | `package-shipped.blade.php` | No | ✅ Yes |
| `customs` | `package-customs.blade.php` | No | ✅ Yes |
| `ready` | `package-ready.blade.php` | **Yes** - Full cost breakdown | ✅ Yes |
| `delivered` | `package-delivered.blade.php` | No | ❌ **Distribution Only** |
| `delayed` | `package-delayed.blade.php` | No | ✅ Yes |

### Special Distribution Email

When packages are distributed (marked as delivered through the distribution process), customers receive a **comprehensive receipt email** with:
- **PDF Receipt Attachment** - Complete receipt as downloadable PDF
- **Detailed Cost Breakdown** - Individual package costs and totals within email
- **Payment Information** - Amount collected and outstanding balance
- **Professional Formatting** - Branded HTML template with responsive design

See [Package Distribution Emails Documentation](package-distribution-emails.md) for complete details.

### Important: Delivered Status Restriction

The `delivered` status can **only** be set through the proper distribution process. This ensures:
- Proper payment collection and validation
- Receipt generation and record keeping
- Audit trail for package handover
- Customer notification with delivery confirmation

Manual status updates to `delivered` are blocked in both the UI and backend validation.

## Cost Information

When a package status is updated to `ready`, the email includes:
- Freight Price
- Customs Duty (if applicable)
- Storage Fee (if applicable)
- Delivery Fee (if applicable)
- **Total Amount Due**

This helps customers prepare for pickup by knowing exactly how much they need to pay.

## Architecture

### Services

1. **PackageNotificationService** (`app/Services/PackageNotificationService.php`)
   - Handles sending notifications based on package status
   - Maps statuses to appropriate notification classes
   - Provides bulk notification functionality

2. **PackageStatusService** (`app/Services/PackageStatusService.php`)
   - Automatically triggers email notifications when status is updated
   - Integrates with PackageNotificationService

### Notification Classes

Located in `app/Notifications/`:
- `PackageProcessingNotification.php`
- `PackageShippedNotification.php`
- `PackageCustomsNotification.php`
- `PackageReadyNotification.php` (includes package cost data)
- `PackageDeliveredNotification.php`
- `PackageDelayedNotification.php`

### Email Templates

Located in `resources/views/emails/packages/`:
- `package-processing.blade.php`
- `package-shipped.blade.php`
- `package-customs.blade.php`
- `package-ready.blade.php` (includes cost breakdown)
- `package-delivered.blade.php`
- `package-delayed.blade.php`

## Usage

### Automatic Notifications

Notifications are sent automatically when using the `PackageStatusService`:

```php
$packageStatusService = app(PackageStatusService::class);
$result = $packageStatusService->updateStatus(
    $package,
    PackageStatus::READY(),
    auth()->user(),
    'Package ready for pickup'
);
// Email notification is sent automatically
```

### Manual Notifications

You can also send notifications manually:

```php
$notificationService = app(PackageNotificationService::class);
$result = $notificationService->sendStatusNotification($package, PackageStatus::READY());
```

### Distribution-Only Status Updates

For the `delivered` status, use the special distribution method:

```php
$packageStatusService = app(PackageStatusService::class);
$result = $packageStatusService->markAsDeliveredThroughDistribution(
    $package,
    $user,
    'Package delivered through distribution process'
);
```

### Bulk Notifications

For bulk operations:

```php
$packages = Package::whereIn('id', $packageIds)->get();
$results = $notificationService->sendBulkStatusNotifications(
    $packages->toArray(), 
    PackageStatus::READY()
);
```

## User Interface

### Confirmation Messages

When updating package statuses through the admin interface:
- Confirmation dialogs inform users that email notifications will be sent
- Success messages confirm that notifications were sent to customers

### Bulk Operations

Both the ManifestPackage component and ManifestPackagesTable support bulk status updates with automatic email notifications.

### Status Update Restrictions

- The `delivered` status is **not available** in manual status update dropdowns
- Attempting to manually set a package to `delivered` will show an error message
- Users are directed to use the distribution process for marking packages as delivered

## Error Handling

- Failed notifications are logged but don't prevent status updates
- Packages without associated users are handled gracefully
- Detailed logging helps with troubleshooting

## Testing

Tests are available in `tests/Unit/PackageNotificationServiceTest.php` to verify:
- Notifications are sent for appropriate statuses
- Cost information is included for ready status
- Error cases are handled properly
- No notifications are sent for pending status

## Configuration

Email notifications use Laravel's built-in notification system and can be configured through:
- Mail configuration in `config/mail.php`
- Queue configuration for background processing
- MailerSend integration for email delivery