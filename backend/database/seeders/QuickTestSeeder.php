<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Quick test seeder with reduced data volume for validation
 * Use this for testing, then switch to LmsActivitySeeder for full 10M dataset
 */
class QuickTestSeeder extends Seeder
{
    private const TARGET_EVENTS = 100_000; // Reduced from 10M for testing
    private const CHUNK_SIZE = 10_000;
    private const NUM_STUDENTS = 1_000; // Reduced from 5K
    private const NUM_COURSES = 50; // Reduced from 100
    private const NUM_INSTRUCTORS = 50; // Reduced from 200

    public function run(): void
    {
        $this->command->info('Starting Quick Test Seeder...');
        $this->command->info('Target: ' . number_format(self::TARGET_EVENTS) . ' events (reduced for testing)');

        $startTime = microtime(true);

        // Get student/course IDs (already seeded by LmsActivitySeeder)
        $studentCount = DB::table('students')->count();
        $courseCount = DB::table('courses')->count();

        if ($studentCount == 0 || $courseCount == 0) {
            $this->command->error('Students and courses must be seeded first. Run LmsActivitySeeder first.');
            return;
        }

        $this->command->info("Using existing: {$studentCount} students, {$courseCount} courses");

        // Seed only the high-volume tables
        $this->seedCourseEvents();

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->command->info("\n✓ Quick test seeding completed in {$elapsed} seconds");
    }

    private function seedCourseEvents(): void
    {
        $this->command->info("\nSeeding " . number_format(self::TARGET_EVENTS) . " course events...");

        $eventTypes = [
            'page_view' => 0.50,
            'video_watch' => 0.20,
            'quiz_attempt' => 0.15,
            'discussion_post' => 0.10,
            'file_download' => 0.05,
        ];

        $resourceTypes = ['video', 'quiz', 'assignment', 'page', 'forum', 'file'];
        $studentCount = DB::table('students')->count();
        $courseCount = DB::table('courses')->count();

        $totalChunks = ceil(self::TARGET_EVENTS / self::CHUNK_SIZE);
        $bar = $this->command->getOutput()->createProgressBar($totalChunks);

        $eventsInserted = 0;

        for ($chunk = 0; $chunk < $totalChunks; $chunk++) {
            $events = [];
            $chunkSize = min(self::CHUNK_SIZE, self::TARGET_EVENTS - $eventsInserted);

            for ($i = 0; $i < $chunkSize; $i++) {
                $eventType = $this->weightedRandom($eventTypes);
                $studentId = rand(1, $studentCount);
                $courseId = rand(1, $courseCount);
                $occurredAt = $this->randomTimestamp();

                $events[] = [
                    'event_type' => $eventType,
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'term_id' => rand(1, 4),
                    'instructor_id' => rand(1, min(50, DB::table('instructors')->count())),
                    'resource_type' => $resourceTypes[array_rand($resourceTypes)],
                    'resource_id' => rand(1, 1000),
                    'event_data' => json_encode([
                        'duration_seconds' => $eventType == 'video_watch' ? rand(60, 3600) : null,
                        'score' => $eventType == 'quiz_attempt' ? rand(0, 100) : null,
                    ]),
                    'occurred_at' => $occurredAt,
                    'created_at' => now(),
                ];
            }

            $this->bulkInsert('course_events', $events);
            $eventsInserted += count($events);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('  ✓ Created ' . number_format($eventsInserted) . ' course events');
    }

    private function bulkInsert(string $table, array $records): void
    {
        if (empty($records))
            return;

        $columns = array_keys($records[0]);
        $values = [];
        $bindings = [];

        foreach ($records as $record) {
            $placeholders = [];
            foreach ($columns as $column) {
                $placeholders[] = '?';
                $bindings[] = $record[$column];
            }
            $values[] = '(' . implode(',', $placeholders) . ')';
        }

        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES " . implode(',', $values);
        DB::insert($sql, $bindings);
    }

    private function randomTimestamp(): string
    {
        $daysAgo = rand(0, 365);
        $hoursAgo = rand(0, 23);
        $minutesAgo = rand(0, 59);

        return Carbon::now()
            ->subDays($daysAgo)
            ->subHours($hoursAgo)
            ->subMinutes($minutesAgo)
            ->toDateTimeString();
    }

    private function weightedRandom(array $weights): string
    {
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0;

        foreach ($weights as $value => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}
