<?php

namespace App\Console\Commands;

use App\Models\ReportTemplate;
use App\Models\User;
use App\Services\BusinessReportService;
use App\Services\ReportExportService;
use App\Services\ReportConfigurationService;
use App\Mail\ScheduledReportDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateScheduledReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:generate-scheduled 
                           {--type= : Report type to generate (sales, manifest, customer, financial)}
                           {--template= : Specific template ID to generate}
                           {--dry-run : Show what would be generated without actually generating}';

    /**
     * The console command description.
     */
    protected $description = 'Generate and deliver scheduled reports';

    protected BusinessReportService $businessReportService;
    protected ReportExportService $exportService;
    protected ReportConfigurationService $configService;

    public function __construct(
        BusinessReportService $businessReportService,
        ReportExportService $exportService,
        ReportConfigurationService $configService
    ) {
        parent::__construct();
        $this->businessReportService = $businessReportService;
        $this->exportService = $exportService;
        $this->configService = $configService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting scheduled report generation...');
        
        $type = $this->option('type');
        $templateId = $this->option('template');
        $dryRun = $this->option('dry-run');
        
        try {
            $templates = $this->getScheduledTemplates($type, $templateId);
            
            if ($templates->isEmpty()) {
                $this->info('No scheduled templates found for generation.');
                return 0;
            }
            
            $this->info("Found {$templates->count()} template(s) to process.");
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($templates as $template) {
                try {
                    if ($dryRun) {
                        $this->line("Would generate: {$template->name} ({$template->type})");
                        continue;
                    }
                    
                    $this->processScheduledTemplate($template);
                    $successCount++;
                    $this->info("✓ Generated report for template: {$template->name}");
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("✗ Failed to generate report for template: {$template->name}");
                    $this->error("  Error: {$e->getMessage()}");
                    
                    Log::error('Scheduled report generation failed', [
                        'template_id' => $template->id,
                        'template_name' => $template->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            $this->info("\nScheduled report generation completed:");
            $this->info("  Success: {$successCount}");
            $this->info("  Errors: {$errorCount}");
            
            return $errorCount > 0 ? 1 : 0;
            
        } catch (\Exception $e) {
            $this->error("Fatal error during scheduled report generation: {$e->getMessage()}");
            Log::error('Scheduled report generation fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    /**
     * Get templates that should be generated based on schedule
     */
    protected function getScheduledTemplates(?string $type, ?string $templateId)
    {
        $query = ReportTemplate::where('is_active', true);
        
        if ($templateId) {
            $query->where('id', $templateId);
        } elseif ($type) {
            $query->where('type', $type);
        }
        
        // For now, we'll generate all active templates
        // In a full implementation, you'd add schedule configuration to the template model
        return $query->get();
    }

    /**
     * Process a single scheduled template
     */
    protected function processScheduledTemplate(ReportTemplate $template): void
    {
        // Apply template default filters
        $filters = $this->configService->applyTemplateDefaults($template);
        
        // Set default date range if not specified
        if (!isset($filters['date_range'])) {
            $filters['date_range'] = 'last_30_days';
        }
        
        // Generate the report data
        $reportData = $this->generateReportData($template->type, $filters);
        
        // Export to PDF
        $exportJob = $this->exportService->queueExport(
            'pdf',
            $reportData,
            $this->getSystemUser(),
            [
                'template_id' => $template->id,
                'scheduled' => true,
                'generated_at' => now()->toISOString()
            ]
        );
        
        // Get recipients for this template
        $recipients = $this->getTemplateRecipients($template);
        
        // Send email notifications
        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new ScheduledReportDelivery($template, $exportJob));
        }
        
        Log::info('Scheduled report generated and queued for delivery', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'export_job_id' => $exportJob,
            'recipients_count' => count($recipients)
        ]);
    }

    /**
     * Generate report data based on type and filters
     */
    protected function generateReportData(string $type, array $filters): array
    {
        switch ($type) {
            case 'sales':
                return $this->businessReportService->generateSalesCollectionsReport($filters);
                
            case 'manifest':
                return $this->businessReportService->generateManifestPerformanceReport($filters);
                
            case 'customer':
                return $this->businessReportService->generateCustomerAnalyticsReport($filters);
                
            case 'financial':
                return $this->businessReportService->generateFinancialSummaryReport($filters);
                
            default:
                throw new \InvalidArgumentException("Unknown report type: {$type}");
        }
    }

    /**
     * Get recipients for a template
     * In a full implementation, this would be configurable per template
     */
    protected function getTemplateRecipients(ReportTemplate $template): array
    {
        // For now, send to all admins and superadmins
        return User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'superadmin']);
        })->pluck('email')->toArray();
    }

    /**
     * Get system user for automated operations
     */
    protected function getSystemUser(): User
    {
        // Return the first superadmin user for system operations
        return User::whereHas('role', function ($query) {
            $query->where('name', 'superadmin');
        })->first() ?? User::first();
    }
}