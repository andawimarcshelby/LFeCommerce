<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use App\Models\CourseEvent;

class ReportQueryBuilder
{
    /**
     * Build query for course events report with filters
     */
    public function buildCourseEventsQuery(array $filters): Builder
    {
        $query = CourseEvent::query()
            ->select([
                'course_events.id',
                'course_events.event_type',
                'course_events.occurred_at',
                'course_events.event_data',
                'students.student_number',
                'students.first_name as student_first_name',
                'students.last_name as student_last_name',
                'courses.course_code',
                'courses.course_name',
                'terms.name as term_name',
            ])
            ->join('students', 'course_events.student_id', '=', 'students.id')
            ->join('courses', 'course_events.course_id', '=', 'courses.id')
            ->join('terms', 'course_events.term_id', '=', 'terms.id');

        // Date range filter (required)
        if (!empty($filters['date_from'])) {
            $query->where('course_events.occurred_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('course_events.occurred_at', '<=', $filters['date_to']);
        }

        // Term filter
        if (!empty($filters['term_ids'])) {
            $query->whereIn('course_events.term_id', $filters['term_ids']);
        }

        // Course filter
        if (!empty($filters['course_ids'])) {
            $query->whereIn('course_events.course_id', $filters['course_ids']);
        }

        // Event type filter
        if (!empty($filters['event_types'])) {
            $query->whereIn('course_events.event_type', $filters['event_types']);
        }

        // Student program filter
        if (!empty($filters['programs'])) {
            $query->whereIn('students.program', $filters['programs']);
        }

        // Sorting
        $sortColumn = $filters['sort_by'] ?? 'occurred_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $query->orderBy("course_events.{$sortColumn}", $sortDirection);

        return $query;
    }

    /**
     * Build query for summary report
     */
    public function buildSummaryQuery(array $filters): QueryBuilder
    {
        $query = DB::table('course_daily_activity')
            ->select([
                'course_daily_activity.report_date',
                'courses.course_code',
                'courses.course_name',
                'terms.name as term_name',
                'course_daily_activity.total_events',
                'course_daily_activity.unique_students',
                'course_daily_activity.page_views',
                'course_daily_activity.video_minutes',
                'course_daily_activity.quiz_attempts',
                'course_daily_activity.discussion_posts',
                'course_daily_activity.avg_session_duration_minutes',
            ])
            ->join('courses', 'course_daily_activity.course_id', '=', 'courses.id')
            ->join('terms', 'course_daily_activity.term_id', '=', 'terms.id');

        if (!empty($filters['date_from'])) {
            $query->where('course_daily_activity.report_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('course_daily_activity.report_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['term_ids'])) {
            $query->whereIn('course_daily_activity.term_id', $filters['term_ids']);
        }

        if (!empty($filters['course_ids'])) {
            $query->whereIn('course_daily_activity.course_id', $filters['course_ids']);
        }

        $query->orderBy('course_daily_activity.report_date', 'desc');

        return $query;
    }

    /**
     * Build query for top-N report
     */
    public function buildTopNQuery(array $filters, string $type = 'students'): QueryBuilder
    {
        if ($type === 'students') {
            return DB::table('student_course_engagement')
                ->select([
                    'students.student_number',
                    'students.first_name',
                    'students.last_name',
                    'students.program',
                    'courses.course_code',
                    'student_course_engagement.total_events',
                    'student_course_engagement.total_logins',
                    'student_course_engagement.participation_score',
                    DB::raw('ROW_NUMBER() OVER (ORDER BY student_course_engagement.total_events DESC) as rank')
                ])
                ->join('students', 'student_course_engagement.student_id', '=', 'students.id')
                ->join('courses', 'student_course_engagement.course_id', '=', 'courses.id')
                ->where('student_course_engagement.term_id', $filters['term_id'] ?? 1)
                ->orderBy('student_course_engagement.total_events', 'desc')
                ->limit($filters['limit'] ?? 100);
        }

        // Top courses by engagement
        return DB::table('course_daily_activity')
            ->select([
                'courses.course_code',
                'courses.course_name',
                'courses.department',
                DB::raw('SUM(course_daily_activity.total_events) as total_events'),
                DB::raw('AVG(course_daily_activity.unique_students) as avg_students'),
                DB::raw('SUM(course_daily_activity.page_views) as total_page_views'),
            ])
            ->join('courses', 'course_daily_activity.course_id', '=', 'courses.id')
            ->groupBy('courses.id', 'courses.course_code', 'courses.course_name', 'courses.department')
            ->orderBy('total_events', 'desc')
            ->limit($filters['limit'] ?? 10);
    }

    /**
     * Build query for top students by activity
     */
    public function buildTopStudentsByActivityQuery(array $filters, int $limit = 100): QueryBuilder
    {
        $query = DB::table('course_events')
            ->select([
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.program',
                'students.year_level',
                DB::raw('COUNT(*) as total_events'),
                DB::raw('COUNT(DISTINCT course_events.course_id) as courses_engaged'),
                DB::raw('COUNT(DISTINCT DATE(course_events.occurred_at)) as active_days'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY COUNT(*) DESC) as rank')
            ])
            ->join('students', 'course_events.student_id', '=', 'students.id')
            ->groupBy('students.id', 'students.student_number', 'students.first_name', 'students.last_name', 'students.program', 'students.year_level');

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('course_events.occurred_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('course_events.occurred_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['term_ids'])) {
            $query->whereIn('course_events.term_id', $filters['term_ids']);
        }

        if (!empty($filters['program'])) {
            $query->where('students.program', $filters['program']);
        }

        return $query->orderBy('total_events', 'desc')->limit($limit);
    }

    /**
     * Build query for top courses by engagement
     */
    public function buildTopCoursesByEngagementQuery(array $filters, int $limit = 50): QueryBuilder
    {
        $query = DB::table('course_events')
            ->select([
                'courses.course_code',
                'courses.course_name',
                'courses.department',
                'courses.level',
                'terms.name as term_name',
                DB::raw('COUNT(*) as total_events'),
                DB::raw('COUNT(DISTINCT course_events.student_id) as unique_students'),
                DB::raw('COUNT(DISTINCT DATE(course_events.occurred_at)) as active_days'),
                DB::raw('ROUND(COUNT(*) * 1.0 / NULLIF(COUNT(DISTINCT course_events.student_id), 0), 2) as avg_events_per_student'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY COUNT(*) DESC) as rank')
            ])
            ->join('courses', 'course_events.course_id', '=', 'courses.id')
            ->join('terms', 'course_events.term_id', '=', 'terms.id')
            ->groupBy('courses.id', 'courses.course_code', 'courses.course_name', 'courses.department', 'courses.level', 'terms.name');

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('course_events.occurred_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('course_events.occurred_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['term_ids'])) {
            $query->whereIn('course_events.term_id', $filters['term_ids']);
        }

        if (!empty($filters['department'])) {
            $query->where('courses.department', $filters['department']);
        }

        return $query->orderBy('total_events', 'desc')->limit($limit);
    }

    /**
     * Build query for late submissions report (exceptions)
     */
    public function buildLateSubmissionsQuery(array $filters, int $limit = 100): QueryBuilder
    {
        $query = DB::table('submissions')
            ->select([
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.course_code',
                'courses.course_name',
                'assignments.title as assignment_title',
                'assignments.due_date',
                'submissions.submitted_at',
                DB::raw('EXTRACT(EPOCH FROM (submissions.submitted_at - assignments.due_date)) / 3600 as hours_late'),
                DB::raw('submissions.final_score'),
                DB::raw('assignments.max_points'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY (submissions.submitted_at - assignments.due_date) DESC) as rank')
            ])
            ->join('students', 'submissions.student_id', '=', 'students.id')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->whereRaw('submissions.submitted_at > assignments.due_date'); // Only late submissions

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('submissions.submitted_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('submissions.submitted_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['term_ids'])) {
            $query->whereIn('assignments.term_id', $filters['term_ids']);
        }

        if (!empty($filters['course_ids'])) {
            $query->whereIn('assignments.course_id', $filters['course_ids']);
        }

        // Order by how late they were (most late first)
        return $query->orderByRaw('(submissions.submitted_at - assignments.due_date) DESC')->limit($limit);
    }

    /**
     * Build query for inactive students report (exceptions)
     */
    public function buildInactiveStudentsQuery(array $filters, int $limit = 100): QueryBuilder
    {
        // Define "inactive" as students with low activity or no recent activity
        $inactivityThreshold = $filters['inactivity_days'] ?? 14; // Days without activity
        $thresholdDate = date('Y-m-d', strtotime("-{$inactivityThreshold} days"));

        $query = DB::table('students')
            ->select([
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.program',
                'students.year_level',
                'students.email',
                DB::raw('MAX(course_events.occurred_at) as last_activity_at'),
                DB::raw('COUNT(course_events.id) as total_events'),
                DB::raw('EXTRACT(DAY FROM (NOW() - MAX(course_events.occurred_at))) as days_since_last_activity'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY MAX(course_events.occurred_at) ASC NULLS FIRST) as rank')
            ])
            ->leftJoin('course_events', function ($join) use ($filters) {
                $join->on('students.id', '=', 'course_events.student_id');
                if (!empty($filters['term_ids'])) {
                    $join->whereIn('course_events.term_id', $filters['term_ids']);
                }
            })
            ->groupBy('students.id', 'students.student_number', 'students.first_name', 'students.last_name', 'students.program', 'students.year_level', 'students.email');

        // Filter for truly inactive students
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            // Students who have less than minimum activity in the period
            $minEvents = $filters['min_events'] ?? 5;
            $query->havingRaw('COUNT(course_events.id) < ?', [$minEvents]);
        } else {
            // Students with no recent activity
            $query->havingRaw('(MAX(course_events.occurred_at) < ? OR MAX(course_events.occurred_at) IS NULL)', [$thresholdDate]);
        }

        if (!empty($filters['program'])) {
            $query->where('students.program', $filters['program']);
        }

        // Order by least active (oldest last activity or no activity)
        return $query->orderByRaw('MAX(course_events.occurred_at) ASC NULLS FIRST')->limit($limit);
    }

    /**
     * Build query for per-student report
     */
    public function buildPerStudentQuery(array $filters): QueryBuilder
    {
        $query = DB::table('students')
            ->select([
                'students.*',
                DB::raw('COUNT(DISTINCT ce.student_id) as has_activity')
            ])
            ->leftJoin('course_enrollments as ce', 'students.id', '=', 'ce.student_id')
            ->groupBy('students.id');

        if (!empty($filters['term_id'])) {
            $query->where('ce.term_id', $filters['term_id']);
        }

        if (!empty($filters['program'])) {
            $query->where('students.program', $filters['program']);
        }

        if (!empty($filters['student_ids'])) {
            $query->whereIn('students.id', $filters['student_ids']);
        }

        $query->orderBy('students.last_name')->orderBy('students.first_name');

        return $query;
    }

    /**
     * Get estimated row count for a query (fast)
     */
    public function estimateRowCount(Builder $query): int
    {
        // Use EXPLAIN for estimate on large tables
        $sql = $query->toSql();

        // For prototyping, use actual count (in production, use EXPLAIN ANALYZE)
        return $query->count();
    }
}
