<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use App\Services\ReportAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ReportTemplateController extends Controller
{
    protected ReportAuditService $auditService;

    public function __construct(ReportAuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display a listing of report templates
     */
    public function index(Request $request)
    {
        Gate::authorize('report.viewReportTemplates');
        
        $templates = ReportTemplate::query()
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20);

        $this->auditService->logReportAccess(auth()->user(), 'template_list', 'view');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        }

        return view('reports.templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new report template
     */
    public function create()
    {
        Gate::authorize('report.createReportTemplates');
        
        return view('reports.templates.create');
    }

    /**
     * Store a newly created report template
     */
    public function store(Request $request)
    {
        Gate::authorize('report.createReportTemplates');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:report_templates,name',
            'type' => 'required|string|in:sales,manifest,customer,financial',
            'description' => 'nullable|string|max:1000',
            'template_config' => 'required|array',
            'template_config.columns' => 'required|array|min:1',
            'template_config.filters' => 'nullable|array',
            'template_config.charts' => 'nullable|array',
            'default_filters' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        try {
            $template = ReportTemplate::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'template_config' => $validated['template_config'],
                'default_filters' => $validated['default_filters'] ?? [],
                'created_by' => auth()->id(),
                'is_active' => $validated['is_active'] ?? true
            ]);

            $this->auditService->logReportAccess(
                auth()->user(), 
                'template_create', 
                'create', 
                ['template_id' => $template->id, 'name' => $template->name]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report template created successfully',
                    'data' => $template
                ], 201);
            }

            return redirect()->route('reports.templates.show', $template)
                           ->with('success', 'Report template created successfully');

        } catch (\Exception $e) {
            Log::error('Report template creation failed', [
                'user_id' => auth()->id(),
                'data' => $validated,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create report template'
                ], 500);
            }

            return back()->withInput()
                        ->withErrors(['error' => 'Failed to create report template']);
        }
    }

    /**
     * Display the specified report template
     */
    public function show(ReportTemplate $template)
    {
        Gate::authorize('report.viewReportTemplates');
        
        $this->auditService->logReportAccess(
            auth()->user(), 
            'template_view', 
            'view', 
            ['template_id' => $template->id]
        );

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $template->load('creator')
            ]);
        }

        return view('reports.templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified report template
     */
    public function edit(ReportTemplate $template)
    {
        Gate::authorize('report.updateReportTemplates');
        
        return view('reports.templates.edit', compact('template'));
    }

    /**
     * Update the specified report template
     */
    public function update(Request $request, ReportTemplate $template)
    {
        Gate::authorize('report.updateReportTemplates');
        
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('report_templates', 'name')->ignore($template->id)
            ],
            'type' => 'required|string|in:sales,manifest,customer,financial',
            'description' => 'nullable|string|max:1000',
            'template_config' => 'required|array',
            'template_config.columns' => 'required|array|min:1',
            'template_config.filters' => 'nullable|array',
            'template_config.charts' => 'nullable|array',
            'default_filters' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        try {
            $originalData = $template->toArray();
            
            $template->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'template_config' => $validated['template_config'],
                'default_filters' => $validated['default_filters'] ?? [],
                'is_active' => $validated['is_active'] ?? true
            ]);

            $this->auditService->logReportAccess(
                auth()->user(), 
                'template_update', 
                'update', 
                [
                    'template_id' => $template->id,
                    'changes' => $template->getChanges(),
                    'original' => $originalData
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report template updated successfully',
                    'data' => $template->fresh()
                ]);
            }

            return redirect()->route('reports.templates.show', $template)
                           ->with('success', 'Report template updated successfully');

        } catch (\Exception $e) {
            Log::error('Report template update failed', [
                'user_id' => auth()->id(),
                'template_id' => $template->id,
                'data' => $validated,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update report template'
                ], 500);
            }

            return back()->withInput()
                        ->withErrors(['error' => 'Failed to update report template']);
        }
    }

    /**
     * Remove the specified report template
     */
    public function destroy(ReportTemplate $template)
    {
        Gate::authorize('report.deleteReportTemplates');
        
        try {
            $templateData = $template->toArray();
            
            $template->delete();

            $this->auditService->logReportAccess(
                auth()->user(), 
                'template_delete', 
                'delete', 
                ['template_data' => $templateData]
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report template deleted successfully'
                ]);
            }

            return redirect()->route('reports.templates.index')
                           ->with('success', 'Report template deleted successfully');

        } catch (\Exception $e) {
            Log::error('Report template deletion failed', [
                'user_id' => auth()->id(),
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete report template'
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to delete report template']);
        }
    }

    /**
     * Duplicate an existing report template
     */
    public function duplicate(ReportTemplate $template)
    {
        Gate::authorize('report.createReportTemplates');
        
        try {
            $newTemplate = $template->replicate();
            $newTemplate->name = $template->name . ' (Copy)';
            $newTemplate->created_by = auth()->id();
            $newTemplate->save();

            $this->auditService->logReportAccess(
                auth()->user(), 
                'template_duplicate', 
                'create', 
                [
                    'original_template_id' => $template->id,
                    'new_template_id' => $newTemplate->id
                ]
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report template duplicated successfully',
                    'data' => $newTemplate
                ]);
            }

            return redirect()->route('reports.templates.edit', $newTemplate)
                           ->with('success', 'Report template duplicated successfully');

        } catch (\Exception $e) {
            Log::error('Report template duplication failed', [
                'user_id' => auth()->id(),
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to duplicate report template'
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to duplicate report template']);
        }
    }
}