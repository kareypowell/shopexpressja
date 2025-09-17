<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupHandler
{
    private BackupConfig $config;

    public function __construct(BackupConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Create a MySQL database dump with timestamp filename generation
     *
     * @param string|null $filename Custom filename (optional)
     * @return string Path to the created dump file
     * @throws Exception
     */
    public function createDump(string $filename = null): string
    {
        $filename = $filename ?: $this->generateTimestampedFilename();
        $backupPath = $this->config->getStoragePath();
        $fullPath = $backupPath . '/' . $filename;

        // Ensure backup directory exists
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $dbConfig = config('database.connections.' . config('database.default'));
        
        $command = $this->buildMysqldumpCommand($dbConfig, $fullPath);
        
        Log::info('Starting database backup', [
            'filename' => $filename,
            'path' => $fullPath,
            'command' => $this->sanitizeCommandForLogging($command)
        ]);

        $result = $this->executeCommand($command);

        if ($result['return_code'] !== 0) {
            $errorOutput = implode("\n", $result['output']);
            
            // Provide specific error messages for common issues
            if ($result['return_code'] === 127) {
                $errorMessage = 'mysqldump command not found. Please install MySQL client tools or ensure mysqldump is in your PATH.';
            } elseif (strpos($errorOutput, 'Access denied') !== false) {
                $errorMessage = 'Database access denied. Please check your database credentials.';
            } elseif (strpos($errorOutput, 'Unknown database') !== false) {
                $errorMessage = 'Database not found. Please check your database name configuration.';
            } else {
                $errorMessage = 'Database backup failed: ' . $errorOutput;
            }
            
            Log::error($errorMessage, [
                'return_code' => $result['return_code'],
                'filename' => $filename,
                'output' => $errorOutput
            ]);
            throw new Exception($errorMessage);
        }

        // Verify the dump was created and has content
        if (!file_exists($fullPath) || filesize($fullPath) === 0) {
            throw new Exception('Database dump file was not created or is empty');
        }

        Log::info('Database backup completed successfully', [
            'filename' => $filename,
            'size' => $this->getDumpSize($fullPath)
        ]);

        return $fullPath;
    }

    /**
     * Execute command (can be overridden for testing)
     *
     * @param string $command Command to execute
     * @return array Array with 'output' and 'return_code' keys
     */
    protected function executeCommand(string $command): array
    {
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        return [
            'output' => $output,
            'return_code' => $returnCode
        ];
    }

    /**
     * Validate dump file integrity
     *
     * @param string $filePath Path to the dump file
     * @return bool True if dump is valid
     */
    public function validateDump(string $filePath): bool
    {
        try {
            // Check if file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                Log::warning('Dump file does not exist or is not readable', ['path' => $filePath]);
                return false;
            }

            // Check if file has content
            if (filesize($filePath) === 0) {
                Log::warning('Dump file is empty', ['path' => $filePath]);
                return false;
            }

            // Read first few lines to validate SQL dump format
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                Log::warning('Cannot open dump file for reading', ['path' => $filePath]);
                return false;
            }

            $validHeaders = false;
            $lineCount = 0;
            
            while (($line = fgets($handle)) !== false && $lineCount < 20) {
                $line = trim($line);
                
                // Look for mysqldump header indicators (MySQL or MariaDB)
                if (strpos($line, '-- MySQL dump') !== false || 
                    strpos($line, '-- MariaDB dump') !== false ||
                    strpos($line, '-- Dump completed on') !== false ||
                    strpos($line, 'CREATE TABLE') !== false ||
                    strpos($line, 'INSERT INTO') !== false) {
                    $validHeaders = true;
                    break;
                }
                
                $lineCount++;
            }
            
            fclose($handle);

            if (!$validHeaders) {
                Log::warning('Dump file does not contain valid MySQL/MariaDB dump headers', ['path' => $filePath]);
                return false;
            }

            // Additional validation: check for common SQL dump patterns
            $content = file_get_contents($filePath, false, null, 0, 2048); // Read first 2KB
            
            if (strpos($content, 'SET @@') === false && 
                strpos($content, 'CREATE') === false && 
                strpos($content, 'INSERT') === false) {
                Log::warning('Dump file does not contain expected SQL content', ['path' => $filePath]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('Error validating dump file', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get dump file size in bytes
     *
     * @param string $filePath Path to the dump file
     * @return int File size in bytes
     */
    public function getDumpSize(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        return filesize($filePath);
    }

    /**
     * Generate timestamped filename for backup
     *
     * @return string Filename with timestamp
     */
    private function generateTimestampedFilename(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $dbName = config('database.connections.' . config('database.default') . '.database');
        
        return "database_backup_{$dbName}_{$timestamp}.sql";
    }

    /**
     * Build mysqldump command with proper options
     *
     * @param array $dbConfig Database configuration
     * @param string $outputPath Output file path
     * @return string Complete mysqldump command
     */
    private function buildMysqldumpCommand(array $dbConfig, string $outputPath): string
    {
        // Try to find mysqldump in common locations
        $mysqldumpPath = $this->findMysqldumpPath();
        $command = $mysqldumpPath;
        
        // Connection parameters
        $command .= ' --host=' . escapeshellarg($dbConfig['host']);
        $command .= ' --port=' . escapeshellarg($dbConfig['port'] ?? 3306);
        $command .= ' --user=' . escapeshellarg($dbConfig['username']);
        
        if (!empty($dbConfig['password'])) {
            $command .= ' --password=' . escapeshellarg($dbConfig['password']);
        }

        // Backup options from config
        if ($this->config->isDatabaseSingleTransaction()) {
            $command .= ' --single-transaction';
        }
        
        if ($this->config->includeDatabaseRoutines()) {
            $command .= ' --routines';
        }
        
        if ($this->config->includeDatabaseTriggers()) {
            $command .= ' --triggers';
        }

        // Additional recommended options
        $command .= ' --opt'; // Equivalent to --add-drop-table --add-locks --create-options --disable-keys --extended-insert --lock-tables --quick --set-charset
        $command .= ' --hex-blob'; // Dump binary strings in hexadecimal format
        $command .= ' --default-character-set=utf8mb4';
        
        // Database name
        $command .= ' ' . escapeshellarg($dbConfig['database']);
        
        // Output redirection
        $command .= ' > ' . escapeshellarg($outputPath);

        return $command;
    }

    /**
     * Find mysqldump executable path
     *
     * @return string Path to mysqldump
     * @throws Exception If mysqldump is not found
     */
    private function findMysqldumpPath(): string
    {
        // Common paths where mysqldump might be located
        $commonPaths = [
            'mysqldump', // In PATH
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump', // Homebrew on Apple Silicon
            '/usr/local/mysql/bin/mysqldump',
            '/Applications/XAMPP/xamppfiles/bin/mysqldump', // XAMPP on macOS
            '/Applications/MAMP/Library/bin/mysqldump', // MAMP on macOS
        ];

        foreach ($commonPaths as $path) {
            if ($this->commandExists($path)) {
                return $path;
            }
        }

        $installInstructions = $this->getMysqldumpInstallInstructions();
        throw new Exception("mysqldump command not found. Please install MySQL client tools or ensure mysqldump is in your PATH.\n\n" . $installInstructions);
    }

    /**
     * Check if a command exists
     *
     * @param string $command Command to check
     * @return bool True if command exists
     */
    private function commandExists(string $command): bool
    {
        // If it's a full path, check if the file exists and is executable
        if (strpos($command, '/') !== false) {
            return file_exists($command) && is_executable($command);
        }
        
        // Otherwise, use which to find it in PATH
        $result = shell_exec("which $command 2>/dev/null");
        return !empty($result);
    }

    /**
     * Get installation instructions for mysqldump based on the operating system
     *
     * @return string Installation instructions
     */
    private function getMysqldumpInstallInstructions(): string
    {
        $os = PHP_OS_FAMILY;
        
        switch (strtolower($os)) {
            case 'darwin': // macOS
                return "Installation options for macOS:\n" .
                       "• Homebrew: brew install mysql-client\n" .
                       "• MacPorts: sudo port install mysql8 +client\n" .
                       "• Download from: https://dev.mysql.com/downloads/mysql/";
                       
            case 'linux':
                return "Installation options for Linux:\n" .
                       "• Ubuntu/Debian: sudo apt-get install mysql-client\n" .
                       "• CentOS/RHEL: sudo yum install mysql\n" .
                       "• Fedora: sudo dnf install mysql\n" .
                       "• Arch: sudo pacman -S mysql-clients";
                       
            case 'windows':
                return "Installation options for Windows:\n" .
                       "• Download MySQL Installer from: https://dev.mysql.com/downloads/installer/\n" .
                       "• Or install via Chocolatey: choco install mysql.utilities";
                       
            default:
                return "Please install MySQL client tools for your operating system.\n" .
                       "Visit: https://dev.mysql.com/downloads/";
        }
    }

    /**
     * Sanitize command for logging (remove password)
     *
     * @param string $command Original command
     * @return string Sanitized command
     */
    private function sanitizeCommandForLogging(string $command): string
    {
        return preg_replace('/--password=[^\s]+/', '--password=***', $command);
    }
}