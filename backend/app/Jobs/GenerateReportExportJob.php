<?php

namespace App\Jobs;

use App\Models\ReportJob;
use App\Services\ReportQueryBuilder;
use App\Services\PdfReportGenerator;
use App\Services\ExcelReportGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 3600;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900];

    protected ReportJob $reportJob;

    /**
     * Create a new job instance.
     */
    public function __construct(ReportJob $reportJob)
    {
        $this->reportJob = $reportJob;
        $this->onQueue('exports'); // Set queue via method, not property
    }

    /**
     * Execute the job.
     */
    public function handle(
        ReportQueryBuilder $queryBuilder,
        PdfReportGenerator $pdfGenerator,
        ExcelReportGenerator $excelGenerator
    ): void {
        try {
            // Check if we can resume from checkpoint
            $resumeFrom = null;
            if ($this->reportJob->canResume()) {
                $resumeFrom = $this->reportJob->checkpoint_data;
                Log::info("Resuming report export from checkpoint", [
                    'job_id' => $this->reportJob->id,
                    'checkpoint' => $resumeFrom,
                ]);
            } else {
                // Mark as running (fresh start)
                $this->reportJob->update([
                    'status' => 'running',
                    'started_at' => now(),
                ]);
            }

            Log::info("Starting report export", [
                'job_id' => $this->reportJob->id,
                'report_type' => $this->reportJob->report_type,
                'format' => $this->reportJob->format,
                'resume_from' => $resumeFrom,
            ]);

            // Build query based on report type
            $query = $this->buildQuery($queryBuilder);

            // Count total rows (skip if resuming)
            if (!$resumeFrom) {
                $totalRows = $query->count();
                $this->reportJob->update(['total_rows' => $totalRows]);
            }

            // Fetch data
            $data = $query->limit(100000)->get()->toArray(); // Limit for safety

            // Progress callback with checkpoint saving
            $progressCallback = function ($processed, $total, $section) {
                $this->reportJob->updateProgress($processed, $total, $section);

                // Save checkpoint every 10% progress
                if ($processed % max(1, intval($total / 10)) === 0) {
                    $this->reportJob->saveCheckpoint([
                        'processed_rows' => $processed,
                        'current_section' => $section,
                        'timestamp' => now()->toDateTimeString(),
                    ]);
                }
            };

            // Generate report based on format
            $filePath = $this->generateReport(
                $data,
                $query,
                $pdfGenerator,
                $excelGenerator,
                $progressCallback,
                $resumeFrom
            );

            // Get file size
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

            // Mark as completed and clear checkpoint
            $this->reportJob->clearCheckpoint();
            $this->reportJob->markCompleted($filePath, $fileSize);

            Log::info("Report export completed", [
                'job_id' => $this->reportJob->id,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);

            // Send completion notification
            $this->reportJob->user->notify(new \App\Notifications\ReportExportCompleted($this->reportJob));

        } catch (Exception $e) {
            Log::error("Report export failed", [
                'job_id' => $this->reportJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Save checkpoint on failure for retry
            $this->reportJob->saveCheckpoint([
                'last_error' => $e->getMessage(),
                'failed_at' => now()->toDateTimeString(),
                'processed_rows' => $this->reportJob->processed_rows,
                'current_section' => $this->reportJob->current_section,
            ]);

            $this->reportJob->markFailed($e->getMessage());

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Build query based on report type
     */
    private function buildQuery(ReportQueryBuilder $queryBuilder)
    {
        $filters = $this->reportJob->filters;

        return match ($this->reportJob->report_type) {
            'detail' => $queryBuilder->buildCourseEventsQuery($filters),
            'summary' => $queryBuilder->buildSummaryQuery($filters),
            'top_n' => $queryBuilder->buildTopNQuery($filters, 'students'),
            'per_student' => $queryBuilder->buildPerStudentQuery($filters),
            default => throw new Exception('Unknown report type: ' . $this->reportJob->report_type),
        };
    }

    /**
     * Generate report file
     */
    private function generateReport(
        array $data,
        $query,
        PdfReportGenerator $pdfGenerator,
        ExcelReportGenerator $excelGenerator,
        callable $progressCallback,
        ?array $resumeFrom = null
    ): string {
        $metadata = [
            'title' => $this->getReportTitle(),
            'columns' => $this->getColumnHeaders(),
        ];

        if ($this->reportJob->format === 'pdf') {
            return $pdfGenerator->generate(
                $this->reportJob->report_type,
                $data,
                $metadata,
                $progressCallback
            );
        }

        if ($this->reportJob->format === 'xlsx') {
            return $excelGenerator->generate(
                $query,
                $metadata,
                $progressCallback
            );
        }

        throw new Exception('Unknown format: ' . $this->reportJob->format);
    }

    /**
     * Get report title
     */
    private function getReportTitle(): string
    {
        return match ($this->reportJob->report_type) {
            'detail' => 'Course Events Detail Report',
            'summary' => 'Course Activity Summary Report',
            'top_n' => 'Top Students by Engagement',
            'per_student' => 'Per-Student Activity Report',
            default => 'LMS Activity Report',
        };
    }

    /**
     * Get column headers
     */
    private function getColumnHeaders(): array
    {
        return match ($this->reportJob->report_type) {
            'detail' => ['Event Type', 'Student', 'Course', 'Term', 'Date'],
            'summary' => ['Date', 'Course', 'Events', 'Students', 'Page Views', 'Video Minutes'],
            'top_n' => ['Rank', 'Student', 'Program', 'Total Events', 'Participation Score'],
            'per_student' => ['Student Number', 'Name', 'Program', 'Year'],
            default => ['Data'],
        };
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error("Report job permanently failed", [
            'job_id' => $this->reportJob->id,
            'error' => $exception->getMessage(),
        ]);

        $this->reportJob->markFailed(
            "Job failed after {$this->tries} attempts: " . $exception->getMessage()
        );

        // Send failure notification
        $this->reportJob->user->notify(new \App\Notifications\ReportExportFailed($this->reportJob));
    }
}
