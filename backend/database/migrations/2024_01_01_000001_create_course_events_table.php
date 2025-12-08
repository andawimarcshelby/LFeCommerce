<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the partitioned course_events table
        DB::statement("
            CREATE TABLE course_events (
                id BIGSERIAL,
                event_type VARCHAR(50) NOT NULL,
                student_id BIGINT NOT NULL,
                course_id BIGINT NOT NULL,
                term_id INT NOT NULL,
                instructor_id BIGINT,
                resource_type VARCHAR(50),
                resource_id BIGINT,
                event_data JSONB,
                occurred_at TIMESTAMPTZ NOT NULL,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                PRIMARY KEY (id, occurred_at)
            ) PARTITION BY RANGE (occurred_at)
        ");

        // Create monthly partitions for 2024
        $partitions = [
            ['2024_01', '2024-01-01', '2024-02-01'],
            ['2024_02', '2024-02-01', '2024-03-01'],
            ['2024_03', '2024-03-01', '2024-04-01'],
            ['2024_04', '2024-04-01', '2024-05-01'],
            ['2024_05', '2024-05-01', '2024-06-01'],
            ['2024_06', '2024-06-01', '2024-07-01'],
            ['2024_07', '2024-07-01', '2024-08-01'],
            ['2024_08', '2024-08-01', '2024-09-01'],
            ['2024_09', '2024-09-01', '2024-10-01'],
            ['2024_10', '2024-10-01', '2024-11-01'],
            ['2024_11', '2024-11-01', '2024-12-01'],
            ['2024_12', '2024-12-01', '2025-01-01'],
        ];

        foreach ($partitions as [$suffix, $from, $to]) {
            DB::statement("
                CREATE TABLE course_events_{$suffix} PARTITION OF course_events
                FOR VALUES FROM ('{$from}') TO ('{$to}')
            ");
        }

        // Create 2025 partitions
        $partitions_2025 = [
            ['2025_01', '2025-01-01', '2025-02-01'],
            ['2025_02', '2025-02-01', '2025-03-01'],
            ['2025_03', '2025-03-01', '2025-04-01'],
            ['2025_04', '2025-04-01', '2025-05-01'],
            ['2025_05', '2025-05-01', '2025-06-01'],
            ['2025_06', '2025-06-01', '2025-07-01'],
            ['2025_07', '2025-07-01', '2025-08-01'],
            ['2025_08', '2025-08-01', '2025-09-01'],
            ['2025_09', '2025-09-01', '2025-10-01'],
            ['2025_10', '2025-10-01', '2025-11-01'],
            ['2025_11', '2025-11-01', '2025-12-01'],
            ['2025_12', '2025-12-01', '2026-01-01'],
        ];

        foreach ($partitions_2025 as [$suffix, $from, $to]) {
            DB::statement("
                CREATE TABLE course_events_{$suffix} PARTITION OF course_events
                FOR VALUES FROM ('{$from}') TO ('{$to}')
            ");
        }

        // Create indexes on partitioned table
        DB::statement('CREATE INDEX idx_course_events_occurred ON course_events(occurred_at DESC)');
        DB::statement('CREATE INDEX idx_course_events_student ON course_events(student_id, occurred_at)');
        DB::statement('CREATE INDEX idx_course_events_course ON course_events(course_id, occurred_at)');
        DB::statement('CREATE INDEX idx_course_events_term ON course_events(term_id, event_type, occurred_at)');
        DB::statement('CREATE INDEX idx_course_events_data ON course_events USING GIN (event_data)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS course_events CASCADE');
    }
};
