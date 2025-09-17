<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class FileBackupHandler
{
    private BackupConfig $config;

    public function __construct(BackupConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Backup a directory to a compressed archive
     *
     * @param string $directory The directory to backup
     * @param string|null $archiveName Custom archive name (optional)
     * @return string Path to the created archive
     * @throws Exception
     */
    public function backupDirectory(string $directory, string $archiveName = null): string
    {
        if (!File::exists($directory)) {
            throw new Exception("Directory does not exist: {$directory}");
        }

        if (!File::isDirectory($directory)) {
            throw new Exception("Path is not a directory: {$directory}");
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $directoryName = basename($directory);
        $archiveName = $archiveName ?: "{$directoryName}_backup_{$timestamp}.zip";
        
        // Ensure archive name ends with .zip
        if (!str_ends_with($archiveName, '.zip')) {
            $archiveName .= '.zip';
        }

        $backupPath = $this->config->getStoragePath();
        $archivePath = $backupPath . '/' . $archiveName;

        // Ensure backup directory exists
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $zip = new ZipArchive();
        $result = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== TRUE) {
            throw new Exception("Cannot create archive: {$archivePath}. Error code: {$result}");
        }

        // Set compression level
        $compressionLevel = $this->config->getCompressionLevel();
        $zip->setCompressionName('*', ZipArchive::CM_DEFLATE, $compressionLevel);

        try {
            $this->addDirectoryToZip($zip, $directory, $directoryName);
            $zip->close();

            // Verify the archive was created successfully
            if (!File::exists($archivePath) || File::size($archivePath) === 0) {
                throw new Exception("Archive creation failed or resulted in empty file");
            }

            Log::info("File backup created successfully", [
                'directory' => $directory,
                'archive_path' => $archivePath,
                'archive_size' => File::size($archivePath)
            ]);

            return $archivePath;

        } catch (Exception $e) {
            if (isset($zip) && $zip instanceof ZipArchive) {
                $zip->close();
            }
            
            // Clean up failed archive
            if (File::exists($archivePath)) {
                File::delete($archivePath);
            }
            
            throw new Exception("Failed to create archive: " . $e->getMessage());
        }
    }

    /**
     * Validate archive integrity
     *
     * @param string $archivePath Path to the archive file
     * @return bool True if archive is valid
     */
    public function validateArchive(string $archivePath): bool
    {
        try {
            if (!File::exists($archivePath)) {
                Log::warning("Archive validation failed: file does not exist", ['path' => $archivePath]);
                return false;
            }

            if (File::size($archivePath) === 0) {
                Log::warning("Archive validation failed: file is empty", ['path' => $archivePath]);
                return false;
            }

            $zip = new ZipArchive();
            $result = $zip->open($archivePath, ZipArchive::CHECKCONS);

            if ($result !== TRUE) {
                Log::warning("Archive validation failed: cannot open archive", [
                    'path' => $archivePath,
                    'error_code' => $result
                ]);
                return false;
            }

            // Check if archive has any files
            $numFiles = $zip->numFiles;
            $zip->close();

            if ($numFiles === 0) {
                Log::warning("Archive validation failed: archive is empty", ['path' => $archivePath]);
                return false;
            }

            Log::info("Archive validation successful", [
                'path' => $archivePath,
                'num_files' => $numFiles,
                'size' => File::size($archivePath)
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Archive validation error", [
                'path' => $archivePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Extract archive to destination directory
     *
     * @param string $archivePath Path to the archive file
     * @param string $destination Destination directory
     * @return bool True if extraction successful
     */
    public function extractArchive(string $archivePath, string $destination): bool
    {
        try {
            if (!$this->validateArchive($archivePath)) {
                throw new Exception("Archive validation failed");
            }

            // Ensure destination directory exists
            if (!File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }

            $zip = new ZipArchive();
            $result = $zip->open($archivePath);

            if ($result !== TRUE) {
                throw new Exception("Cannot open archive for extraction. Error code: {$result}");
            }

            $extractResult = $zip->extractTo($destination);
            $zip->close();

            if (!$extractResult) {
                throw new Exception("Failed to extract archive contents");
            }

            Log::info("Archive extracted successfully", [
                'archive_path' => $archivePath,
                'destination' => $destination
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Archive extraction failed", [
                'archive_path' => $archivePath,
                'destination' => $destination,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get archive contents list
     *
     * @param string $archivePath Path to the archive file
     * @return array List of files in the archive
     */
    public function getArchiveContents(string $archivePath): array
    {
        try {
            if (!$this->validateArchive($archivePath)) {
                return [];
            }

            $zip = new ZipArchive();
            $result = $zip->open($archivePath);

            if ($result !== TRUE) {
                Log::warning("Cannot open archive to get contents", [
                    'path' => $archivePath,
                    'error_code' => $result
                ]);
                return [];
            }

            $contents = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat !== false) {
                    $contents[] = [
                        'name' => $stat['name'],
                        'size' => $stat['size'],
                        'compressed_size' => $stat['comp_size'],
                        'mtime' => $stat['mtime']
                    ];
                }
            }

            $zip->close();
            return $contents;

        } catch (Exception $e) {
            Log::error("Failed to get archive contents", [
                'path' => $archivePath,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Create pre-restore backup of directories
     *
     * @param array $directories List of directories to backup
     * @return string Path to the pre-restore backup archive
     * @throws Exception
     */
    public function createPreRestoreBackup(array $directories): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $archiveName = "pre_restore_backup_{$timestamp}.zip";
        $backupPath = $this->config->getStoragePath();
        $archivePath = $backupPath . '/' . $archiveName;

        // Ensure backup directory exists
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $zip = new ZipArchive();
        $result = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== TRUE) {
            throw new Exception("Cannot create pre-restore backup archive. Error code: {$result}");
        }

        // Set compression level
        $compressionLevel = $this->config->getCompressionLevel();
        $zip->setCompressionName('*', ZipArchive::CM_DEFLATE, $compressionLevel);

        try {
            foreach ($directories as $directory) {
                if (File::exists($directory) && File::isDirectory($directory)) {
                    $directoryName = basename($directory);
                    $this->addDirectoryToZip($zip, $directory, $directoryName);
                }
            }

            $zip->close();

            if (!File::exists($archivePath) || File::size($archivePath) === 0) {
                throw new Exception("Pre-restore backup creation failed");
            }

            Log::info("Pre-restore backup created successfully", [
                'directories' => $directories,
                'archive_path' => $archivePath,
                'archive_size' => File::size($archivePath)
            ]);

            return $archivePath;

        } catch (Exception $e) {
            if (isset($zip) && $zip instanceof ZipArchive) {
                $zip->close();
            }
            
            // Clean up failed archive
            if (File::exists($archivePath)) {
                File::delete($archivePath);
            }
            
            throw new Exception("Failed to create pre-restore backup: " . $e->getMessage());
        }
    }

    /**
     * Recursively add directory contents to ZIP archive
     *
     * @param ZipArchive $zip The ZIP archive object
     * @param string $directory Directory to add
     * @param string $localPath Local path within the archive
     * @return void
     */
    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $localPath): void
    {
        $files = File::allFiles($directory);
        
        // If directory is empty, add an empty directory entry
        if (empty($files)) {
            $zip->addEmptyDir($localPath . '/');
            return;
        }
        
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $localPath . '/' . $file->getRelativePathname();
            
            // Add file to archive
            $zip->addFile($filePath, $relativePath);
        }

        // Add empty directories
        $directories = File::directories($directory);
        foreach ($directories as $dir) {
            $relativeDirPath = $localPath . '/' . basename($dir) . '/';
            $zip->addEmptyDir($relativeDirPath);
            
            // Recursively add subdirectory contents
            $this->addDirectoryToZip($zip, $dir, $localPath . '/' . basename($dir));
        }
    }

    /**
     * Get archive file size in bytes
     *
     * @param string $archivePath Path to the archive file
     * @return int File size in bytes
     */
    public function getArchiveSize(string $archivePath): int
    {
        if (!File::exists($archivePath)) {
            return 0;
        }

        return File::size($archivePath);
    }

    /**
     * Delete archive file
     *
     * @param string $archivePath Path to the archive file
     * @return bool True if deletion successful
     */
    public function deleteArchive(string $archivePath): bool
    {
        try {
            if (File::exists($archivePath)) {
                File::delete($archivePath);
                Log::info("Archive deleted successfully", ['path' => $archivePath]);
                return true;
            }
            return true; // File doesn't exist, consider it deleted
        } catch (Exception $e) {
            Log::error("Failed to delete archive", [
                'path' => $archivePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}