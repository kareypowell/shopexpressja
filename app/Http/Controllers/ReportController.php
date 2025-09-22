<?php

namespace App\Http\Controllers;

use App\Services\BusinessReportService;
use App\Services\ReportDataService;
use App\Services\ReportExportService;
use App\Services\ReportAuditService;
use App\Http\Livewire\Reports\ReportDashboard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    protected BusinessReportService $businessReportService;
    protected ReportDataService $reportDataService;
    protected ReportExportService $exportService;
    protected ReportAuditService $auditService;

    public function __construct(
        BusinessReportService $businessReportService,
        ReportDataService $reportDataService,
        ReportExportService $exportService,
        ReportAuditService $auditService
    ) {
        $this->businessReportService = $businessReportService;
        $this->reportDataService = $reportDataService;
        $this->exportService = $exportService;
        $this->auditService = $auditService;
        
        // Apply middleware
        $this->middleware(['auth', 'verified']);
        $this->middleware('throttle:reports')->only(['apiSalesReport', 'apiManifestReport', 'apiCustomerReport']);
    }

    /**
     * Display the main reports dashboard
     */
    public function index()
    {
        Gate::authorize('report.viewAny');
        
        $this->auditService->logReportAccess(auth()->user(), 'dashboard', 'view');
        
        return view('reports.index');
    }

    /**
     * Display the sales and collections report
     */
    public function salesReport(Request $request)
    {
        Gate::authorize('report.viewSalesReports');
        
        $this->auditService->logReportAccess(auth()->user(), 'sales_report', 'view', $request->all());
        
        return view('reports.sales', [
            'reportType' => 'sales',
            'title' => 'Sales & Collections Report'
        ]);
    }

    /**
     * Display the manifest performance report
     */
    public function manifestReport(Request $request)
    {
        Gate::authorize('report.viewManifestReports');
        
        $this->auditService->logReportAccess(auth()->user(), 'manifest_report', 'view', $request->all());
        
        return view('reports.manifests', [
            'reportType' => 'manifests',
            'title' => 'Manifest Performance Report'
        ]);
    }

    /**
     * Display customer-specific reports
     */
    public function customerReport(Request $request)
    {
        Gate::authorize('report.viewCustomerReports');
        
        $this->auditService->logReportAccess(auth()->user(), 'customer_report', 'view', $request->all());
        
        return view('reports.customers', [
            'reportType' => 'customers',
            'title' => 'Customer Analytics Report'
        ]);
    }

    /**
     * Display financial summary report
     */
    public function financialReport(Request $request)
    {
        Gate::authorize('report.viewSalesReports');
        
        $this->auditService->logReportAccess(auth()->user(), 'financial_report', 'view', $request->all());
        
        return view('reports.financial', [
            'reportType' => 'financial',
            'title' => 'Financial Summary Report'
        ]);
    }

    /**
     * API endpoint for sales and collections data
     */
    public function apiSalesReport(Request $request): JsonResponse
    {
        Gate::authorize('report.viewSalesReports');
        
        // Rate limiting
        $key = 'api-sales-report:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        RateLimiter::hit($key, 60);
        
        try {
            $filters = $this->validateReportFilters($request);
            
            $data = $this->businessReportService->generateSalesCollectionsReport($filters);
            
            $this->auditService->logReportAccess(auth()->user(), 'sales_report', 'api_access', $filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'generated_at' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Sales report API error', [
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate sales report'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API endpoint for manifest performance data
     */
    public function apiManifestReport(Request $request): JsonResponse
    {
        Gate::authorize('report.viewManifestReports');
        
        // Rate limiting
        $key = 'api-manifest-report:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        RateLimiter::hit($key, 60);
        
        try {
            $filters = $this->validateReportFilters($request);
            
            $data = $this->businessReportService->generateManifestPerformanceReport($filters);
            
            $this->auditService->logReportAccess(auth()->user(), 'manifest_report', 'api_access', $filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'generated_at' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Manifest report API error', [
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate manifest report'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API endpoint for customer analytics data
     */
    public function apiCustomerReport(Request $request): JsonResponse
    {
        Gate::authorize('report.viewCustomerReports');
        
        // Rate limiting
        $key = 'api-customer-report:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        RateLimiter::hit($key, 60);
        
        try {
            $filters = $this->validateReportFilters($request);
            
            $data = $this->businessReportService->generateCustomerAnalyticsReport($filters);
            
            $this->auditService->logReportAccess(auth()->user(), 'customer_report', 'api_access', $filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'generated_at' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Customer report API error', [
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate customer report'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API endpoint for financial summary data
     */
    public function apiFinancialSummary(Request $request): JsonResponse
    {
        Gate::authorize('report.viewSalesReports');
        
        try {
            $filters = $this->validateReportFilters($request);
            
            $data = $this->businessReportService->generateFinancialSummaryReport($filters);
            
            $this->auditService->logReportAccess(auth()->user(), 'financial_summary', 'api_access', $filters);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'generated_at' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Financial summary API error', [
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate financial summary'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get available report filters and options
     */
    public function apiReportOptions(): JsonResponse
    {
        Gate::authorize('report.viewAny');
        
        try {
            $options = [
                'date_ranges' => [
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    'last_7_days' => 'Last 7 Days',
                    'last_30_days' => 'Last 30 Days',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year' => 'This Year',
                    'custom' => 'Custom Range'
                ],
                'manifest_types' => [
                    'all' => 'All Types',
                    'air' => 'Air Freight',
                    'sea' => 'Sea Freight'
                ],
                'offices' => $this->reportDataService->getAvailableOffices(),
                'export_formats' => [
                    'pdf' => 'PDF',
                    'csv' => 'CSV'
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $options
            ]);
            
        } catch (\Exception $e) {
            Log::error('Report options API error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to load report options'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate report filter parameters
     */
    protected function validateReportFilters(Request $request): array
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
            'page' => 'nullable|integer|min:1'
        ]);

        // Set defaults
        $validated['date_range'] = $validated['date_range'] ?? 'last_30_days';
        $validated['manifest_type'] = $validated['manifest_type'] ?? 'all';
        $validated['limit'] = $validated['limit'] ?? 100;
        $validated['page'] = $validated['page'] ?? 1;

        return $validated;
    }
}