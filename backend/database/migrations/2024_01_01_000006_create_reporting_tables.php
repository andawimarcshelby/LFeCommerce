<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Daily activity summary per course
        Schema::create('course_daily_activity', function (Blueprint $table) {
            $table->date('report_date');
            $table->unsignedBigInteger('course_id');
            $table->unsignedInteger('term_id');
            $table->unsignedBigInteger('total_events')->default(0);
            $table->unsignedInteger('unique_students')->default(0);
            $table->unsignedBigInteger('page_views')->default(0);
            $table->unsignedInteger('video_minutes')->default(0);
            $table->unsignedInteger('quiz_attempts')->default(0);
            $table->unsignedInteger('discussion_posts')->default(0);
            $table->decimal('avg_session_duration_minutes', 10, 2)->nullable();

            $table->primary(['report_date', 'course_id', 'term_id']);
            $table->index(['course_id', 'report_date']);
        });

        // Student engagement summary per course
        Schema::create('student_course_engagement', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedInteger('term_id');
            $table->unsignedBigInteger('total_events')->default(0);
            $table->unsignedInteger('total_logins')->default(0);
            $table->timestampTz('last_activity_at')->nullable();
            $table->unsignedInteger('assignments_submitted')->default(0);
            $table->unsignedInteger('assignments_graded')->default(0);
            $table->decimal('avg_score', 5, 2)->nullable();
            $table->decimal('participation_score', 5, 2)->nullable();

            $table->primary(['student_id', 'course_id', 'term_id']);
            $table->index(['course_id', 'term_id']);
        });

        // Materialized view for term-level rollups
        DB::statement("
            CREATE MATERIALIZED VIEW term_summary AS
            SELECT 
                t.id as term_id,
                t.name as term_name,
                c.id as course_id,
                c.course_code,
                c.course_name,
                COUNT(DISTINCT ce.student_id) as enrolled_students,
                SUM(cda.total_events) as total_events,
                AVG(sce.avg_score) as avg_course_score,
                SUM(sce.assignments_submitted) as total_submissions
            FROM terms t
            JOIN courses c ON c.term_id = t.id
            LEFT JOIN course_enrollments ce ON ce.course_id = c.id AND ce.term_id = t.id
            LEFT JOIN course_daily_activity cda ON cda.course_id = c.id AND cda.term_id = t.id
            LEFT JOIN student_course_engagement sce ON sce.course_id = c.id AND sce.term_id = t.id
            GROUP BY t.id, t.name, c.id, c.course_code, c.course_name
        ");

        DB::statement('CREATE UNIQUE INDEX idx_term_summary ON term_summary(term_id, course_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS term_summary');
        Schema::dropIfExists('student_course_engagement');
        Schema::dropIfExists('course_daily_activity');
    }
};
