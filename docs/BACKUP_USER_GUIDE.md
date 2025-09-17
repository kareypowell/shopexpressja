# Backup Management User Guide

## Overview

The ShipSharkLtd backup management system provides comprehensive data protection for your business operations. This guide covers how to use the backup management features available in the admin interface.

## Accessing Backup Management

1. Log in to the admin panel with administrator privileges
2. Navigate to **System** → **Backup Management** in the sidebar
3. You'll see three main sections:
   - **Dashboard**: Overview of backup status and recent activity
   - **History**: List of all backup files with download options
   - **Settings**: Configuration for automated backups and retention policies

## Backup Dashboard

### Overview Information
The dashboard displays:
- **Last Backup**: Date and time of the most recent backup
- **Backup Status**: Current system status (healthy/warning/error)
- **Storage Usage**: Amount of disk space used by backup files
- **Recent Activity**: List of recent backup operations

### Creating Manual Backups

1. Click the **Create Backup** button on the dashboard
2. Select backup type:
   - **Database Only**: Creates a MySQL database dump
   - **Files Only**: Backs up uploaded files and documents
   - **Full Backup**: Includes both database and files
3. Optionally provide a custom name for the backup
4. Click **Start Backup** to begin the process
5. Monitor progress in the Recent Activity section

### Backup Status Indicators

- 🟢 **Green**: Backup completed successfully
- 🟡 **Yellow**: Backup completed with warnings
- 🔴 **Red**: Backup failed - check error details
- ⏳ **Blue**: Backup currently in progress

## Backup History

### Viewing Backup Files

The History section shows all available backup files with:
- **Name**: Backup file name with timestamp
- **Type**: Database, Files, or Full backup
- **Size**: File size in MB/GB
- **Created**: Date and time of creation
- **Status**: Success/Failed indicator
- **Actions**: Download and delete options

### Downloading Backups

1. Locate the backup file in the history list
2. Click the **Download** button
3. The system will generate a secure download link
4. Your browser will begin downloading the backup file
5. Download links expire after 1 hour for security

### Filtering and Search

- Use the **Type** filter to show only specific backup types
- Use the **Date Range** picker to filter by creation date
- Use the search box to find backups by name

## Backup Settings

### Automated Backup Configuration

1. Navigate to the Settings tab
2. Toggle **Enable Automated Backups** to activate scheduling
3. Configure backup frequency:
   - **Daily**: Runs every day at specified time
   - **Weekly**: Runs once per week on selected day
   - **Monthly**: Runs on specified day of each month
4. Set the backup time (24-hour format)
5. Choose backup type (Database, Files, or Full)
6. Click **Save Settings**

### Retention Policies

Configure how long backup files are kept:

1. **Database Backup Retention**: Number of days to keep database backups
2. **File Backup Retention**: Number of days to keep file backups
3. **Maximum Storage**: Total storage limit for all backups

When limits are reached, the oldest backups are automatically deleted.

### Notification Settings

Configure email alerts for backup operations:

1. **Notification Email**: Email address for backup alerts
2. **Success Notifications**: Receive emails for successful backups
3. **Failure Notifications**: Receive emails for failed backups (recommended)
4. **Storage Warnings**: Receive alerts when storage usage is high

## Best Practices

### Regular Monitoring
- Check the backup dashboard weekly to ensure backups are running
- Review backup history monthly to verify retention policies
- Monitor storage usage to prevent disk space issues

### Backup Strategy
- Use **Full Backups** for comprehensive protection
- Schedule automated backups during low-traffic hours
- Keep at least 30 days of database backups
- Store critical backups offsite by downloading them

### Security Considerations
- Only administrators should have access to backup management
- Download backup files to secure, encrypted storage
- Regularly test backup restoration procedures
- Monitor backup access logs for unauthorized activity

## Troubleshooting

### Common Issues

**Backup Failed - Disk Space**
- Check available disk space on the server
- Reduce retention periods to free up space
- Delete old, unnecessary backup files

**Backup Failed - Database Connection**
- Verify database server is running
- Check database credentials in configuration
- Contact system administrator if issues persist

**Download Links Not Working**
- Ensure you're using the link within 1 hour
- Try generating a new download link
- Check your browser's download settings

**Automated Backups Not Running**
- Verify the Laravel scheduler is configured (cron job)
- Check backup settings are enabled
- Review system logs for error messages

### Getting Help

If you encounter issues not covered in this guide:

1. Check the system logs in the admin panel
2. Contact your system administrator
3. Review the backup error messages for specific details
4. Ensure your user account has proper administrator permissions

## Security Notes

- Backup files contain sensitive business data
- Always use secure connections (HTTPS) when downloading
- Store downloaded backups in encrypted, secure locations
- Regularly audit who has access to backup management features
- Consider implementing additional access controls for sensitive environments