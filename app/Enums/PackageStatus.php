<?php

namespace App\Enums;

class PackageStatus
{
    const PENDING = 'pending';
    const PROCESSING = 'processing';
    const SHIPPED = 'shipped';
    const CUSTOMS = 'customs';
    const READY = 'ready';
    const DELIVERED = 'delivered';
    const DELAYED = 'delayed';

    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function PENDING(): self
    {
        return new self(self::PENDING);
    }

    public static function PROCESSING(): self
    {
        return new self(self::PROCESSING);
    }

    public static function SHIPPED(): self
    {
        return new self(self::SHIPPED);
    }

    public static function CUSTOMS(): self
    {
        return new self(self::CUSTOMS);
    }

    public static function READY(): self
    {
        return new self(self::READY);
    }

    public static function DELIVERED(): self
    {
        return new self(self::DELIVERED);
    }

    public static function DELAYED(): self
    {
        return new self(self::DELAYED);
    }

    public static function from(string $value): self
    {
        if (!in_array($value, self::values())) {
            throw new \InvalidArgumentException("Invalid status value: {$value}");
        }
        return new self($value);
    }

    public static function cases(): array
    {
        return [
            self::PENDING(),
            self::PROCESSING(),
            self::SHIPPED(),
            self::CUSTOMS(),
            self::READY(),
            self::DELIVERED(),
            self::DELAYED(),
        ];
    }

    /**
     * Get status cases available for manual updates (excludes DELIVERED)
     */
    public static function manualUpdateCases(): array
    {
        return [
            self::PENDING(),
            self::PROCESSING(),
            self::SHIPPED(),
            self::CUSTOMS(),
            self::READY(),
            // self::DELIVERED(), // Excluded - only available through distribution process
            self::DELAYED(),
        ];
    }

    /**
     * Map legacy status values to normalized format
     */
    public static function fromLegacyStatus(string $legacyStatus): self
    {
        $status = strtolower(trim($legacyStatus));
        
        switch ($status) {
            case 'pending':
            case 'new':
            case 'created':
                return self::PENDING();
            case 'processing':
            case 'in_process':
            case 'in process':
                return self::PROCESSING();
            case 'shipped':
            case 'in_transit':
            case 'in transit':
                return self::SHIPPED();
            case 'delayed':
                return self::DELAYED();
            case 'customs':
            case 'at_customs':
            case 'at customs':
                return self::CUSTOMS();
            case 'ready':
            case 'ready_for_pickup':
            case 'ready for pickup':
                return self::READY();
            case 'delivered':
            case 'completed':
            case 'done':
                return self::DELIVERED();
            default:
                return self::PENDING(); // Default fallback for unmappable statuses
        }
    }

    /**
     * Get human-readable label for the status
     */
    public function getLabel(): string
    {
        switch ($this->value) {
            case self::PENDING:
                return 'Pending';
            case self::PROCESSING:
                return 'Processing';
            case self::SHIPPED:
                return 'Shipped';
            case self::CUSTOMS:
                return 'At Customs';
            case self::READY:
                return 'Ready for Pickup';
            case self::DELIVERED:
                return 'Delivered';
            case self::DELAYED:
                return 'Delayed';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get CSS badge class for consistent styling
     */
    public function getBadgeClass(): string
    {
        switch ($this->value) {
            case self::PENDING:
                return 'default';        // Gray - Neutral/waiting state
            case self::PROCESSING:
                return 'primary';        // Wax flower - Active processing
            case self::SHIPPED:
                return 'shs';           // Brand color - In transit
            case self::CUSTOMS:
                return 'warning';       // Yellow - Attention needed
            case self::READY:
                return 'success';       // Green - Ready for customer
            case self::DELIVERED:
                return 'success';       // Green - Completed successfully
            case self::DELAYED:
                return 'danger';        // Red - Problem/issue
            default:
                return 'default';
        }
    }

    /**
     * Get all valid status transitions from current status
     */
    public function getValidTransitions(): array
    {
        switch ($this->value) {
            case self::PENDING:
                return [self::PROCESSING(), self::DELAYED()];
            case self::PROCESSING:
                return [self::SHIPPED(), self::PENDING(), self::DELAYED()];
            case self::SHIPPED:
                return [self::CUSTOMS(), self::READY(), self::DELAYED()];
            case self::CUSTOMS:
                return [self::READY(), self::SHIPPED(), self::DELAYED()];
            case self::READY:
                return [self::DELIVERED()];
            case self::DELIVERED:
                return []; // Terminal state
            case self::DELAYED:
                return [self::PROCESSING(), self::SHIPPED(), self::CUSTOMS(), self::READY()]; // Can recover from delay
            default:
                return [];
        }
    }

    /**
     * Check if transition to another status is valid
     */
    public function canTransitionTo(PackageStatus $newStatus): bool
    {
        $validTransitions = $this->getValidTransitions();
        foreach ($validTransitions as $transition) {
            if ($transition->value === $newStatus->value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::SHIPPED,
            self::CUSTOMS,
            self::READY,
            self::DELIVERED,
            self::DELAYED,
        ];
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
        return $this->value === self::READY;
    }

    /**
     * Check if two status instances are equal
     */
    public function equals(PackageStatus $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->value;
    }
}