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

            // Parse backup file paths
            $filePaths = $this->parseBackupFilePaths($backup->file_path);
            
            if (empty($filePaths)) {
                abort(404, 'No backup files found.');
            }

            // Verify at least one physical file exists
            $existingFiles = [];
            foreach ($filePaths as $path) {
                if (file_exists($path)) {
                    $existingFiles[] = $path;
                }
            }

            if (empty($existingFiles)) {
                Log::error('Backup files not found on disk', [
                    'backup_id' => $backup->id,
                    'file_paths' => $filePaths,
                    'user_id' => auth()->id(),
                ]);
                
                abort(404, 'Backup file not found.');
            }

            // Log the download access for security auditing
            Log::info('Backup file downloaded', [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'file_count' => count($existingFiles),
                'file_size' => $backup->file_size,
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'download_time' => now(),
            ]);

            // Handle single file download
            if (count($existingFiles) === 1) {
                $filePath = $existingFiles[0];
                $downloadName = $backup->name ?: basename($filePath);

                // Ensure the download name has the correct extension
                if (!pathinfo($downloadName, PATHINFO_EXTENSION)) {
                    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                    if ($extension) {
                        $downloadName .= '.' . $extension;
                    }
                }

                return Response::download($filePath, $downloadName, [
                    'Content-Type' => $this->getMimeType($backup->type),
                    'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ]);
            }

            // Handle multiple files - create a ZIP archive
            return $this->downloadMultipleFiles($backup, $existingFiles);

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
     * Parse backup file paths from JSON or single path
     */
    private function parseBackupFilePaths($filePath)
    {
        // Try to decode as JSON first
        $paths = json_decode($filePath, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($paths)) {
            // Handle JSON format: {"database": "path", "files": ["path1", "path2"]}
            $allPaths = [];
            
            foreach ($paths as $type => $typePaths) {
                if (is_array($typePaths)) {
                    $allPaths = array_merge($allPaths, $typePaths);
                } else {
                    $allPaths[] = $typePaths;
                }
            }
            
            return $allPaths;
        }
        
        // Handle single file path (legacy format)
        return [$filePath];
    }

    /**
     * Download multiple files as a ZIP archive
     */
    private function downloadMultipleFiles(Backup $backup, array $filePaths)
    {
        $zipName = $backup->name . '_complete.zip';
        
        return response()->streamDownload(function () use ($filePaths) {
            $zip = new \ZipArchive();
            $tempZipPath = tempnam(sys_get_temp_dir(), 'backup_download_');
            
            if ($zip->open($tempZipPath, \ZipArchive::CREATE) === TRUE) {
                foreach ($filePaths as $filePath) {
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, basename($filePath));
                    }
                }
                $zip->close();
                
                readfile($tempZipPath);
                unlink($tempZipPath);
            }
        }, $zipName, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
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