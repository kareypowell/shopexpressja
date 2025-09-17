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
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the backup operation failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Get the result message
     *
     * @return string
     */
    public function getMessage(): string
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
    public function getBackupId(): ?int
    {
        return $this->backup?->id;
    }

    /**
     * Get additional metadata
     *
     * @return array
     */
    public function getMetadata(): array
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
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'backup_id' => $this->getBackupId(),
            'backup' => $this->backup?->toArray(),
            'metadata' => $this->metadata
        ];
    }

    /**
     * Convert result to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}