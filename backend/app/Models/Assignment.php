<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'assignment_type',
        'max_score',
        'due_date',
        'allows_late',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'due_date' => 'datetime',
        'allows_late' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
