<?php

namespace App\Console\Commands;

use App\Services\SecurityMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SecurityAnomalyDetectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:detect-anomalies 
                            {--hours=1 : Number of hours to analyze}
                            {--alert-threshold=medium : Minimum severity to generate alerts (low, medium, high, critical)}';

    /**
     * The console command description.
     */
    protected $description = 'Detect security anomalies and generate alerts for suspicious system-wide activity';

    protected SecurityMonitoringService $securityService;

    public function __construct(SecurityMonitoringService $securityService)
    {
        parent::__construct();
        $this->securityService = $securityService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $alertThreshold = $this->option('alert-threshold');

        $this->info("Starting security anomaly detection for the last {$hours} hour(s)...");

        try {
            // Detect system-wide anomalies
            $anomalies = $this->securityService->detectSystemAnomalies();

            if (empty($anomalies)) {
                $this->info('No security anomalies detected.');
                return Command::SUCCESS;
            }

            $this->warn("Found " . count($anomalies) . " potential security anomalies:");

            $alertsGenerated = 0;

            foreach ($anomalies as $anomaly) {
                $this->displayAnomaly($anomaly);

                // Generate alert if severity meets threshold
                if ($this->shouldGenerateAlert($anomaly['severity'], $alertThreshold)) {
                    $this->generateAnomalyAlert($anomaly);
                    $alertsGenerated++;
                }
            }

            if ($alertsGenerated > 0) {
                $this->info("Generated {$alertsGenerated} security alerts.");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Security anomaly detection failed: ' . $e->getMessage());
            Log::error('Security anomaly detection command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Display anomaly information
     */
    protected function displayAnomaly(array $anomaly): void
    {
        $severity = strtoupper($anomaly['severity']);
        $type = $anomaly['type'];
        $description = $anomaly['description'];
        $count = $anomaly['count'] ?? 'N/A';

        $this->line("  [{$severity}] {$type}: {$description} (Count: {$count})");
    }

    /**
     * Check if alert should be generated based on severity threshold
     */
    protected function shouldGenerateAlert(string $severity, string $threshold): bool
    {
        $severityLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        
        $severityValue = $severityLevels[$severity] ?? 0;
        $thresholdValue = $severityLevels[$threshold] ?? 2;

        return $severityValue >= $thresholdValue;
    }

    /**
     * Generate security alert for anomaly
     */
    protected function generateAnomalyAlert(array $anomaly): void
    {
        $riskScore = $this->calculateRiskScore($anomaly['severity']);
        
        $alertData = [
            'risk_score' => $riskScore,
            'risk_level' => $anomaly['severity'],
            'alerts' => [$anomaly['description']],
            'analysis_type' => 'system_anomaly',
            'anomaly_type' => $anomaly['type'],
            'anomaly_count' => $anomaly['count'] ?? null,
            'detection_time' => now(),
            'automated_detection' => true
        ];

        $this->securityService->generateSecurityAlert($alertData);
        
        $this->info("  → Alert generated for {$anomaly['type']}");
    }

    /**
     * Calculate risk score based on severity
     */
    protected function calculateRiskScore(string $severity): int
    {
        return match ($severity) {
            'critical' => 95,
            'high' => 80,
            'medium' => 60,
            'low' => 30,
            default => 25
        };
    }
}