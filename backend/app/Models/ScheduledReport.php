<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'frequency',
        'scheduled_time',
        'day_of_week',
        'day_of_month',
        'is_active',
        'report_type',
        'format',
        'filters',
        'last_run_at',
        'next_run_at',
        'run_count',
        'success_count',
        'failure_count',
        'last_error',
        'send_email',
        'email_recipients',
    ];

    protected $casts = [
        'filters' => 'array',
        'scheduled_time' => 'datetime:H:i',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'is_active' => 'boolean',
        'send_email' => 'boolean',
        'run_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForExecution($query)
    {
        return $query->active()
            ->where('next_run_at', '<=', now())
            ->orWhereNull('next_run_at');
    }

    /**
     * Calculate and set the next run time
     */
    public function calculateNextRun(): void
    {
        if (!$this->is_active) {
            $this->next_run_at = null;
            return;
        }

        $now = now();
        $time = Carbon::parse($this->scheduled_time);

        switch ($this->frequency) {
            case 'daily':
                // Run daily at scheduled time
                $next = $now->copy()->setTime($time->hour, $time->minute);
                if ($next->isPast()) {
                    $next->addDay();
                }
                break;

            case 'weekly':
                // Run weekly on specified day
                $dayOfWeek = $this->day_of_week ?? 'monday';
                $next = $now->copy()->next($dayOfWeek)->setTime($time->hour, $time->minute);

                // If today is the day and time hasn't passed, use today
                if ($now->isDayOfWeek($this->getDayOfWeekNumber($dayOfWeek))) {
                    $today = $now->copy()->setTime($time->hour, $time->minute);
                    if ($today->isFuture()) {
                        $next = $today;
                    }
                }
                break;

            case 'monthly':
                // Run monthly on specified day
                $dayOfMonth = $this->day_of_month ?? 1;
                $next = $now->copy()->day(min($dayOfMonth, $now->daysInMonth))
                    ->setTime($time->hour, $time->minute);

                if ($next->isPast()) {
                    $next->addMonth()->day(min($dayOfMonth, $next->daysInMonth));
                }
                break;

            default:
                $next = null;
        }

        $this->next_run_at = $next;
    }

    /**
     * Mark as executed (success)
     */
    public function markExecuted(): void
    {
        $this->last_run_at = now();
        $this->run_count++;
        $this->success_count++;
        $this->last_error = null;
        $this->calculateNextRun();
        $this->save();
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $error): void
    {
        $this->last_run_at = now();
        $this->run_count++;
        $this->failure_count++;
        $this->last_error = $error;
        $this->calculateNextRun();
        $this->save();
    }

    /**
     * Helper to get day of week number
     */
    private function getDayOfWeekNumber(string $day): int
    {
        $days = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $days[strtolower($day)] ?? 1;
    }

    /**
     * Get frequency display name
     */
    public function getFrequencyDisplayAttribute(): string
    {
        return match ($this->frequency) {
            'daily' => 'Daily at ' . $this->scheduled_time->format('g:i A'),
            'weekly' => 'Weekly on ' . ucfirst($this->day_of_week ?? 'Monday') . ' at ' . $this->scheduled_time->format('g:i A'),
            'monthly' => 'Monthly on day ' . ($this->day_of_month ?? 1) . ' at ' . $this->scheduled_time->format('g:i A'),
            default => 'Unknown',
        };
    }

    /**
     * Check if schedule is due to run
     */
    public function isDue(): bool
    {
        return $this->is_active &&
            $this->next_run_at &&
            $this->next_run_at->isPast();
    }
}
