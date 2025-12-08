<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'submission_type',
        'student_id',
        'course_id',
        'term_id',
        'assignment_id',
        'attempt_number',
        'status',
        'submitted_at',
        'graded_at',
        'score',
        'max_score',
        'late_penalty',
        'file_count',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'late_penalty' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function gradingAudits(): HasMany
    {
        return $this->hasMany(GradingAudit::class);
    }

    public function getPercentageAttribute(): ?float
    {
        if (!$this->score || !$this->max_score) {
            return null;
        }

        return ($this->score / $this->max_score) * 100;
    }
}
