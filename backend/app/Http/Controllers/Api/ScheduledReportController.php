<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduledReportController extends Controller
{
    /**
     * List user's scheduled reports
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $schedules = ScheduledReport::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $schedules->map(fn($schedule) => [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'description' => $schedule->description,
                'frequency' => $schedule->frequency,
                'frequency_display' => $schedule->frequency_display,
                'scheduled_time' => $schedule->scheduled_time->format('H:i'),
                'day_of_week' => $schedule->day_of_week,
                'day_of_month' => $schedule->day_of_month,
                'is_active' => $schedule->is_active,
                'report_type' => $schedule->report_type,
                'format' => $schedule->format,
                'filters' => $schedule->filters,
                'last_run_at' => $schedule->last_run_at?->toIso8601String(),
                'next_run_at' => $schedule->next_run_at?->toIso8601String(),
                'run_count' => $schedule->run_count,
                'success_count' => $schedule->success_count,
                'failure_count' => $schedule->failure_count,
                'last_error' => $schedule->last_error,
                'send_email' => $schedule->send_email,
                'email_recipients' => $schedule->email_recipients,
                'created_at' => $schedule->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Store a new scheduled report
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'frequency' => 'required|in:daily,weekly,monthly',
            'scheduled_time' => 'required|date_format:H:i',
            'day_of_week' => 'required_if:frequency,weekly|nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'day_of_month' => 'required_if:frequency,monthly|nullable|integer|min:1|max:31',
            'report_type' => 'required|in:detail,summary,top_n,per_student',
            'format' => 'required|in:pdf,xlsx',
            'filters' => 'nullable|array',
            'send_email' => 'boolean',
            'email_recipients' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schedule = new ScheduledReport($validator->validated());
        $schedule->user_id = $request->user()->id;
        $schedule->is_active = true;
        
        // Calculate initial next run time
        $schedule->calculateNextRun();
        $schedule->save();

        return response()->json([
            'message' => 'Scheduled report created successfully',
            'data' => $schedule,
        ], 201);
    }

    /**
     * Show a specific scheduled report
     */
    public function show(Request $request, ScheduledReport $schedule): JsonResponse
    {
        // Authorization
        if ($schedule->user_id !== $request->user()->id && !$request->user()->can('view all reports')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $schedule,
        ]);
    }

    /**
     * Update a scheduled report
     */
    public function update(Request $request, ScheduledReport $schedule): JsonResponse
    {
        // Authorization
        if ($schedule->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'frequency' => 'sometimes|in:daily,weekly,monthly',
            'scheduled_time' => 'sometimes|date_format:H:i',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'is_active' => 'boolean',
            'report_type' => 'sometimes|in:detail,summary,top_n,per_student',
            'format' => 'sometimes|in:pdf,xlsx',
            'filters' => 'nullable|array',
            'send_email' => 'boolean',
            'email_recipients' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schedule->fill($validator->validated());
        
        // Recalculate next run if schedule changed
        if ($request->hasAny(['frequency', 'scheduled_time', 'day_of_week', 'day_of_month', 'is_active'])) {
            $schedule->calculateNextRun();
        }
        
        $schedule->save();

        return response()->json([
            'message' => 'Scheduled report updated successfully',
            'data' => $schedule,
        ]);
    }

    /**
     * Delete a scheduled report
     */
    public function destroy(Request $request, ScheduledReport $schedule): JsonResponse
    {
        // Authorization
        if ($schedule->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $schedule->delete();

        return response()->json([
            'message' => 'Scheduled report deleted successfully',
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggle(Request $request, ScheduledReport $schedule): JsonResponse
    {
        // Authorization
        if ($schedule->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $schedule->is_active = !$schedule->is_active;
        $schedule->calculateNextRun();
        $schedule->save();

        return response()->json([
            'message' => $schedule->is_active ? 'Schedule activated' : 'Schedule deactivated',
            'data' => $schedule,
        ]);
    }

    /**
     * Manually trigger a scheduled report (runs immediately)
     */
    public function trigger(Request $request, ScheduledReport $schedule): JsonResponse
    {
        use App\Jobs\GenerateReportExportJob;
        use App\Models\ReportJob;

        // Authorization
        if ($schedule->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Create report job
            $reportJob = ReportJob::create([
                'user_id' => $schedule->user_id,
                'report_type' => $schedule->report_type,
                'format' => $schedule->format,
                'filters' => $schedule->filters ?? [],
                'status' => 'queued',
                'metadata' => [
                    'scheduled_report_id' => $schedule->id,
                    'manual_trigger' => true,
                ],
            ]);

            // Dispatch job
            GenerateReportExportJob::dispatch($reportJob);

            return response()->json([
                'message' => 'Report generation triggered successfully',
                'job_id' => $reportJob->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to trigger report: ' . $e->getMessage(),
            ], 500);
        }
    }
}
