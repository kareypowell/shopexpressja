<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsolidationHistory extends Model
{
    use HasFactory;

    protected $table = 'consolidation_history';

    protected $fillable = [
        'consolidated_package_id',
        'action',
        'performed_by',
        'details',
        'performed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'performed_at' => 'datetime',
    ];

    /**
     * Get the consolidated package that this history entry belongs to
     */
    public function consolidatedPackage(): BelongsTo
    {
        return $this->belongsTo(ConsolidatedPackage::class);
    }

    /**
     * Get the user who performed this action
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope to filter by action type
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get recent history entries
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }

    /**
     * Get formatted action description
     */
    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            'consolidated' => 'Packages consolidated',
            'unconsolidated' => 'Packages unconsolidated',
            'status_changed' => 'Status changed',
        ];

        return $descriptions[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Get formatted details for display
     */
    public function getFormattedDetailsAttribute(): array
    {
        $details = $this->details ?? [];
        $formatted = [];

        switch ($this->action) {
            case 'consolidated':
                $formatted['Package Count'] = $details['package_count'] ?? 'N/A';
                $formatted['Total Weight'] = isset($details['total_weight']) ? number_format($details['total_weight'], 2) . ' lbs' : 'N/A';
                $formatted['Total Cost'] = isset($details['total_cost']) ? '$' . number_format($details['total_cost'], 2) : 'N/A';
                if (isset($details['package_ids'])) {
                    $formatted['Package IDs'] = implode(', ', $details['package_ids']);
                }
                break;

            case 'unconsolidated':
                $formatted['Package Count'] = $details['package_count'] ?? 'N/A';
                if (isset($details['reason'])) {
                    $formatted['Reason'] = $details['reason'];
                }
                if (isset($details['package_ids'])) {
                    $formatted['Package IDs'] = implode(', ', $details['package_ids']);
                }
                break;

            case 'status_changed':
                if (isset($details['old_status'])) {
                    $formatted['From Status'] = ucfirst($details['old_status']);
                }
                if (isset($details['new_status'])) {
                    $formatted['To Status'] = ucfirst($details['new_status']);
                }
                $formatted['Package Count'] = $details['package_count'] ?? 'N/A';
                if (isset($details['reason'])) {
                    $formatted['Reason'] = $details['reason'];
                }
                break;

            default:
                $formatted = $details;
                break;
        }

        return $formatted;
    }
}