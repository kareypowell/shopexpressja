<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\ReportAuditService;

class ReportAccessMiddleware
{
    protected ReportAuditService $auditService;

    public function __construct(ReportAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permission = null)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Basic report access check
        if (!Gate::allows('viewAny', 'App\Models\Report')) {
            $this->auditService->logUnauthorizedAccess(
                'report_access',
                $user,
                $request,
                'User does not have basic report access permissions'
            );
            abort(403, 'Access denied. You do not have permission to access reports.');
        }

        // Specific permission check if provided
        if ($permission) {
            $this->checkSpecificPermission($user, $permission, $request);
        }

        // Log successful report access for audit trail
        $this->logReportAccess($request, $user, $permission);

        return $next($request);
    }

    /**
     * Check specific report permission
     *
     * @param  \App\Models\User  $user
     * @param  string  $permission
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function checkSpecificPermission($user, string $permission, Request $request)
    {
        $permissionMap = [
            'sales' => 'viewSalesReports',
            'manifest' => 'viewManifestReports',
            'customer' => 'viewCustomerReports',
            'financial' => 'viewFinancialReports',
            'export' => 'exportReports',
            'admin' => 'administerReports',
        ];

        $gateMethod = $permissionMap[$permission] ?? null;

        if (!$gateMethod || !Gate::allows($gateMethod, 'App\Models\Report')) {
            $this->auditService->logUnauthorizedAccess(
                "{$permission}_report_access",
                $user,
                $request,
                "User does not have permission to access {$permission} reports"
            );
            abort(403, "Access denied. You do not have permission to access {$permission} reports.");
        }
    }

    /**
     * Log report access for audit trail
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  string|null  $permission
     * @return void
     */
    private function logReportAccess(Request $request, $user, ?string $permission = null)
    {
        $reportType = $permission ?? 'general';
        $filters = $request->all(); // Capture any filters applied
        
        $this->auditService->logReportAccess($reportType, $user, $request, $filters);
    }
}