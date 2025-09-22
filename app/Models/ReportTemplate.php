<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'template_config',
        'default_filters',
        'created_by',
        'is_active'
    ];

    protected $casts = [
        'template_config' => 'array',
        'default_filters' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Report types available in the system
     */
    const TYPE_SALES = 'sales';
    const TYPE_MANIFEST = 'manifest';
    const TYPE_CUSTOMER = 'customer';
    const TYPE_FINANCIAL = 'financial';

    /**
     * Get all available report types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_SALES => 'Sales & Collections',
            self::TYPE_MANIFEST => 'Manifest Performance',
            self::TYPE_CUSTOMER => 'Customer Analytics',
            self::TYPE_FINANCIAL => 'Financial Summary'
        ];
    }

    /**
     * Relationship to the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get active templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by report type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the template configuration with defaults
     */
    public function getConfigWithDefaults(): array
    {
        $defaults = [
            'chart_types' => ['line', 'bar'],
            'layout' => 'standard',
            'include_charts' => true,
            'include_tables' => true
        ];

        return array_merge($defaults, $this->template_config ?? []);
    }

    /**
     * Get the default filters with fallbacks
     */
    public function getDefaultFiltersWithFallbacks(): array
    {
        $defaults = [
            'date_range' => 'last_30_days',
            'include_all_offices' => true,
            'include_all_manifest_types' => true
        ];

        return array_merge($defaults, $this->default_filters ?? []);
    }
}
