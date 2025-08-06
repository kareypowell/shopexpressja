<?php

namespace App\Enums;

enum PackageStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case CUSTOMS = 'customs';
    case READY = 'ready';
    case DELIVERED = 'delivered';
    case DELAYED = 'delayed';

    /**
     * Map legacy status values to normalized format
     */
    public static function fromLegacyStatus(string $legacyStatus): self
    {
        return match(strtolower(trim($legacyStatus))) {
            'pending', 'new', 'created' => self::PENDING,
            'processing', 'in_process', 'in process' => self::PROCESSING,
            'shipped', 'in_transit', 'in transit' => self::SHIPPED,
            'delayed' => self::DELAYED,
            'customs', 'at_customs', 'at customs' => self::CUSTOMS,
            'ready', 'ready_for_pickup', 'ready for pickup' => self::READY,
            'delivered', 'completed', 'done' => self::DELIVERED,
            default => self::PENDING, // Default fallback for unmappable statuses
        };
    }

    /**
     * Get human-readable label for the status
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::CUSTOMS => 'At Customs',
            self::READY => 'Ready for Pickup',
            self::DELIVERED => 'Delivered',
            self::DELAYED => 'Delayed',
        };
    }

    /**
     * Get CSS badge class for consistent styling
     */
    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'default',
            self::PROCESSING => 'primary',
            self::SHIPPED => 'shs',
            self::CUSTOMS => 'warning',
            self::READY => 'success',
            self::DELIVERED => 'success',
            self::DELAYED => 'danger',
        };
    }

    /**
     * Get all valid status transitions from current status
     */
    public function getValidTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::PROCESSING, self::DELAYED],
            self::PROCESSING => [self::SHIPPED, self::PENDING, self::DELAYED],
            self::SHIPPED => [self::CUSTOMS, self::READY, self::DELAYED],
            self::CUSTOMS => [self::READY, self::SHIPPED, self::DELAYED],
            self::READY => [self::DELIVERED],
            self::DELIVERED => [], // Terminal state
            self::DELAYED => [self::PROCESSING, self::SHIPPED, self::CUSTOMS], // Can recover from delay
        };
    }

    /**
     * Check if transition to another status is valid
     */
    public function canTransitionTo(PackageStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getValidTransitions());
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all status labels as array
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $status) {
            $labels[$status->value] = $status->getLabel();
        }
        return $labels;
    }

    /**
     * Check if status is terminal (no further transitions allowed)
     */
    public function isTerminal(): bool
    {
        return empty($this->getValidTransitions());
    }

    /**
     * Check if status allows distribution
     */
    public function allowsDistribution(): bool
    {
        return $this === self::READY;
    }
}