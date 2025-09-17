<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Services\BackupStorageManager;
use Illuminate\Console\Command;

class BackupStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display backup system status and recent backup history';

    /**
     * The backup service instance.
     *
     * @var BackupService
     */
    protected $backupService;

    /**
     * The backup storage manager instance.
     *
     * @var BackupStorageManager
     */
    protected $storageManager;

    /**
     * Create a new command instance.
     *
     * @param BackupService $backupService
     * @param BackupStorageManager $storageManager
     */
    public function __construct(BackupService $backupService, BackupStorageManager $storageManager)
    {
        parent::__construct();
        $this->backupService = $backupService;
        $this->storageManager = $storageManager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Backup System Status');
        $this->info('==================');

        try {
            // Get backup status
            $status = $this->backupService->getBackupStatus();
            $this->displaySystemStatus($status);

            // Get recent backup history
            $history = $this->backupService->getBackupHistory(10);
            $this->displayBackupHistory($history);

            // Get storage information
            $storageInfo = $this->storageManager->getStorageInfo();
            $this->displayStorageInfo($storageInfo);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to retrieve backup status: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display system status information.
     *
     * @param \App\Services\BackupStatus $status
     */
    protected function displaySystemStatus($status): void
    {
        $this->info("\nSystem Status:");
        
        $statusData = [
            ['Last Backup', $status->getLastBackupDate() ? $status->getLastBackupDate()->format('Y-m-d H:i:s') : 'Never'],
            ['Last Successful Backup', $status->getLastSuccessfulBackupDate() ? $status->getLastSuccessfulBackupDate()->format('Y-m-d H:i:s') : 'Never'],
            ['Total Backups', $status->getTotalBackups()],
            ['Failed Backups (Last 7 days)', $status->getRecentFailures()],
            ['System Health', $status->isHealthy() ? '✓ Healthy' : '✗ Issues Detected'],
        ];

        $this->table(['Property', 'Value'], $statusData);

        if (!$status->isHealthy()) {
            $this->warn("\nHealth Issues:");
            foreach ($status->getHealthIssues() as $issue) {
                $this->error("- {$issue}");
            }
        }
    }

    /**
     * Display recent backup history.
     *
     * @param \Illuminate\Support\Collection $history
     */
    protected function displayBackupHistory($history): void
    {
        $this->info("\nRecent Backup History:");

        if ($history->isEmpty()) {
            $this->info('No backups found.');
            return;
        }

        $tableData = [];
        foreach ($history as $backup) {
            $tableData[] = [
                $backup->created_at->format('Y-m-d H:i:s'),
                $backup->name,
                ucfirst($backup->type),
                $this->formatBytes($backup->file_size ?? 0),
                $this->getStatusIcon($backup->status) . ' ' . ucfirst($backup->status),
            ];
        }

        $this->table(
            ['Date', 'Name', 'Type', 'Size', 'Status'],
            $tableData
        );
    }

    /**
     * Display storage information.
     *
     * @param array $storageInfo
     */
    protected function displayStorageInfo(array $storageInfo): void
    {
        $this->info("\nStorage Information:");

        $storageData = [
            ['Backup Directory', $storageInfo['path']],
            ['Total Backup Files', $storageInfo['total_files']],
            ['Total Storage Used', $this->formatBytes($storageInfo['total_size'])],
            ['Available Disk Space', $this->formatBytes($storageInfo['available_space'])],
            ['Disk Usage', $storageInfo['disk_usage_percent'] . '%'],
        ];

        $this->table(['Property', 'Value'], $storageData);

        // Show warning if disk usage is high
        if ($storageInfo['disk_usage_percent'] > 80) {
            $this->warn('⚠ Warning: Disk usage is high. Consider running backup cleanup.');
        }

        // Show retention policy info
        $this->info("\nRetention Policy:");
        $retentionData = [
            ['Database Backups', $storageInfo['retention']['database'] . ' days'],
            ['File Backups', $storageInfo['retention']['files'] . ' days'],
        ];
        $this->table(['Type', 'Retention Period'], $retentionData);
    }

    /**
     * Get status icon for backup status.
     *
     * @param string $status
     * @return string
     */
    protected function getStatusIcon(string $status): string
    {
        switch ($status) {
            case 'completed':
                return '✓';
            case 'failed':
                return '✗';
            case 'pending':
                return '⏳';
            default:
                return '?';
        }
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}