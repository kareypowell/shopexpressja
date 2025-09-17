<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BackupSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Default retention settings
        \App\Models\BackupSetting::firstOrCreate(
            ['key' => 'retention.database_days'],
            [
                'value' => '30',
                'type' => 'integer',
                'description' => 'Number of days to retain database backups'
            ]
        );

        \App\Models\BackupSetting::firstOrCreate(
            ['key' => 'retention.files_days'],
            [
                'value' => '14',
                'type' => 'integer',
                'description' => 'Number of days to retain file backups'
            ]
        );

        // Default notification settings
        \App\Models\BackupSetting::firstOrCreate(
            ['key' => 'notifications.email'],
            [
                'value' => '',
                'type' => 'string',
                'description' => 'Email address for backup notifications'
            ]
        );

        \App\Models\BackupSetting::firstOrCreate(
            ['key' => 'notifications.notify_on_success'],
            [
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Send notifications for successful backups'
            ]
        );

        \App\Models\BackupSetting::firstOrCreate(
            ['key' => 'notifications.notify_on_failure'],
            [
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Send notifications for failed backups'
            ]
        );
    }
}
