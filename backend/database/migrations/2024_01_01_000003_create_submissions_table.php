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
        // Create partitioned submissions table
        DB::statement("
            CREATE TABLE submissions (
                id BIGSERIAL,
                submission_type VARCHAR(30) NOT NULL,
                student_id BIGINT NOT NULL,
                course_id BIGINT NOT NULL,
                term_id INT NOT NULL,
                assignment_id BIGINT NOT NULL,
                attempt_number INT DEFAULT 1,
                status VARCHAR(20) NOT NULL,
                submitted_at TIMESTAMPTZ,
                graded_at TIMESTAMPTZ,
                score DECIMAL(5,2),
                max_score DECIMAL(5,2),
                late_penalty DECIMAL(5,2) DEFAULT 0,
                file_count INT DEFAULT 0,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                PRIMARY KEY (id, submitted_at)
            ) PARTITION BY RANGE (submitted_at)
        ");

        // Create partitions for 2024-2025
        for ($year = 2024; $year <= 2025; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                $nextMonth = $month == 12 ? 1 : $month + 1;
                $nextYear = $month == 12 ? $year + 1 : $year;
                $nextMonthStr = str_pad($nextMonth, 2, '0', STR_PAD_LEFT);

                $suffix = "{$year}_" . $monthStr;
                $from = "{$year}-{$monthStr}-01";
                $to = "{$nextYear}-{$nextMonthStr}-01";

                DB::statement("
                    CREATE TABLE submissions_{$suffix} PARTITION OF submissions
                    FOR VALUES FROM ('{$from}') TO ('{$to}')
                ");
            }
        }

        // Create indexes
        DB::statement('CREATE INDEX idx_submissions_student ON submissions(student_id, submitted_at)');
        DB::statement('CREATE INDEX idx_submissions_course ON submissions(course_id, term_id)');
        DB::statement('CREATE INDEX idx_submissions_status ON submissions(status, submitted_at)');
        DB::statement('CREATE INDEX idx_submissions_assignment ON submissions(assignment_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS submissions CASCADE');
    }
};
