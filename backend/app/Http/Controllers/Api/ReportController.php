<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Models\ReportJob;
use App\Jobs\GenerateReportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Preview report (first page, fast response)
     * POST /api/reports/preview
     */
    public function preview(Request $request)
    {
        $validator = $this->validateReportRequest($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filters = $request->input('filters', []);
        $reportType = $request->input('report_type', 'detail');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 100);

        try {
            $data = match ($reportType) {
                'detail' => $this->reportService->detailReport($filters, $page, $perPage),
                'summary' => $this->reportService->summaryReport($filters, $request->input('group_by', 'date')),
                'top-n' => $this->reportService->topNReport($filters, $request->input('top_type', 'customers'), $request->input('limit', 100)),
                'exceptions' => $this->reportService->exceptionsReport($filters, $request->input('exception_type', 'failed_orders')),
                default => throw new \InvalidArgumentException("Invalid report type: {$reportType}")
            };

            $totalEstimate = $this->reportService->estimateRows($filters);

            return response()->json([
                'success' => true,
                'report_type' => $reportType,
                'data' => $data,
                'total_estimate' => $totalEstimate,
                'filters_applied' => $filters,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue export job (PDF/Excel)
     * POST /api/reports/exports
     */
    public function export(Request $request)
    {
        $validator = $this->validateExportRequest($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filters = $request->input('filters', []);
        $reportType = $request->input('report_type', 'detail');
        $format = $request->input('format', 'pdf');

        // Estimate total rows
        $totalRows = $this->reportService->estimateRows($filters);

        // Create report job
        $reportJob = ReportJob::create([
            'user_id' => auth()->id() ?? null,
            'report_type' => $reportType,
            'format' => $format,
            'status' => 'queued',
            'filters' => $filters,
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'percent' => 0,
        ]);

        // Dispatch to queue
        GenerateReportJob::dispatch($reportJob->id)
            ->onQueue('exports');

        return response()->json([
            'success' => true,
            'job_id' => $reportJob->id,
            'status' => 'queued',
            'total_rows' => $totalRows,
            'message' => 'Report generation queued successfully',
        ], 202);
    }

    /**
     * Get export job status
     * GET /api/reports/exports/{id}
     */
    public function status($id)
    {
        $reportJob = ReportJob::find($id);

        if (!$reportJob) {
            return response()->json([
                'success' => false,
                'error' => 'Report job not found'
            ], 404);
        }

        // Check authorization
        if (auth()->id() && $reportJob->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
        }

        $response = [
            'success' => true,
            'job_id' => $reportJob->id,
            'status' => $reportJob->status,
            'report_type' => $reportJob->report_type,
            'format' => $reportJob->format,
            'total_rows' => $reportJob->total_rows,
            'processed_rows' => $reportJob->processed_rows,
            'percent' => $reportJob->percent,
            'current_section' => $reportJob->current_section,
            'created_at' => $reportJob->created_at,
            'started_at' => $reportJob->started_at,
            'finished_at' => $reportJob->finished_at,
        ];

        // Add download link if completed
        if ($reportJob->status === 'completed' && $reportJob->file_path) {
            $response['download_url'] = $this->generateSignedUrl($reportJob);
            $response['file_size'] = $reportJob->file_size;
            $response['expires_at'] = $reportJob->url_expires_at;
        }

        // Add error if failed
        if ($reportJob->status === 'failed') {
            $response['error'] = $reportJob->error;
        }

        return response()->json($response);
    }

    /**
     * List user's export jobs
     * GET /api/reports/exports
     */
    public function list(Request $request)
    {
        $query = ReportJob::query();

        // Filter by user if authenticated
        if (auth()->id()) {
            $query->where('user_id', auth()->id());
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Cancel a queued/running job
     * DELETE /api/reports/exports/{id}
     */
    public function cancel($id)
    {
        $reportJob = ReportJob::find($id);

        if (!$reportJob) {
            return response()->json([
                'success' => false,
                'error' => 'Report job not found'
            ], 404);
        }

        // Check authorization
        if (auth()->id() && $reportJob->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
        }

        // Can only cancel queued or running jobs
        if (!in_array($reportJob->status, ['queued', 'running'])) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot cancel job with status: ' . $reportJob->status
            ], 400);
        }

        $reportJob->update([
            'status' => 'failed',
            'error' => 'Cancelled by user',
            'finished_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job cancelled successfully',
        ]);
    }

    /**
     * Validate report request
     */
    private function validateReportRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'report_type' => 'required|in:detail,summary,top-n,exceptions',
            'filters' => 'array',
            'filters.date_from' => 'date',
            'filters.date_to' => 'date|after_or_equal:filters.date_from',
            'filters.region_id' => 'integer|exists:regions,id',
            'filters.customer_id' => 'integer|exists:customers,id',
            'filters.status' => 'string',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:1000',
        ]);
    }

    /**
     * Validate export request
     */
    private function validateExportRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'report_type' => 'required|in:detail,summary,top-n,exceptions,per-entity',
            'format' => 'required|in:pdf,xlsx',
            'filters' => 'array',
            'filters.date_from' => 'date',
            'filters.date_to' => 'date|after_or_equal:filters.date_from',
        ]);
    }

    /**
     * Generate signed URL for download
     */
    private function generateSignedUrl(ReportJob $reportJob): string
    {
        // Generate signed URL with 15-minute expiry
        $expiresAt = now()->addMinutes(config('app.report_signed_url_ttl', 15));

        $reportJob->update([
            'signed_url' => Storage::url($reportJob->file_path),
            'url_expires_at' => $expiresAt,
        ]);

        return Storage::url($reportJob->file_path);
    }
}
