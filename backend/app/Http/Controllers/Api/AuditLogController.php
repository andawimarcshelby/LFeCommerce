<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Get all audit logs (admin/viewer only)
     */
    public function index(Request $request): JsonResponse
    {
        // Check if user has admin or viewer role
        if (!$request->user()->hasAnyRole(['admin', 'viewer'])) {
            return response()->json([
                'message' => 'Unauthorized. Admin or viewer role required.'
            ], 403);
        }

        $query = AuditLog::with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action') && $request->action) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->has('status') && $request->status) {
            $query->where('response_status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Search by IP or user agent
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', '%' . $search . '%')
                    ->orWhere('user_agent', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->paginate(50);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
            ]
        ]);
    }

    /**
     * Get audit log statistics
     */
    public function stats(Request $request): JsonResponse
    {
        // Check permission
        if (!$request->user()->hasAnyRole(['admin', 'viewer'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $stats = [
            'total_logs' => AuditLog::count(),
            'today_logs' => AuditLog::whereDate('created_at', today())->count(),
            'unique_users' => AuditLog::distinct('user_id')->count('user_id'),
            'avg_response_time' => round(AuditLog::avg('duration_ms'), 2),
            'error_count' => AuditLog::where('response_status', '>=', 400)->count(),
        ];

        return response()->json($stats);
    }
}
