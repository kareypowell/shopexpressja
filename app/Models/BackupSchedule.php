<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BackupSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'frequency',
        'time',
        'is_active',
        'retention_days',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'time' => 'datetime:H:i:s',
        'retention_days' => 'integer',
    ];

    /**
     * Scope to get active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get schedules that are due to run.
     */
    public function scopeDue($query)
    {
        return $query->where('is_active', true)
                    ->where('next_run_at', '<=', now());
    }

    /**
     * Scope to get schedules by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get schedules by frequency.
     */
    public function scopeOfFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Calculate and set the next run time based on frequency.
     */
    public function calculateNextRun()
    {
        $baseTime = Carbon::today()->setTimeFromTimeString($this->time->format('H:i:s'));
        
        // If today's scheduled time hasn't passed yet, use today
        if ($baseTime->isFuture()) {
            $nextRun = $baseTime;
        } else {
            // Otherwise, calculate the next occurrence
            switch ($this->frequency) {
                case 'daily':
                    $nextRun = $baseTime->addDay();
                    break;
                case 'weekly':
                    $nextRun = $baseTime->addWeek();
                    break;
                case 'monthly':
                    $nextRun = $baseTime->addMonth();
                    break;
                default:
                    $nextRun = $baseTime->addDay();
            }
        }

        $this->next_run_at = $nextRun;
    }

    /**
     * Mark the schedule as run and calculate next run time.
     */
    public function markAsRun()
    {
        $this->last_run_at = now();
        $this->calculateNextRun();
        $this->save();
    }

    /**
     * Check if the schedule is due to run.
     */
    public function isDue()
    {
        return $this->is_active && 
               $this->next_run_at && 
               $this->next_run_at->isPast();
    }

    /**
     * Get the frequency in human readable format.
     */
    public function getFrequencyLabelAttribute()
    {
        return ucfirst($this->frequency);
    }

    /**
     * Get the type in human readable format.
     */
    public function getTypeLabelAttribute()
    {
        switch ($this->type) {
            case 'database':
                return 'Database Only';
            case 'files':
                return 'Files Only';
            case 'full':
                return 'Full Backup';
            default:
                return ucfirst($this->type);
        }
    }
}
