<?php

namespace App\Http\Controllers;

use App\Models\SavedReportFilter;
use App\Services\ReportConfigurationService;
use App\Services\ReportAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SavedReportFilterController extends Controller
{
    protected ReportConfigurationService $configService;
    protected ReportAuditService $auditService;

    public function __construct(
        ReportConfigurationService $configService,
        ReportAuditService $auditService
    ) {
        $this->configService = $configService;
        $this->auditService = $auditService;
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display a listing of saved filters
     */
    public function index(Request $request)
    {
        Gate::authorize('report.manageSavedFilters');
        
        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->apiIndex($request);
        }
        
        $reportType = $request->get('report_type');
        
        $userFilters = $this->configService->getUserSavedFilters(auth()->user(), $reportType);
        $sharedFilters = $this->configService->getSharedFilters($reportType ?: 'sales', auth()->user());
        
        $this->auditService->logReportAccess(
            auth()->user(), 
            'saved_filters_list', 
            'view', 
            ['report_type' => $reportType]
        );
        
        return view('reports.filters.index', [
            'userFilters' => $userFilters,
            'sharedFilters' => $sharedFilters,
            'reportType' => $reportType,
            'availableReportTypes' => [
                'sales' => 'Sales & Collections',
                'manifest' => 'Manifest Performance', 
                'customer' => 'Customer Analytics',
                'financial' => 'Financial Summary'
            ]
        ]);
    }

    /**
     * API version of index method
     */
    protected function apiIndex(Request $request): JsonResponse
    {
        $reportType = $request->get('report_type');
        
        $userFilters = $this->configService->getUserSavedFilters(auth()->user(), $reportType);
        $sharedFilters = $this->configService->getSharedFilters($reportType ?: 'sales', auth()->user());
        
        return response()->json([
            'success' => true,
            'data' => [
                'user_filters' => $userFilters,
                'shared_filters' => $sharedFilters
            ]
        ]);
    }

    /**
     * Store a newly created saved filter
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('report.manageSavedFilters');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|string|in:sales,manifest,customer,financial',
            'filter_config' => 'required|array',
            'is_shared' => 'boolean',
            'shared_with_roles' => 'nullable|array',
            'shared_with_roles.*' => 'string|in:admin,superadmin'
        ]);

        try {
            // Validate filter configuration
            $errors = $this->configService->validateFilterConfig(
                $validated['filter_config'], 
                $validated['report_type']
            );
            
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid filter configuration',
                    'errors' => $errors
                ], 422);
            }

            // Check sharing permissions
            if ($validated['is_shared'] ?? false) {
                Gate::authorize('report.shareSavedFilters');
            }

            $filter = $this->configService->createSavedFilter(auth()->user(), $validated);

            $this->auditService->logReportAccess(
                auth()->user(), 
                'saved_filter_create', 
                'create', 
                ['filter_id' => $filter->id, 'name' => $filter->name]
            );

            return response()->json([
                'success' => true,
                'message' => 'Saved filter created successfully',
                'data' => $filter
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create saved filter', [
                'user_id' => auth()->id(),
                'data' => $validated,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create saved filter'
            ], 500);
        }
    }

    /**
     * Display the specified saved filter
     */
    public function show(SavedReportFilter $filter): JsonResponse
    {
        Gate::authorize('report.manageSavedFilters');
        
        // Check if user can access this filter
        if ($filter->user_id !== auth()->id() && !$filter->is_shared) {
            return response()->json([
                'success' => false,
                'message' => 'Filter not found or access denied'
            ], 404);
        }

        $this->auditService->logReportAccess(
            auth()->user(), 
            'saved_filter_view', 
            'view', 
            ['filter_id' => $filter->id]
        );

        return response()->json([
            'success' => true,
            'data' => $filter->load('user:id,name')
        ]);
    }

    /**
     * Update the specified saved filter
     */
    public function update(Request $request, SavedReportFilter $filter): JsonResponse
    {
        Gate::authorize('report.manageSavedFilters');
        
        // Check if user owns this filter
        if ($filter->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update your own filters'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'filter_config' => 'required|array',
            'is_shared' => 'boolean',
            'shared_with_roles' => 'nullable|array',
            'shared_with_roles.*' => 'string|in:admin,superadmin'
        ]);

        try {
            // Validate filter configuration
            $errors = $this->configService->validateFilterConfig(
                $validated['filter_config'], 
                $filter->report_type
            );
            
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid filter configuration',
                    'errors' => $errors
                ], 422);
            }

            // Check sharing permissions
            if ($validated['is_shared'] ?? false) {
                Gate::authorize('report.shareSavedFilters');
            }

            $originalData = $filter->toArray();
            $updatedFilter = $this->configService->updateSavedFilter($filter, $validated);

            $this->auditService->logReportAccess(
                auth()->user(), 
                'saved_filter_update', 
                'update', 
                [
                    'filter_id' => $filter->id,
                    'changes' => $updatedFilter->getChanges(),
                    'original' => $originalData
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Saved filter updated successfully',
                'data' => $updatedFilter
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update saved filter', [
                'user_id' => auth()->id(),
                'filter_id' => $filter->id,
                'data' => $validated,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update saved filter'
            ], 500);
        }
    }

    /**
     * Remove the specified saved filter
     */
    public function destroy(SavedReportFilter $filter): JsonResponse
    {
        Gate::authorize('report.manageSavedFilters');
        
        // Check if user owns this filter
        if ($filter->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own filters'
            ], 403);
        }

        try {
            $filterData = $filter->toArray();
            $deleted = $this->configService->deleteSavedFilter($filter);

            if ($deleted) {
                $this->auditService->logReportAccess(
                    auth()->user(), 
                    'saved_filter_delete', 
                    'delete', 
                    ['filter_data' => $filterData]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Saved filter deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete saved filter'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to delete saved filter', [
                'user_id' => auth()->id(),
                'filter_id' => $filter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete saved filter'
            ], 500);
        }
    }

    /**
     * Duplicate an existing saved filter
     */
    public function duplicate(Request $request, SavedReportFilter $filter): JsonResponse
    {
        Gate::authorize('report.manageSavedFilters');
        
        // Check if user can access this filter
        if ($filter->user_id !== auth()->id() && !$filter->is_shared) {
            return response()->json([
                'success' => false,
                'message' => 'Filter not found or access denied'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255'
        ]);

        try {
            $newFilter = $this->configService->duplicateFilter(
                $filter, 
                auth()->user(), 
                $validated['name'] ?? null
            );

            $this->auditService->logReportAccess(
                auth()->user(), 
                'saved_filter_duplicate', 
                'create', 
                [
                    'original_filter_id' => $filter->id,
                    'new_filter_id' => $newFilter->id
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Filter duplicated successfully',
                'data' => $newFilter
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to duplicate saved filter', [
                'user_id' => auth()->id(),
                'filter_id' => $filter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate filter'
            ], 500);
        }
    }

    /**
     * Share a filter with specific roles
     */
    public function share(Request $request, SavedReportFilter $filter): JsonResponse
    {
        Gate::authorize('report.shareSavedFilters');
        
        // Check if user owns this filter
        if ($filter->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only share your own filters'
            ], 403);
        }

        $validated = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|in:admin,superadmin'
        ]);

        try {
            $updatedFilter = $this->configService->shareFilterWithRoles($filter, $validated['roles']);

            $this->auditService->logReportAccess(
                auth()->user(), 
                'saved_filter_share', 
                'update', 
                [
                    'filter_id' => $filter->id,
                    'shared_with_roles' => $validated['roles']
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Filter shared successfully',
                'data' => $updatedFilter
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to share saved filter', [
                'user_id' => auth()->id(),
                'filter_id' => $filter->id,
                'roles' => $validated['roles'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to share filter'
            ], 500);
        }
    }
}