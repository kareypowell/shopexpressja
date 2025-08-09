# Package Distribution Email System

## Overview

When packages are distributed to customers, the system automatically sends a comprehensive receipt email with both a detailed breakdown within the email and a PDF receipt attachment.

## Features

### 📧 **Automatic Email Sending**
- Emails are sent automatically when packages are distributed
- Uses Laravel's queue system for reliable delivery
- Includes both HTML email content and PDF attachment

### 📄 **Detailed Email Content**
- Professional HTML template with company branding
- Complete package breakdown with individual costs
- Payment status and outstanding balance information
- Receipt information and distribution details

### 📎 **PDF Receipt Attachment**
- Generated PDF receipt attached to email
- Named as "Receipt-{receipt_number}.pdf"
- Contains same detailed information as email

### 💰 **Cost Breakdown**
- Individual package costs (freight, customs, storage, delivery)
- Category totals (freight total, customs total, etc.)
- Grand total and amount collected
- Outstanding balance calculation and highlighting

## Architecture

### Services

1. **PackageDistributionService** (`app/Services/PackageDistributionService.php`)
   - Handles the complete distribution process
   - Generates PDF receipt
   - Triggers email sending
   - Updates distribution records

2. **DistributionEmailService** (`app/Services/DistributionEmailService.php`)
   - Manages email sending logic
   - Validates email addresses and receipt files
   - Updates email tracking fields
   - Provides retry functionality

3. **ReceiptGeneratorService** (`app/Services/ReceiptGeneratorService.php`)
   - Generates PDF receipts
   - Stores receipts in configured storage

### Mail Class

**PackageReceiptEmail** (`app/Mail/PackageReceiptEmail.php`)
- Implements `ShouldQueue` for background processing
- Calculates totals and formats data
- Attaches PDF receipt
- Uses professional email template

### Email Template

**Receipt Email Template** (`resources/views/emails/packages/receipt.blade.php`)
- Responsive HTML design
- Professional styling with company branding
- Detailed package table with cost breakdown
- Payment status badges
- Outstanding balance warnings

## Database Schema

### PackageDistribution Table
```sql
- email_sent (boolean) - Tracks if email was sent
- email_sent_at (timestamp) - When email was sent
- receipt_path (string) - Path to PDF receipt file
```

## Usage

### Automatic Distribution
```php
$distributionService = app(PackageDistributionService::class);
$result = $distributionService->distributePackages(
    $packageIds,
    $amountCollected,
    $user
);
// Email is sent automatically
```

### Manual Email Sending
```php
$emailService = app(DistributionEmailService::class);
$result = $emailService->sendReceiptEmail($distribution, $customer);
```

### Retry Failed Emails
```php
$emailService = app(DistributionEmailService::class);
$result = $emailService->retryFailedReceipt($distributionId);
```

## Email Content Structure

### Header Section
- Company logo and branding
- "Package Delivery Receipt" title

### Receipt Information
- Receipt number
- Distribution date
- Customer name
- Payment status badge

### Package Details Table
| Tracking Number | Description | Freight | Customs | Storage | Delivery | Total |
|----------------|-------------|---------|---------|---------|----------|-------|
| TRK123456      | Electronics | $50.00  | $10.00  | $5.00   | $0.00    | $65.00|

### Totals Section
- Freight Total: $100.00
- Customs Total: $20.00
- Storage Total: $10.00
- Delivery Total: $0.00
- **Grand Total: $130.00**
- **Amount Collected: $130.00**
- Outstanding Balance: $0.00 (if any)

### Outstanding Balance Warning
If there's an outstanding balance, a highlighted warning section appears with:
- Amount owed
- Instructions to contact for payment

### Footer
- Thank you message
- Contact information for questions

## Configuration

### Queue Configuration
- Emails are queued on the 'emails' queue
- 5-second delay ensures database transaction completion
- Implements `ShouldQueue` for background processing

### Storage Configuration
- PDF receipts stored using Laravel's storage system
- Configurable storage disk
- Automatic file existence validation

### Email Configuration
- Uses Laravel's mail configuration
- Supports all Laravel mail drivers
- Email validation before sending

## Error Handling

### Graceful Failures
- Distribution continues even if email fails
- Comprehensive error logging
- Email status tracking in database

### Validation
- Email address validation
- Receipt file existence checks
- Customer record validation

### Logging
- Successful email sending logged
- Failed attempts logged with details
- Distribution process logging

## Testing

### Unit Tests
- `PackageDistributionEmailTest` - Comprehensive test suite
- Tests email sending, content validation, and error handling
- Mocks file system and email sending for reliable testing

### Test Coverage
- ✅ Automatic email sending during distribution
- ✅ Email content and breakdown validation
- ✅ Database tracking of email status
- ✅ Independent email service functionality
- ✅ Error handling and validation

## Monitoring

### Email Status Tracking
- `email_sent` boolean flag
- `email_sent_at` timestamp
- Failed email logging

### Performance Monitoring
- Queue processing metrics
- Email delivery success rates
- PDF generation performance

## Troubleshooting

### Common Issues

1. **Email not sent**
   - Check queue processing: `php artisan queue:work`
   - Verify email configuration
   - Check receipt file exists on public disk

2. **PDF not attached / Receipt file not found**
   - **Fixed Issue**: Receipt files are stored on `public` disk (`storage/app/public/receipts/`)
   - Email service now correctly checks `Storage::disk('public')->exists()`
   - PDF attachment uses `Storage::disk('public')->path()`

3. **Email content missing data**
   - Check distribution items are properly loaded
   - Verify package relationships
   - Validate totals calculation

### Storage Configuration

**Important**: Receipt files are stored on the `public` disk:
- **File Location**: `storage/app/public/receipts/`
- **Receipt Generator**: Uses `Storage::disk('public')->put()`
- **Email Service**: Uses `Storage::disk('public')->exists()`
- **PDF Attachment**: Uses `Storage::disk('public')->path()`

### Retry Failed Emails

Use the built-in Artisan command to retry failed distribution emails:

```bash
# Retry specific distribution by ID
php artisan distribution:retry-emails --id=5

# Retry all failed emails
php artisan distribution:retry-emails --failed

# Retry all emails (with confirmation)
php artisan distribution:retry-emails --all
```

### Debug Commands
```bash
# Check queue status
php artisan queue:work --verbose

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Check if receipt files exist
ls -la storage/app/public/receipts/

# Retry specific failed distribution email
php artisan distribution:retry-emails --id=DISTRIBUTION_ID
```

## Security Considerations

- Email addresses validated before sending
- PDF files stored securely
- Customer data protected in email content
- Queue jobs processed securely

## Future Enhancements

- Email delivery status tracking
- Customer email preferences
- Multiple language support
- Email template customization
- Delivery confirmation tracking