<?php

namespace App\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportAuditService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Log report access
     *
     * @param string $reportType
     * @param User $user
     * @param Request $request
     * @param array $filters
     * @return void
     */
    public function logReportAccess(User $user, string $reportType, string $action, array $filters = [])
    {
        try {
            $request = request();
            $this->auditService->log([
                'event_type' => 'report_access',
                'action' => $action,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'description' => "User {$action} {$reportType} report",
                    'report_type' => $reportType,
                    'filters_applied' => $filters,
                    'user_role' => $user->getRoleName() ?: 'unknown',
                    'route_name' => $request->route() ? $request->route()->getName() : null,
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log report access', [
                'user_id' => $user->id,
                'report_type' => $reportType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log report export
     *
     * @param string $reportType
     * @param string $exportFormat
     * @param User $user
     * @param Request $request
     * @param array $filters
     * @param bool $containsSensitiveData
     * @return void
     */
    public function logReportExport(string $reportType, string $exportFormat, User $user, Request $request, array $filters = [], bool $containsSensitiveData = false)
    {
        try {
            $this->auditService->log([
                'event_type' => 'report_export',
                'action' => 'export',
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'description' => "User exported {$reportType} report as {$exportFormat}",
                    'report_type' => $reportType,
                    'export_format' => $exportFormat,
                    'filters_applied' => $filters,
                    'contains_sensitive_data' => $containsSensitiveData,
                    'user_role' => $user->getRoleName() ?: 'unknown',
                    'route_name' => $request->route() ? $request->route()->getName() : null,
                    'url' => $request->fullUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log report export', [
                'user_id' => $user->id,
                'report_type' => $reportType,
                'export_format' => $exportFormat,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log report filter save/update
     *
     * @param string $action
     * @param string $filterName
     * @param User $user
     * @param Request $request
     * @param array $filterConfig
     * @return void
     */
    public function logFilterManagement(string $action, string $filterName, User $user, Request $request, array $filterConfig = [])
    {
        try {
            $this->auditService->log([
                'event_type' => 'report_filter_management',
                'action' => $action,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'description' => "User {$action} report filter: {$filterName}",
                    'filter_name' => $filterName,
                    'filter_config' => $filterConfig,
                    'user_role' => $user->role->name ?? 'unknown',
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log filter management', [
                'user_id' => $user->id,
                'action' => $action,
                'filter_name' => $filterName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log customer data access
     *
     * @param User $user
     * @param User $customer
     * @param Request $request
     * @param string $dataType
     * @return void
     */
    public function logCustomerDataAccess(User $user, User $customer, Request $request, string $dataType = 'general')
    {
        try {
            $this->auditService->log([
                'event_type' => 'customer_data_access',
                'action' => 'access',
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'description' => "User accessed customer data for: {$customer->first_name} {$customer->last_name}",
                    'accessed_customer_id' => $customer->id,
                    'accessed_customer_email' => $customer->email,
                    'data_type' => $dataType,
                    'user_role' => $user->getRoleName() ?: 'unknown',
                    'route_name' => $request->route() ? $request->route()->getName() : null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log customer data access', [
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'data_type' => $dataType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log report template management
     *
     * @param string $action
     * @param string $templateName
     * @param User $user
     * @param Request $request
     * @return void
     */
    public function logTemplateManagement(string $action, string $templateName, User $user, Request $request)
    {
        try {
            $this->auditService->log([
                'event_type' => 'report_template_management',
                'action' => $action,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'description' => "User {$action} report template: {$templateName}",
                    'template_name' => $templateName,
                    'user_role' => $user->role->name ?? 'unknown',
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log template management', [
                'user_id' => $user->id,
                'action' => $action,
                'template_name' => $templateName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log unauthorized access attempts
     *
     * @param string $attemptedAction
     * @param User $user
     * @param Request $request
     * @param string $reason
     * @return void
     */
    public function logUnauthorizedAccess(string $attemptedAction, User $user, Request $request, string $reason = '')
    {
        try {
            $this->auditService->log([
                'event_type' => 'unauthorized_report_access',
                'action' => 'unauthorized_access',
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'description' => "Unauthorized access attempt: {$attemptedAction}",
                    'attempted_action' => $attemptedAction,
                    'denial_reason' => $reason,
                    'user_role' => $user->getRoleName() ?: 'unknown',
                    'route_name' => $request->route() ? $request->route()->getName() : null,
                    'url' => $request->fullUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log unauthorized access', [
                'user_id' => $user->id,
                'attempted_action' => $attemptedAction,
                'error' => $e->getMessage()
            ]);
        }
    }
}