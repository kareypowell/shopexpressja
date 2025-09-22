<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BusinessReportService;
use App\Services\ReportDataService;
use App\Services\ReportAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Response;

class ReportApiController extends Controller
{
    protected BusinessReportService $businessReportService;
    protected ReportDataService $reportDataService;
    protected ReportAuditService $auditService;

    public function __construct(
        BusinessReportService $businessReportService,
        ReportDataService $reportDataService,
        ReportAuditService $auditService
    ) {
        $this->businessReportService = $businessReportService;
        $this->reportDataService = $reportDataService;
        $this->auditService = $auditService;
        
        // Apply Sanctum authentication for API routes
        $this->middleware(['auth:sanctum']);
        $this->middleware('throttle:api-reports')->only([
            'salesData', 'manifestData', 'customerData', 'financialSummary'
        ]);
    }

    /**
     * Get sales and collections data
     * 
     * @group Reports
     * @authenticated
     */
    public function salesData(Request $request): JsonResponse
    {
        Gate::authorize('viewSalesReports');
        
        // Enhanced rate limiting for API
        $key = 'api-reports-sales:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 100)) {
            return $this->rateLimitResponse($key);
        }
        
        RateLimiter::hit($key, 3600); // 1 hour window
        
        try {
            $filters = $this->validateApiFilters($request);
            
            $data = $this->businessReportService->generateSalesCollectionsReport($filters);
            
            $this->auditService->logReportAccess(
                $request->user(), 
                'sales_data', 
                'api_external', 
                $filters,
                $request->ip(),
                $request->userAgent()
            );
            
            return $this->successResponse($data, 'Sales data retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve sales data', $e);
        }
    }

    /**
     * Get manifest performance data
     * 
     * @group Reports
     * @authenticated
     */
    public function manifestData(Request $request): JsonResponse
    {
        Gate::authorize('viewManifestReports');
        
        $key = 'api-reports-manifest:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 100)) {
            return $this->rateLimitResponse($key);
        }
        
        RateLimiter::hit($key, 3600);
        
        try {
            $filters = $this->validateApiFilters($request);
            
            $data = $this->businessReportService->generateManifestPerformanceReport($filters);
            
            $this->auditService->logReportAccess(
                $request->user(), 
                'manifest_data', 
                'api_external', 
                $filters,
                $request->ip(),
                $request->userAgent()
            );
            
            return $this->successResponse($data, 'Manifest data retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve manifest data', $e);
        }
    }

    /**
     * Get customer analytics data
     * 
     * @group Reports
     * @authenticated
     */
    public function customerData(Request $request): JsonResponse
    {
        Gate::authorize('viewCustomerReports');
        
        $key = 'api-reports-customer:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 100)) {
            return $this->rateLimitResponse($key);
        }
        
        RateLimiter::hit($key, 3600);
        
        try {
            $filters = $this->validateApiFilters($request);
            
            $data = $this->businessReportService->generateCustomerAnalyticsReport($filters);
            
            $this->auditService->logReportAccess(
                $request->user(), 
                'customer_data', 
                'api_external', 
                $filters,
                $request->ip(),
                $request->userAgent()
            );
            
            return $this->successResponse($data, 'Customer data retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer data', $e);
        }
    }

    /**
     * Get financial summary data
     * 
     * @group Reports
     * @authenticated
     */
    public function financialSummary(Request $request): JsonResponse
    {
        Gate::authorize('viewSalesReports');
        
        $key = 'api-reports-financial:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 50)) {
            return $this->rateLimitResponse($key);
        }
        
        RateLimiter::hit($key, 3600);
        
        try {
            $filters = $this->validateApiFilters($request);
            
            $data = $this->businessReportService->generateFinancialSummaryReport($filters);
            
            $this->auditService->logReportAccess(
                $request->user(), 
                'financial_summary', 
                'api_external', 
                $filters,
                $request->ip(),
                $request->userAgent()
            );
            
            return $this->successResponse($data, 'Financial summary retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve financial summary', $e);
        }
    }

    /**
     * Get aggregated dashboard metrics
     * 
     * @group Reports
     * @authenticated
     */
    public function dashboardMetrics(Request $request): JsonResponse
    {
        Gate::authorize('viewReports');
        
        $key = 'api-reports-dashboard:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 200)) {
            return $this->rateLimitResponse($key);
        }
        
        RateLimiter::hit($key, 3600);
        
        try {
            $filters = $this->validateApiFilters($request);
            
            // Get aggregated metrics for dashboard
            $metrics = [
                'sales_summary' => $this->reportDataService->getSalesCollectionsData($filters),
                'manifest_summary' => $this->reportDataService->getManifestMetrics($filters),
                'customer_summary' => $this->reportDataService->getCustomerStatistics($filters),
                'financial_summary' => $this->reportDataService->getFinancialBreakdown($filters)
            ];
            
            $this->auditService->logReportAccess(
                $request->user(), 
                'dashboard_metrics', 
                'api_external', 
                $filters,
                $request->ip(),
                $request->userAgent()
            );
            
            return $this->successResponse($metrics, 'Dashboard metrics retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve dashboard metrics', $e);
        }
    }

    /**
     * Get available filter options for API consumers
     * 
     * @group Reports
     * @authenticated
     */
    public function filterOptions(): JsonResponse
    {
        Gate::authorize('viewReports');
        
        try {
            $options = [
                'date_ranges' => [
                    'today', 'yesterday', 'last_7_days', 'last_30_days',
                    'this_month', 'last_month', 'this_year', 'custom'
                ],
                'manifest_types' => ['all', 'air', 'sea'],
                'offices' => $this->reportDataService->getAvailableOffices(),
                'export_formats' => ['json', 'csv'],
                'pagination' => [
                    'max_limit' => 1000,
                    'default_limit' => 100
                ]
            ];
            
            return $this->successResponse($options, 'Filter options retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve filter options', $e);
        }
    }

    /**
     * Validate API request filters
     */
    protected function validateApiFilters(Request $request): array
    {
        $validated = $request->validate([
            'date_range' => 'nullable|string|in:today,yesterday,last_7_days,last_30_days,this_month,last_month,this_year,custom',
            'start_date' => 'nullable|date|required_if:date_range,custom',
            'end_date' => 'nullable|date|after_or_equal:start_date|required_if:date_range,custom',
            'manifest_type' => 'nullable|string|in:all,air,sea',
            'office_id' => 'nullable|integer|exists:offices,id',
            'customer_id' => 'nullable|integer|exists:users,id',
            'status' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:1000',
            'page' => 'nullable|integer|min:1',
            'format' => 'nullable|string|in:json,csv'
        ]);

        // Set API-specific defaults
        $validated['date_range'] = $validated['date_range'] ?? 'last_30_days';
        $validated['manifest_type'] = $validated['manifest_type'] ?? 'all';
        $validated['limit'] = $validated['limit'] ?? 100;
        $validated['page'] = $validated['page'] ?? 1;
        $validated['format'] = $validated['format'] ?? 'json';

        return $validated;
    }

    /**
     * Return standardized success response
     */
    protected function successResponse(array $data, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'generated_at' => now()->toISOString(),
                'api_version' => '1.0'
            ]
        ]);
    }

    /**
     * Return standardized error response
     */
    protected function errorResponse(string $message, \Exception $e = null): JsonResponse
    {
        if ($e) {
            Log::error('Report API error', [
                'user_id' => auth()->id(),
                'message' => $message,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') && $e ? $e->getMessage() : 'Internal server error'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Return rate limit response
     */
    protected function rateLimitResponse(string $key): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => RateLimiter::availableIn($key)
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }
}