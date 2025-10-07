<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuditSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Exception;

class AuditExportService
{
    /**
     * Export audit logs to CSV format
     *
     * @param Collection $auditLogs
     * @param array $filters Applied filters for context
     * @return string CSV content
     */
    public function exportToCsv(Collection $auditLogs, array $filters = []): string
    {
        $headers = [
            'ID',
            'Date/Time',
            'Event Type',
            'Action',
            'User',
            'User Email',
            'Auditable Type',
            'Auditable ID',
            'IP Address',
            'URL',
            'User Agent',
            'Old Values',
            'New Values',
            'Additional Data'
        ];

        $rows = [];
        $rows[] = $headers;

        foreach ($auditLogs as $log) {
            $rows[] = [
                $log->id,
                $log->created_at->format('Y-m-d H:i:s'),
                $log->event_type ?? '',
                $log->action ?? '',
                $log->user ? ($log->user->full_name) : 'System',
                $log->user ? $log->user->email : 'N/A',
                $log->auditable_type ?? '',
                $log->auditable_id ?? '',
                $log->ip_address ?? '',
                $log->url ?? '',
                $log->user_agent ?? '',
                $log->old_values ? json_encode($log->old_values) : '',
                $log->new_values ? json_encode($log->new_values) : '',
                $log->additional_data ? json_encode($log->additional_data) : ''
            ];
        }

        return $this->convertToCsv($rows);
    }

    /**
     * Export audit logs to PDF format for compliance reporting
     *
     * @param Collection $auditLogs
     * @param array $filters Applied filters for context
     * @param array $options Export options
     * @return string Path to generated PDF
     */
    public function exportToPdf(Collection $auditLogs, array $filters = [], array $options = []): string
    {
        try {
            $data = [
                'audit_logs' => $auditLogs,
                'filters' => $filters,
                'generated_at' => Carbon::now(),
                'total_records' => $auditLogs->count(),
                'report_title' => $options['title'] ?? 'Audit Log Report',
                'company' => [
                    'name' => config('app.name', 'Shop Express JA'),
                    'address' => '57 Law Street, Kingston, Jamaica',
                    'phone' => '876-453-7789',
                    'email' => 'support@shopexpressja.com',
                    'website' => 'www.shopexpressjs.com',
                ],
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.audit-log-report', $data);
            
            // Set PDF options
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

            // Generate filename
            $filename = 'audit-reports/audit_log_report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
            
            // Save PDF to storage
            $pdfContent = $pdf->output();
            Storage::disk('public')->put($filename, $pdfContent);

            return $filename;

        } catch (Exception $e) {
            throw new Exception('Failed to generate PDF report: ' . $e->getMessage());
        }
    }

    /**
     * Generate a comprehensive compliance report
     *
     * @param array $filters
     * @param array $options
     * @return array Report data
     */
    public function generateComplianceReport(array $filters = [], array $options = []): array
    {
        $query = AuditLog::with(['user']);

        // Apply filters
        $this->applyFilters($query, $filters);

        $auditLogs = $query->orderBy('created_at', 'desc')->get();

        // Generate statistics
        $statistics = $this->generateStatistics($auditLogs);

        // Group by event types
        $eventTypeBreakdown = $auditLogs->groupBy('event_type')->map(function ($logs) {
            return [
                'count' => $logs->count(),
                'actions' => $logs->groupBy('action')->map->count(),
                'users' => $logs->map(function($log) {
                    return $log->user ? ($log->user->full_name) : null;
                })->filter()->unique()->values(),
            ];
        });

        // Security events analysis
        $securityEvents = $auditLogs->where('event_type', 'security_event');
        $failedLogins = $auditLogs->where('action', 'failed_login');

        return [
            'report_metadata' => [
                'generated_at' => Carbon::now(),
                'filters_applied' => $filters,
                'total_records' => $auditLogs->count(),
                'date_range' => [
                    'from' => $auditLogs->min('created_at'),
                    'to' => $auditLogs->max('created_at'),
                ],
            ],
            'statistics' => $statistics,
            'event_type_breakdown' => $eventTypeBreakdown,
            'security_analysis' => [
                'security_events_count' => $securityEvents->count(),
                'failed_logins_count' => $failedLogins->count(),
                'unique_ip_addresses' => $auditLogs->pluck('ip_address')->filter()->unique()->count(),
                'most_active_users' => $auditLogs->groupBy('user_id')
                    ->map(function ($logs) {
                        return [
                            'user' => $logs->first()->user ? ($logs->first()->user->full_name) : 'System',
                            'count' => $logs->count(),
                        ];
                    })
                    ->sortByDesc('count')
                    ->take(10)
                    ->values(),
            ],
            'audit_logs' => $auditLogs,
        ];
    }

    /**
     * Create configurable export templates
     *
     * @param string $templateName
     * @param array $configuration
     * @return bool
     */
    public function createExportTemplate(string $templateName, array $configuration): bool
    {
        try {
            $template = [
                'name' => $templateName,
                'fields' => $configuration['fields'] ?? [],
                'filters' => $configuration['filters'] ?? [],
                'format' => $configuration['format'] ?? 'csv',
                'options' => $configuration['options'] ?? [],
                'created_at' => Carbon::now(),
            ];

            AuditSetting::updateOrCreate(
                ['setting_key' => "export_template_{$templateName}"],
                ['setting_value' => $template]
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get available export templates
     *
     * @return Collection
     */
    public function getExportTemplates(): Collection
    {
        return AuditSetting::where('setting_key', 'like', 'export_template_%')
            ->get()
            ->map(function ($setting) {
                return $setting->setting_value;
            });
    }

    /**
     * Schedule report generation
     *
     * @param array $configuration
     * @return bool
     */
    public function scheduleReport(array $configuration): bool
    {
        try {
            $schedule = [
                'name' => $configuration['name'],
                'frequency' => $configuration['frequency'], // daily, weekly, monthly
                'filters' => $configuration['filters'] ?? [],
                'format' => $configuration['format'] ?? 'pdf',
                'recipients' => $configuration['recipients'] ?? [],
                'next_run' => $this->calculateNextRun($configuration['frequency']),
                'created_at' => Carbon::now(),
                'active' => true,
            ];

            AuditSetting::updateOrCreate(
                ['setting_key' => "scheduled_report_{$configuration['name']}"],
                ['setting_value' => $schedule]
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get scheduled reports
     *
     * @return Collection
     */
    public function getScheduledReports(): Collection
    {
        return AuditSetting::where('setting_key', 'like', 'scheduled_report_%')
            ->get()
            ->map(function ($setting) {
                return $setting->setting_value;
            })
            ->where('active', true);
    }

    /**
     * Convert array data to CSV format
     *
     * @param array $rows
     * @return string
     */
    protected function convertToCsv(array $rows): string
    {
        $csvContent = '';
        foreach ($rows as $row) {
            $escapedRow = array_map(function($field) {
                // Handle null values
                if ($field === null) {
                    return '';
                }
                
                // Convert to string and escape quotes
                $field = (string) $field;
                return str_replace('"', '""', $field);
            }, $row);
            $csvContent .= '"' . implode('","', $escapedRow) . '"' . "\n";
        }

        return $csvContent;
    }

    /**
     * Apply filters to audit log query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('action', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('event_type', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('auditable_type', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('ip_address', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', '%' . $filters['ip_address'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * Generate statistics from audit logs
     *
     * @param Collection $auditLogs
     * @return array
     */
    protected function generateStatistics(Collection $auditLogs): array
    {
        return [
            'total_events' => $auditLogs->count(),
            'unique_users' => $auditLogs->pluck('user_id')->filter()->unique()->count(),
            'unique_ip_addresses' => $auditLogs->pluck('ip_address')->filter()->unique()->count(),
            'event_types' => $auditLogs->groupBy('event_type')->map->count(),
            'actions' => $auditLogs->groupBy('action')->map->count(),
            'daily_activity' => $auditLogs->groupBy(function ($log) {
                return $log->created_at->format('Y-m-d');
            })->map->count(),
            'hourly_activity' => $auditLogs->groupBy(function ($log) {
                return $log->created_at->format('H');
            })->map->count(),
        ];
    }

    /**
     * Calculate next run time for scheduled reports
     *
     * @param string $frequency
     * @return Carbon
     */
    protected function calculateNextRun(string $frequency): Carbon
    {
        switch ($frequency) {
            case 'daily':
                return Carbon::now()->addDay();
            case 'weekly':
                return Carbon::now()->addWeek();
            case 'monthly':
                return Carbon::now()->addMonth();
            default:
                return Carbon::now()->addDay();
        }
    }
}