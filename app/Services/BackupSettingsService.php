<?php

namespace App\Services;

use App\Models\BackupSetting;

class BackupSettingsService
{
    /**
     * Get retention settings
     */
    public static function getRetentionSettings()
    {
        return [
            'database_days' => BackupSetting::get('retention.database_days', 30),
            'files_days' => BackupSetting::get('retention.files_days', 14)
        ];
    }

    /**
     * Get notification settings
     */
    public static function getNotificationSettings()
    {
        return [
            'email' => BackupSetting::get('notifications.email', ''),
            'notify_on_success' => BackupSetting::get('notifications.notify_on_success', false),
            'notify_on_failure' => BackupSetting::get('notifications.notify_on_failure', true)
        ];
    }

    /**
     * Get database retention days
     */
    public static function getDatabaseRetentionDays()
    {
        return BackupSetting::get('retention.database_days', 30);
    }

    /**
     * Get files retention days
     */
    public static function getFilesRetentionDays()
    {
        return BackupSetting::get('retention.files_days', 14);
    }

    /**
     * Get notification email
     */
    public static function getNotificationEmail()
    {
        return BackupSetting::get('notifications.email', '');
    }

    /**
     * Should notify on success
     */
    public static function shouldNotifyOnSuccess()
    {
        return BackupSetting::get('notifications.notify_on_success', false);
    }

    /**
     * Should notify on failure
     */
    public static function shouldNotifyOnFailure()
    {
        return BackupSetting::get('notifications.notify_on_failure', true);
    }

    /**
     * Get all backup settings as an array
     */
    public static function getAllSettings()
    {
        return [
            'retention' => static::getRetentionSettings(),
            'notifications' => static::getNotificationSettings()
        ];
    }
}