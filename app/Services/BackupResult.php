<?php

namespace App\Services;

use App\Models\Backup;

class BackupResult
{
    public bool $success;
    public string $message;
    public ?Backup $backup;
    public array $metadata;

    public function __construct(bool $success, string $message, ?Backup $backup = null, array $metadata = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->backup = $backup;
        $this->metadata = $metadata;
    }

    /**
     * Check if the backup operation was successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->success;
    }

    /**
     * Check if the backup operation failed
     *
     * @return bool
     */
    public function isFailed()
    {
        return !$this->success;
    }

    /**
     * Get the result message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the backup model instance
     *
     * @return Backup|null
     */
    public function getBackup(): ?Backup
    {
        return $this->backup;
    }

    /**
     * Get backup ID if available
     *
     * @return int|null
     */
    public function getBackupId()
    {
        return $this->backup ? $this->backup->id : null;
    }

    /**
     * Get additional metadata
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Add metadata to the result
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Convert result to array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'backup_id' => $this->getBackupId(),
            'backup' => $this->backup ? $this->backup->toArray() : null,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Convert result to JSON
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Check if the backup operation was successful (alias for isSuccessful)
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * Get the file path of the backup
     *
     * @return string|null
     */
    public function getFilePath()
    {
        if ($this->backup && $this->backup->file_path) {
            return $this->backup->file_path;
        }
        return $this->metadata['file_path'] ?? null;
    }

    /**
     * Get the file size of the backup
     *
     * @return int
     */
    public function getFileSize()
    {
        if ($this->backup && $this->backup->file_size) {
            return $this->backup->file_size;
        }
        return $this->metadata['file_size'] ?? 0;
    }

    /**
     * Get the error message (alias for getMessage when failed)
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->message;
    }

    /**
     * Get the backup type
     *
     * @return string
     */
    public function getType()
    {
        if ($this->backup && $this->backup->type) {
            return $this->backup->type;
        }
        return $this->metadata['type'] ?? 'unknown';
    }

    /**
     * Get the backup duration in seconds
     *
     * @return float
     */
    public function getDuration()
    {
        if ($this->backup && $this->backup->created_at && $this->backup->completed_at) {
            return $this->backup->created_at->diffInSeconds($this->backup->completed_at);
        }
        return $this->metadata['duration'] ?? 0;
    }

    /**
     * Get the backup creation timestamp
     *
     * @return \Carbon\Carbon|null
     */
    public function getCreatedAt()
    {
        if ($this->backup && $this->backup->created_at) {
            return $this->backup->created_at;
        }
        return null;
    }
}