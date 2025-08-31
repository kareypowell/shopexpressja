# Broadcast Scheduled Processing

This document explains the scheduled broadcast processing system that automatically sends broadcast messages at their scheduled times.

## Overview

The system includes:
- A console command (`broadcast:process-scheduled`) that processes due scheduled broadcasts
- Automatic scheduling via Laravel's task scheduler (runs every 5 minutes)
- Comprehensive logging and monitoring
- Error handling and recovery

## Console Command

### Basic Usage

```bash
# Process all due scheduled broadcasts
php artisan broadcast:process-scheduled

# Dry run mode (shows what would be processed without sending)
php artisan broadcast:process-scheduled --dry-run

# Limit the number of broadcasts processed in one run
php artisan broadcast:process-scheduled --limit=10

# Combine options
php artisan broadcast:process-scheduled --dry-run --limit=5
```

### Command Options

- `--dry-run`: Shows what broadcasts would be processed without actually sending them
- `--limit=N`: Maximum number of broadcasts to process in one run (default: 50)

### Output

The command provides detailed output including:
- Number of broadcasts processed
- Number of failed broadcasts
- Execution time
- Error details (if any)

Example output:
```
Starting scheduled broadcast processing...
✅ Processing completed successfully in 1.23s
📊 Processed: 3 broadcasts
⚠️  Failed: 1 broadcasts
Errors encountered:
  • Broadcast 5: No recipients found for broadcast
```

## Automatic Scheduling

The command is automatically scheduled to run every 5 minutes via Laravel's task scheduler. The configuration includes:

- **Frequency**: Every 5 minutes (`*/5 * * * *`)
- **Overlap Protection**: Prevents multiple instances from running simultaneously
- **Background Execution**: Runs in the background to avoid blocking
- **Timeout**: 10-minute timeout to prevent stuck processes
- **Logging**: Success and failure events are logged

### Scheduler Configuration

The scheduler is configured in `app/Console/Kernel.php`:

```php
$schedule->command('broadcast:process-scheduled')
    ->everyFiveMinutes()
    ->withoutOverlapping(10) // 10-minute timeout
    ->runInBackground()
    ->onSuccess(function () {
        \Log::info('Scheduled broadcast processing completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Scheduled broadcast processing failed');
    });
```

## Processing Logic

When a scheduled broadcast is processed:

1. **Status Update**: Broadcast status changes from `scheduled` to `sending`
2. **Recipient Resolution**: System determines recipients based on broadcast type
3. **Validation**: Ensures recipients exist before proceeding
4. **Delivery Records**: Creates individual delivery tracking records
5. **Email Processing**: Sends personalized emails to each recipient
6. **Final Status**: Updates broadcast status to `sent` upon completion

## Error Handling

The system handles various error scenarios:

### Individual Broadcast Failures
- Failed broadcasts are marked with `failed` status
- Error messages are logged for debugging
- Other broadcasts in the same run continue processing

### Common Failure Scenarios
- **No Recipients**: Broadcasts with no valid recipients fail gracefully
- **Email Delivery Issues**: Individual email failures are tracked in delivery records
- **Database Errors**: Connection or constraint issues are logged and handled

### Recovery
- Failed broadcasts remain in `failed` status and won't be retried automatically
- Administrators can manually review and resend failed broadcasts
- Delivery records provide detailed failure information

## Monitoring and Logging

### Log Levels
- **INFO**: Successful processing, individual email sends
- **WARNING**: Individual email failures
- **ERROR**: System errors, broadcast processing failures

### Log Entries
All log entries include relevant context:
- Broadcast ID and details
- Customer information (for individual emails)
- Error messages and stack traces
- Execution timing and statistics

### Monitoring Points
- Scheduled job execution (every 5 minutes)
- Processing success/failure rates
- Individual email delivery rates
- System performance and timing

## Performance Considerations

### Batch Processing
- Default limit of 50 broadcasts per run prevents memory issues
- Large recipient lists are processed efficiently
- Background execution prevents blocking other operations

### Database Optimization
- Efficient queries for due broadcasts
- Bulk operations for delivery record creation
- Proper indexing on scheduled_at and status columns

### Memory Management
- Processes broadcasts individually to prevent memory exhaustion
- Lazy loading for recipient relationships
- Garbage collection between broadcasts

## Troubleshooting

### Common Issues

1. **No Broadcasts Processing**
   - Check if scheduler is running (`php artisan schedule:work`)
   - Verify broadcast status is `scheduled`
   - Confirm scheduled_at time is in the past

2. **High Failure Rates**
   - Check email configuration
   - Verify recipient email addresses
   - Review error logs for specific issues

3. **Performance Issues**
   - Reduce batch size with `--limit` option
   - Check database performance
   - Monitor memory usage

### Debugging Commands

```bash
# Check what would be processed
php artisan broadcast:process-scheduled --dry-run

# Process with detailed output
php artisan broadcast:process-scheduled -v

# Check scheduler status
php artisan schedule:list

# View recent logs
tail -f storage/logs/laravel.log | grep broadcast
```

## Security Considerations

- Command requires proper Laravel environment setup
- Email content is sanitized before sending
- Recipient validation prevents unauthorized access
- Audit trail maintained for all processing activities

## Integration with Broadcast System

The scheduled processing integrates seamlessly with the broadcast messaging system:
- Uses existing service layer (`BroadcastMessageService`)
- Leverages existing email infrastructure
- Maintains consistency with manual broadcast sending
- Shares the same validation and security measures