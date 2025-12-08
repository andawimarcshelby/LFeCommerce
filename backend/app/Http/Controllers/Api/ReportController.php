<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportJob;
use App\Services\ReportQueryBuilder;
use App\Jobs\GenerateReportExportJob;
use App\Http\Requests\ReportPreviewRequest;
use App\Http\Requests\ReportExportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(private ReportQueryBuilder $queryBuilder)
    {
    }

    /**
     * Preview report (first page only)
     */
    public function preview(ReportPreviewRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 100);

        // Build query based on report type and sub-type
        $query = match ($validated['report_type']) {
            'detail' => $this->queryBuilder->buildCourseEventsQuery($validated['filters']),
            'summary' => $this->queryBuilder->buildSummaryQuery($validated['filters']),
            'top_n' => $this->buildTopNQuery($validated['filters']),
            'per_student' => $this->queryBuilder->buildPerStudentQuery($validated['filters']),
        };

        // Get paginated results
        $start = microtime(true);
        $results = $query->paginate($perPage, ['*'], 'page', $page);
        $queryTime = round((microtime(true) - $start) * 1000, 2);

        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            'meta' => [
                'query_time_ms' => $queryTime,
                'report_type' => $validated['report_type'],
                'sub_type' => $validated['filters']['top_n_type'] ?? null,
            ],
        ]);
    }

    /**
     * Helper to build Top-N query based on sub-type
     */
    private function buildTopNQuery(array $filters)
    {
        $topNType = $filters['top_n_type'] ?? 'top_students';
        $limit = $filters['limit'] ?? 100;

        return match ($topNType) {
            'top_students' => $this->queryBuilder->buildTopStudentsByActivityQuery($filters, $limit),
            'top_courses' => $this->queryBuilder->buildTopCoursesByEngagementQuery($filters, $limit),
            'late_submissions' => $this->queryBuilder->buildLateSubmissionsQuery($filters, $limit),
            'inactive_students' => $this->queryBuilder->buildInactiveStudentsQuery($filters, $limit),
            default => $this->queryBuilder->buildTopStudentsByActivityQuery($filters, $limit),
        };
    }

    /**
     * Create export job
     */
    public function export(ReportExportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Check concurrent exports limit
        $userId = Auth::id() ?? 1; // Default for demo
        $runningJobs = ReportJob::where('user_id', $userId)
            ->whereIn('status', ['queued', 'running'])
            ->count();

        $maxConcurrent = config('app.report_max_concurrent_exports_per_user', 5);

        if ($runningJobs >= $maxConcurrent) {
            return response()->json([
                'error' => "You have {$runningJobs} exports in progress. Maximum allowed: {$maxConcurrent}.",
            ], 429);
        }

        // Create report job
        $reportJob = ReportJob::create([
            'user_id' => $userId,
            'report_type' => $validated['report_type'],
            'format' => $validated['format'],
            'filters' => $validated['filters'],
            'status' => 'queued',
        ]);

        // Dispatch job
        GenerateReportExportJob::dispatch($reportJob);

        // Audit log
        \App\Models\AuditLog::log('report_export_created', $reportJob, [
            'report_type' => $reportJob->report_type,
            'format' => $reportJob->format,
        ]);

        return response()->json([
            'message' => 'Export job created successfully',
            'job_id' => $reportJob->id,
            'status' => $reportJob->status,
        ], 201);
    }

    /**
     * Get export job status
     */
    public function status(string $id): JsonResponse
    {
        $userId = Auth::id() ?? 1;

        $job = ReportJob::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        return response()->json([
            'id' => $job->id,
            'report_type' => $job->report_type,
            'format' => $job->format,
            'status' => $job->status,
            'progress_percent' => $job->progress_percent,
            'current_section' => $job->current_section,
            'total_rows' => $job->total_rows,
            'processed_rows' => $job->processed_rows,
            'file_size' => $job->file_size_human,
            'download_url' => $job->download_url,
            'error_message' => $job->error_message,
            'created_at' => $job->created_at,
            'started_at' => $job->started_at,
            'finished_at' => $job->finished_at,
            'is_expired' => $job->isExpired(),
        ]);
    }

    /**
     * List user's export jobs
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id() ?? 1;

        $jobs = ReportJob::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($jobs);
    }

    /**
     * Delete/cancel export job
     */
    public function destroy(string $id): JsonResponse
    {
        $userId = Auth::id() ?? 1;

        $job = ReportJob::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Delete file if exists
        if ($job->file_path && file_exists($job->file_path)) {
            unlink($job->file_path);
        }

        $job->delete();

        return response()->json([
            'message' => 'Export job deleted successfully',
        ]);
    }

    /**
     * Download report file
     */
    public function download(Request $request): mixed
    {
        $jobId = $request->input('job');
        $userId = Auth::id() ?? 1;

        $job = ReportJob::where('id', $jobId)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($job->status !== 'completed' || !$job->file_path || !file_exists($job->file_path)) {
            abort(404, 'Report file not found');
        }

        if ($job->isExpired()) {
            abort(410, 'Download link has expired');
        }

        // Audit log
        \App\Models\AuditLog::log('report_downloaded', $job, [
            'file_size' => $job->file_size,
            'format' => $job->format,
        ]);

        return response()->download($job->file_path);
    }
}
