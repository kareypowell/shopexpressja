<?php

namespace App\Http\Controllers;

use App\Models\ReportExportJob;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    protected $exportService;

    public function __construct(ReportExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Get export job status
     */
    public function status(Request $request, string $jobId)
    {
        $job = ReportExportJob::where('id', $jobId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$job) {
            return response()->json([
                'error' => 'Export job not found'
            ], 404);
        }

        $status = $this->exportService->getExportStatus($jobId);

        return response()->json($status);
    }

    /**
     * Download completed export file
     */
    public function download(Request $request, string $jobId)
    {
        $token = $request->get('token');
        
        $job = ReportExportJob::where('id', $jobId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$job) {
            abort(404, 'Export job not found');
        }

        if ($job->status !== 'completed' || !$job->file_path) {
            abort(404, 'Export file not available');
        }

        // Verify download token
        $expectedToken = hash_hmac('sha256', $job->id . $job->file_path, config('app.key'));
        if (!hash_equals($expectedToken, $token)) {
            abort(403, 'Invalid download token');
        }

        if (!Storage::disk('local')->exists($job->file_path)) {
            abort(404, 'Export file not found');
        }

        $filename = $this->generateDownloadFilename($job);
        
        return $this->streamFile($job->file_path, $filename);
    }

    /**
     * Download file directly (for sync exports)
     */
    public function downloadDirect(Request $request)
    {
        $encryptedPath = $request->get('path');
        $expires = $request->get('expires');

        // Check if link has expired
        if ($expires && now()->timestamp > $expires) {
            abort(403, 'Download link has expired');
        }

        try {
            $filePath = decrypt($encryptedPath);
        } catch (\Exception $e) {
            abort(403, 'Invalid download link');
        }

        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'File not found');
        }

        $filename = basename($filePath);
        
        return $this->streamFile($filePath, $filename);
    }

    /**
     * List user's export jobs
     */
    public function index(Request $request)
    {
        $jobs = ReportExportJob::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'jobs' => $jobs->items(),
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total()
                ]
            ]);
        }

        return view('reports.exports.index', compact('jobs'));
    }

    /**
     * Cancel a queued export job
     */
    public function cancel(Request $request, string $jobId)
    {
        $job = ReportExportJob::where('id', $jobId)
            ->where('user_id', Auth::id())
            ->where('status', 'queued')
            ->first();

        if (!$job) {
            return response()->json([
                'error' => 'Export job not found or cannot be cancelled'
            ], 404);
        }

        $job->update([
            'status' => 'cancelled',
            'completed_at' => now()
        ]);

        return response()->json([
            'message' => 'Export job cancelled successfully'
        ]);
    }

    /**
     * Delete an export job and its file
     */
    public function delete(Request $request, string $jobId)
    {
        $job = ReportExportJob::where('id', $jobId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$job) {
            return response()->json([
                'error' => 'Export job not found'
            ], 404);
        }

        // Delete file if it exists
        if ($job->file_path && Storage::disk('local')->exists($job->file_path)) {
            Storage::disk('local')->delete($job->file_path);
        }

        // Delete job record
        $job->delete();

        return response()->json([
            'message' => 'Export job deleted successfully'
        ]);
    }

    /**
     * Get export statistics for user
     */
    public function statistics(Request $request)
    {
        $userId = Auth::id();
        
        $stats = [
            'total_exports' => ReportExportJob::where('user_id', $userId)->count(),
            'completed_exports' => ReportExportJob::where('user_id', $userId)
                ->where('status', 'completed')->count(),
            'failed_exports' => ReportExportJob::where('user_id', $userId)
                ->where('status', 'failed')->count(),
            'pending_exports' => ReportExportJob::where('user_id', $userId)
                ->whereIn('status', ['queued', 'processing'])->count(),
            'recent_exports' => ReportExportJob::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'report_type', 'export_format', 'status', 'created_at'])
        ];

        return response()->json($stats);
    }

    /**
     * Stream file download
     */
    protected function streamFile(string $filePath, string $filename): StreamedResponse
    {
        $mimeType = $this->getMimeType($filePath);
        $fileSize = Storage::disk('local')->size($filePath);

        return response()->stream(function () use ($filePath) {
            $stream = Storage::disk('local')->readStream($filePath);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => $fileSize,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Generate user-friendly download filename
     */
    protected function generateDownloadFilename(ReportExportJob $job): string
    {
        $reportType = str_replace('_', '-', $job->report_type);
        $format = strtolower($job->export_format);
        $date = $job->created_at->format('Y-m-d');
        
        return "shipshark-{$reportType}-{$date}.{$format}";
    }

    /**
     * Get MIME type for file
     */
    protected function getMimeType(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        switch(strtolower($extension)) {
            case 'pdf':
                return 'application/pdf';
            case 'csv':
                return 'text/csv';
            case 'xlsx':
                return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            case 'xls':
                return 'application/vnd.ms-excel';
            default:
                return 'application/octet-stream';
        }
    }
}