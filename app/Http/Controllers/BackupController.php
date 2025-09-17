<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin');
    }

    /**
     * Download a backup file with security checks and logging
     */
    public function download(Request $request, Backup $backup)
    {
        try {
            // Verify the backup exists and is completed
            if ($backup->status !== 'completed') {
                abort(404, 'Backup file not available for download.');
            }

            // Verify the physical file exists
            if (!Storage::exists($backup->file_path)) {
                Log::error('Backup file not found on disk', [
                    'backup_id' => $backup->id,
                    'file_path' => $backup->file_path,
                    'user_id' => auth()->id(),
                ]);
                
                abort(404, 'Backup file not found.');
            }

            // Log the download access for security auditing
            Log::info('Backup file downloaded', [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'file_path' => $backup->file_path,
                'file_size' => $backup->file_size,
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'download_time' => now(),
            ]);

            // Get the file path and determine the appropriate filename
            $filePath = Storage::path($backup->file_path);
            $downloadName = $backup->name ?: basename($backup->file_path);

            // Ensure the download name has the correct extension
            if (!pathinfo($downloadName, PATHINFO_EXTENSION)) {
                $extension = pathinfo($backup->file_path, PATHINFO_EXTENSION);
                if ($extension) {
                    $downloadName .= '.' . $extension;
                }
            }

            // Return a streamed response for large files
            return Response::download($filePath, $downloadName, [
                'Content-Type' => $this->getMimeType($backup->type),
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to download backup file', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
            ]);

            abort(500, 'Failed to download backup file. Please try again.');
        }
    }

    /**
     * Get the appropriate MIME type for the backup file
     */
    private function getMimeType(string $backupType): string
    {
        switch ($backupType) {
            case 'database':
                return 'application/sql';
            case 'files':
                return 'application/zip';
            case 'full':
                return 'application/zip';
            default:
                return 'application/octet-stream';
        }
    }
}