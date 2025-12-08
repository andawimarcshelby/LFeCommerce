<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class ReportJob extends Model
{
    protected $fillable = [
        'user_id',
        'report_type',
        'format',
        'filters',
        'status',
        'total_rows',
        'processed_rows',
        'progress_percent',
        'current_section',
        'file_path',
        'file_size_bytes',
        'download_url',
        'error_message',
        'started_at',
        'finished_at',
        'expires_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Update job progress
     */
    public function updateProgress(int $processed, int $total, string $section = null): void
    {
        $this->update([
            'processed_rows' => $processed,
            'total_rows' => $total,
            'progress_percent' => $total > 0 ? min(100, round(($processed / $total) * 100)) : 0,
            'current_section' => $section,
        ]);
    }

    /**
     * Mark job as completed
     */
    public function markCompleted(string $filePath, int $fileSize): void
    {
        $expiresAt = Carbon::now()->addHours(config('app.report_download_ttl_hours', 24));

        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size_bytes' => $fileSize,
            'download_url' => $this->generateSignedUrl($filePath),
            'finished_at' => now(),
            'expires_at' => $expiresAt,
            'progress_percent' => 100,
        ]);
    }

    /**
     * Mark job as failed
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'finished_at' => now(),
        ]);
    }

    /**
     * Generate signed download URL
     */
    public function generateSignedUrl(string $filePath = null): string
    {
        $path = $filePath ?? $this->file_path;

        if (!$path) {
            return '';
        }

        return URL::temporarySignedRoute(
            'reports.download',
            Carbon::now()->addHours(24),
            ['filename' => basename($path), 'job' => $this->id]
        );
    }

    /**
     * Check if download has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size_bytes) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size_bytes;

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
