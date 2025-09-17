<?php

namespace App\Services;

class RestoreResult
{
    public bool $success;
    public string $message;
    public array $data;

    public function __construct(bool $success, string $message, array $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Check if the restore operation was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the restore operation failed
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Get the result message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get additional data from the restore operation
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get specific data value by key
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data
        ];
    }
}