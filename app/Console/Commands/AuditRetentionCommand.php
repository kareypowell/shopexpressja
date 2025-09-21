<?php

namespace App\Console\Commands;

use App\Services\AuditRetentionService;
use App\Models\AuditSetting;
use Illuminate\Console\Command;

class AuditRetentionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:retention 
                            {--status : Show current retention status}
                            {--optimize : Show optimization recommendations}
                            {--apply-optimizations : Apply recommended optimizations}
                            {--set-policy= : Set retention policy (format: event_type:days)}
                            {--show-policies : Show current retention policies}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Manage audit log retention policies and optimization';

    /**
     * The audit retention service.
     */
    protected $auditRetentionService;

    /**
     * Create a new command instance.
     */
    public function __construct(AuditRetentionService $auditRetentionService)
    {
        parent::__construct();
        $this->auditRetentionService = $auditRetentionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Audit Retention Policy Manager');
        $this->info('=============================');

        // Show current policies
        if ($this->option('show-policies')) {
            return $this->showCurrentPolicies();
        }

        // Show retention status
        if ($this->option('status')) {
            return $this->showRetentionStatus();
        }

        // Show optimization recommendations
        if ($this->option('optimize')) {
            return $this->showOptimizations();
        }

        // Apply optimizations
        if ($this->option('apply-optimizations')) {
            return $this->applyOptimizations();
        }

        // Set specific policy
        if ($policyString = $this->option('set-policy')) {
            return $this->setRetentionPolicy($policyString);
        }

        // Default: show status
        return $this->showRetentionStatus();
    }

    /**
     * Show current retention policies
     */
    protected function showCurrentPolicies()
    {
        $this->info('Current Retention Policies:');
        $this->newLine();

        $policies = AuditSetting::get('retention_policy', AuditSetting::DEFAULT_SETTINGS['retention_policy']);

        $headers = ['Event Type', 'Retention (Days)', 'Retention Period'];
        $rows = [];

        foreach ($policies as $eventType => $days) {
            $period = $this->formatRetentionPeriod($days);
            $rows[] = [$eventType, $days, $period];
        }

        $this->table($headers, $rows);

        return 0;
    }

    /**
     * Show retention status
     */
    protected function showRetentionStatus()
    {
        $this->info('Audit Log Retention Status:');
        $this->newLine();

        $status = $this->auditRetentionService->getRetentionStatus();

        if (isset($status['error'])) {
            $this->error($status['error']);
            return 1;
        }

        $this->info("Total Records: " . number_format($status['total_records']));
        $this->info("Storage Usage: {$status['estimated_storage_mb']} MB");
        $this->newLine();

        if ($status['cleanup_needed']) {
            $this->warn('⚠️  Cleanup needed - some logs have exceeded retention periods');
        }

        if ($status['archive_needed']) {
            $this->warn('📦 Archive recommended - some logs are ready for archival');
        }

        if (!$status['cleanup_needed'] && !$status['archive_needed']) {
            $this->info('✅ All logs are within retention policies');
        }

        $this->newLine();

        // Show details by event type
        $headers = ['Event Type', 'Total', 'Expired', 'Archive Ready', 'Retention', 'Status'];
        $rows = [];

        foreach ($status['by_event_type'] as $eventType => $data) {
            $statusIcon = '✅';
            if ($data['expired_records'] > 0) {
                $statusIcon = '🗑️';
            } elseif ($data['archive_ready'] > 0) {
                $statusIcon = '📦';
            }

            $rows[] = [
                $eventType,
                number_format($data['total_records']),
                number_format($data['expired_records']),
                number_format($data['archive_ready']),
                $data['retention_days'] . ' days',
                $statusIcon,
            ];
        }

        $this->table($headers, $rows);

        // Show recommendations
        if (!empty($status['recommendations'])) {
            $this->newLine();
            $this->info('Recommendations:');
            foreach ($status['recommendations'] as $recommendation) {
                $this->info("  • {$recommendation}");
            }
        }

        return 0;
    }

    /**
     * Show optimization recommendations
     */
    protected function showOptimizations()
    {
        $this->info('Retention Policy Optimization Analysis:');
        $this->newLine();

        $optimizations = $this->auditRetentionService->optimizeRetentionPolicies();

        if (isset($optimizations['error'])) {
            $this->error($optimizations['error']);
            return 1;
        }

        if (empty($optimizations['optimizations'])) {
            $this->info('✅ Current retention policies are already optimized');
            return 0;
        }

        $this->info("Potential storage savings: {$optimizations['estimated_savings_mb']} MB");
        $this->newLine();

        $headers = ['Event Type', 'Current', 'Recommended', 'Change', 'Records', 'Reason'];
        $rows = [];

        foreach ($optimizations['optimizations'] as $opt) {
            $changeText = $opt['change'] === 'reduce' ? 
                "↓ -{$opt['days_difference']}d" : 
                "↑ +{$opt['days_difference']}d";

            // Truncate reason if too long
            $reason = strlen($opt['reason']) > 40 ? 
                substr($opt['reason'], 0, 37) . '...' : 
                $opt['reason'];

            $rows[] = [
                $opt['event_type'],
                $opt['current_days'] . 'd',
                $opt['recommended_days'] . 'd',
                $changeText,
                number_format($opt['record_count']),
                $reason,
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Run with --apply-optimizations to apply these recommendations');

        return 0;
    }

    /**
     * Apply optimization recommendations
     */
    protected function applyOptimizations()
    {
        $this->info('Applying retention policy optimizations...');

        $optimizations = $this->auditRetentionService->optimizeRetentionPolicies();

        if (isset($optimizations['error'])) {
            $this->error($optimizations['error']);
            return 1;
        }

        if (empty($optimizations['optimizations'])) {
            $this->info('No optimizations needed - current policies are already optimal');
            return 0;
        }

        if (!$this->option('force')) {
            $this->warn("This will update retention policies for " . count($optimizations['optimizations']) . " event types.");
            $this->warn("Estimated storage savings: {$optimizations['estimated_savings_mb']} MB");
            
            if (!$this->confirm('Apply these optimizations?')) {
                $this->info('Optimization cancelled.');
                return 1;
            }
        }

        try {
            $success = $this->auditRetentionService->applyOptimizedPolicies($optimizations['recommended_policies']);

            if ($success) {
                $this->info('✅ Retention policies optimized successfully!');
                
                $this->newLine();
                $this->info('Applied changes:');
                foreach ($optimizations['optimizations'] as $opt) {
                    $changeText = $opt['change'] === 'reduce' ? 'reduced' : 'increased';
                    $this->info("  • {$opt['event_type']}: {$changeText} from {$opt['current_days']} to {$opt['recommended_days']} days");
                }

                return 0;
            } else {
                $this->error('Failed to apply optimizations');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Failed to apply optimizations: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Set specific retention policy
     */
    protected function setRetentionPolicy(string $policyString)
    {
        if (!str_contains($policyString, ':')) {
            $this->error('Invalid policy format. Use: event_type:days (e.g., authentication:180)');
            return 1;
        }

        [$eventType, $daysString] = explode(':', $policyString, 2);
        $days = (int) $daysString;

        if ($days < 1) {
            $this->error('Retention days must be at least 1');
            return 1;
        }

        $this->info("Setting retention policy for {$eventType}: {$days} days");

        if (!$this->option('force')) {
            if (!$this->confirm("Update retention policy for {$eventType} to {$days} days?")) {
                $this->info('Policy update cancelled.');
                return 1;
            }
        }

        try {
            $currentPolicies = AuditSetting::get('retention_policy', AuditSetting::DEFAULT_SETTINGS['retention_policy']);
            $currentPolicies[$eventType] = $days;

            // Validate the updated policy
            $validation = $this->auditRetentionService->validateRetentionPolicy($currentPolicies);
            
            if (!$validation['valid']) {
                $this->error('Invalid retention policy:');
                foreach ($validation['errors'] as $error) {
                    $this->error("  - {$error}");
                }
                return 1;
            }

            if (!empty($validation['warnings'])) {
                $this->warn('Policy warnings:');
                foreach ($validation['warnings'] as $warning) {
                    $this->warn("  - {$warning}");
                }
            }

            AuditSetting::set('retention_policy', $currentPolicies);
            
            $this->info("✅ Retention policy updated successfully for {$eventType}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to update retention policy: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Format retention period in human-readable format
     */
    protected function formatRetentionPeriod(int $days): string
    {
        if ($days >= 365) {
            $years = round($days / 365, 1);
            return $years == 1 ? '1 year' : "{$years} years";
        } elseif ($days >= 30) {
            $months = round($days / 30, 1);
            return $months == 1 ? '1 month' : "{$months} months";
        } elseif ($days >= 7) {
            $weeks = round($days / 7, 1);
            return $weeks == 1 ? '1 week' : "{$weeks} weeks";
        } else {
            return $days == 1 ? '1 day' : "{$days} days";
        }
    }
}