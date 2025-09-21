<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:viewAny,App\Models\AuditLog');
    }

    /**
     * Display the audit log management interface.
     */
    public function index()
    {
        return view('admin.audit-logs.index');
    }

    /**
     * Download an audit export file.
     */
    public function download(Request $request, $filename)
    {
        // Validate filename to prevent directory traversal
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
            abort(404);
        }

        // Check if file exists in exports or audit-reports directories
        $exportPath = 'exports/' . $filename;
        $reportPath = 'audit-reports/' . $filename;

        if (Storage::disk('public')->exists($exportPath)) {
            $filePath = $exportPath;
        } elseif (Storage::disk('public')->exists($reportPath)) {
            $filePath = $reportPath;
        } else {
            abort(404);
        }

        // Get file content and mime type
        $fileContent = Storage::disk('public')->get($filePath);
        $mimeType = Storage::disk('public')->mimeType($filePath);

        // Set appropriate headers for download
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($fileContent),
        ];

        return response($fileContent, 200, $headers);
    }
}