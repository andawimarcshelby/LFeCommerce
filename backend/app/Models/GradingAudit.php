<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingAudit extends Model
{
    protected $fillable = [
        'action_type',
        'submission_id',
        'actor_id',
        'actor_type',
        'old_score',
        'new_score',
        'comment',
        'occurred_at',
    ];

    protected $casts = [
        'old_score' => 'decimal:2',
        'new_score' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
