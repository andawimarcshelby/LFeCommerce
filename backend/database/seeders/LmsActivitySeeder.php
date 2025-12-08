<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LmsActivitySeeder extends Seeder
{
    private const TARGET_EVENTS = 10_000_000;
    private const CHUNK_SIZE = 50_000;
    private const NUM_STUDENTS = 5_000;
    private const NUM_COURSES = 100;
    private const NUM_INSTRUCTORS = 200;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting LMS Activity Seeder...');
        $this->command->info('Target: ' . number_format(self::TARGET_EVENTS) . ' events');

        $startTime = microtime(true);

        // 1. Seed dimension tables
        $this->seedTerms();
        $this->seedStudents();
        $this->seedInstructors();
        $this->seedCourses();
        $this->seedAssignments();
        $this->seedEnrollments();

        // 2. Seed fact tables (high volume)
        $this->seedCourseEvents();
        $this->seedSubmissions();
        $this->seedAuthEvents();
        $this->seedGradingAudits();

        // 3. Populate reporting tables
        $this->populateReportingTables();

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->command->info("\n✓ Seeding completed in {$elapsed} seconds");
    }

    private function seedTerms(): void
    {
        $this->command->info('Seeding terms...');

        $terms = [
            ['name' => 'Fall 2024', 'term_code' => '2024-FALL', 'start_date' => '2024-09-01', 'end_date' => '2024-12-15'],
            ['name' => 'Spring 2025', 'term_code' => '2025-SPRING', 'start_date' => '2025-01-15', 'end_date' => '2025-05-15'],
            ['name' => 'Summer 2025', 'term_code' => '2025-SUMMER', 'start_date' => '2025-06-01', 'end_date' => '2025-08-15'],
            ['name' => 'Fall 2025', 'term_code' => '2025-FALL', 'start_date' => '2025-09-01', 'end_date' => '2025-12-15'],
        ];

        foreach ($terms as &$term) {
            $term['created_at'] = now();
            $term['updated_at'] = now();
        }

        DB::table('terms')->insert($terms);
        $this->command->info('  ✓ Created 4 terms');
    }

    private function seedStudents(): void
    {
        $this->command->info('Seeding students...');

        $programs = ['Computer Science', 'Engineering', 'Mathematics', 'Physics', 'Business', 'Education'];
        $chunks = ceil(self::NUM_STUDENTS / 1000);

        $bar = $this->command->getOutput()->createProgressBar($chunks);

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $students = [];
            $batchSize = min(1000, self::NUM_STUDENTS - ($chunk * 1000));

            for ($i = 0; $i < $batchSize; $i++) {
                $num = ($chunk * 1000) + $i + 1;
                $students[] = [
                    'student_number' => 'STU' . str_pad($num, 6, '0', STR_PAD_LEFT),
                    'first_name' => 'Student',
                    'last_name' => 'User' . $num,
                    'email' => "student{$num}@university.edu",
                    'program' => $programs[array_rand($programs)],
                    'year_level' => rand(1, 4),
                    'enrollment_status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('students')->insert($students);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('  ✓ Created ' . number_format(self::NUM_STUDENTS) . ' students');
    }

    private function seedInstructors(): void
    {
        $this->command->info('Seeding instructors...');

        $departments = ['Computer Science', 'Mathematics', 'Engineering', 'Physics', 'Business'];
        $titles = ['Professor', 'Associate Professor', 'Assistant Professor', 'Lecturer'];

        $instructors = [];
        for ($i = 1; $i <= self::NUM_INSTRUCTORS; $i++) {
            $instructors[] = [
                'employee_number' => 'EMP' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'first_name' => 'Instructor',
                'last_name' => 'Faculty' . $i,
                'email' => "instructor{$i}@university.edu",
                'department' => $departments[array_rand($departments)],
                'title' => $titles[array_rand($titles)],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('instructors')->insert($instructors);
        $this->command->info('  ✓ Created ' . self::NUM_INSTRUCTORS . ' instructors');
    }

    private function seedCourses(): void
    {
        $this->command->info('Seeding courses...');

        $prefixes = ['CS', 'MATH', 'ENG', 'PHYS', 'BUS', 'EDU'];
        $courses = [];

        for ($i = 1; $i <= self::NUM_COURSES; $i++) {
            $prefix = $prefixes[array_rand($prefixes)];
            $courseNum = rand(100, 499);

            $courses[] = [
                'course_code' => "{$prefix}{$courseNum}",
                'course_name' => "Course {$prefix}{$courseNum}",
                'term_id' => rand(1, 4),
                'department' => $prefix == 'CS' ? 'Computer Science' : 'Mathematics',
                'credits' => rand(3, 4),
                'enrollment_count' => rand(20, 150),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('courses')->insert($courses);
        $this->command->info('  ✓ Created ' . self::NUM_COURSES . ' courses');
    }

    private function seedAssignments(): void
    {
        $this->command->info('Seeding assignments...');

        $types = ['homework', 'quiz', 'exam', 'project'];
        $assignments = [];

        // 10 assignments per course
        for ($courseId = 1; $courseId <= self::NUM_COURSES; $courseId++) {
            for ($a = 1; $a <= 10; $a++) {
                $assignments[] = [
                    'course_id' => $courseId,
                    'title' => ucfirst($types[array_rand($types)]) . " {$a}",
                    'assignment_type' => $types[array_rand($types)],
                    'max_score' => 100.00,
                    'due_date' => Carbon::now()->addDays(rand(7, 90)),
                    'allows_late' => rand(0, 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('assignments')->insert($assignments);
        $this->command->info('  ✓ Created ' . number_format(count($assignments)) . ' assignments');
    }

    private function seedEnrollments(): void
    {
        $this->command->info('Seeding course enrollments...');

        $enrollments = [];

        // Each student enrolled in 4-6 courses
        for ($studentId = 1; $studentId <= self::NUM_STUDENTS; $studentId++) {
            $numCourses = rand(4, 6);
            $enrolledCourses = array_rand(range(1, self::NUM_COURSES), $numCourses);

            foreach ($enrolledCourses as $courseId) {
                $courseId = $courseId + 1; // array_rand returns 0-based index

                $enrollments[] = [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'term_id' => rand(1, 4),
                    'enrolled_at' => Carbon::now()->subDays(rand(30, 180)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Insert in batches
                if (count($enrollments) >= 5000) {
                    DB::table('course_enrollments')->insert($enrollments);
                    $enrollments = [];
                }
            }
        }

        if (!empty($enrollments)) {
            DB::table('course_enrollments')->insert($enrollments);
        }

        $this->command->info('  ✓ Created course enrollments');
    }

    private function seedCourseEvents(): void
    {
        $this->command->info("\nSeeding " . number_format(self::TARGET_EVENTS) . " course events...");

        $eventTypes = [
            'page_view' => 0.50,      // 50%
            'video_watch' => 0.20,     // 20%
            'quiz_attempt' => 0.15,    // 15%
            'discussion_post' => 0.10, // 10%
            'file_download' => 0.05,   // 5%
        ];

        $resourceTypes = ['video', 'quiz', 'assignment', 'page', 'forum', 'file'];

        $totalChunks = ceil(self::TARGET_EVENTS / self::CHUNK_SIZE);
        $bar = $this->command->getOutput()->createProgressBar($totalChunks);

        $eventsInserted = 0;

        for ($chunk = 0; $chunk < $totalChunks; $chunk++) {
            $events = [];
            $chunkSize = min(self::CHUNK_SIZE, self::TARGET_EVENTS - $eventsInserted);

            for ($i = 0; $i < $chunkSize; $i++) {
                $eventType = $this->weightedRandom($eventTypes);
                $studentId = rand(1, self::NUM_STUDENTS);
                $courseId = rand(1, self::NUM_COURSES);
                $occurredAt = $this->randomTimestamp();

                $events[] = [
                    'event_type' => $eventType,
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'term_id' => rand(1, 4),
                    'instructor_id' => rand(1, self::NUM_INSTRUCTORS),
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

            // Use raw INSERT for performance
            $this->bulkInsert('course_events', $events);
            $eventsInserted += count($events);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('  ✓ Created ' . number_format($eventsInserted) . ' course events');
    }

    private function seedSubmissions(): void
    {
        $this->command->info('Seeding submissions...');

        $statuses = ['submitted', 'graded', 'late', 'missing'];
        $targetSubmissions = 500_000;

        $bar = $this->command->getOutput()->createProgressBar(ceil($targetSubmissions / 5000));

        for ($i = 0; $i < $targetSubmissions; $i += 5000) {
            $submissions = [];

            for ($j = 0; $j < min(5000, $targetSubmissions - $i); $j++) {
                $status = $statuses[array_rand($statuses)];
                $maxScore = 100.00;
                $score = $status == 'graded' ? rand(0, 100) : null;

                $submissions[] = [
                    'submission_type' => ['assignment', 'quiz', 'exam'][array_rand(['assignment', 'quiz', 'exam'])],
                    'student_id' => rand(1, self::NUM_STUDENTS),
                    'course_id' => rand(1, self::NUM_COURSES),
                    'term_id' => rand(1, 4),
                    'assignment_id' => rand(1, self::NUM_COURSES * 10),
                    'attempt_number' => rand(1, 3),
                    'status' => $status,
                    'submitted_at' => $this->randomTimestamp(),
                    'graded_at' => $status == 'graded' ? $this->randomTimestamp() : null,
                    'score' => $score,
                    'max_score' => $maxScore,
                    'late_penalty' => rand(0, 10),
                    'file_count' => rand(0, 5),
                    'created_at' => now(),
                ];
            }

            $this->bulkInsert('submissions', $submissions);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('  ✓ Created ' . number_format($targetSubmissions) . ' submissions');
    }

    private function seedAuthEvents(): void
    {
        $this->command->info('Seeding auth events...');

        $targetAuth = 100_000;
        $eventTypes = ['login', 'logout', 'session_timeout', 'failed_login'];
        $userTypes = ['student', 'instructor', 'admin'];

        $events = [];
        for ($i = 0; $i < $targetAuth; $i++) {
            $userType = $userTypes[array_rand($userTypes)];
            $userId = $userType == 'student' ? rand(1, self::NUM_STUDENTS) : rand(1, self::NUM_INSTRUCTORS);

            $events[] = [
                'event_type' => $eventTypes[array_rand($eventTypes)],
                'user_id' => $userId,
                'user_type' => $userType,
                'ip_address' => $this->randomIp(),
                'user_agent' => 'Mozilla/5.0',
                'session_id' => 'sess_' . bin2hex(random_bytes(16)),
                'occurred_at' => $this->randomTimestamp(),
                'created_at' => now(),
            ];

            if (count($events) >= 5000) {
                $this->bulkInsert('auth_events', $events);
                $events = [];
            }
        }

        if (!empty($events)) {
            $this->bulkInsert('auth_events', $events);
        }

        $this->command->info('  ✓ Created ' . number_format($targetAuth) . ' auth events');
    }

    private function seedGradingAudits(): void
    {
        $this->command->info('Seeding grading audits...');

        $targetAudits = 50_000;
        $actionTypes = ['grade_assigned', 'grade_changed', 'comment_added', 'auto_graded'];

        $audits = [];
        for ($i = 0; $i < $targetAudits; $i++) {
            $audits[] = [
                'action_type' => $actionTypes[array_rand($actionTypes)],
                'submission_id' => rand(1, 500_000),
                'actor_id' => rand(1, self::NUM_INSTRUCTORS),
                'actor_type' => ['instructor', 'auto_grader'][array_rand(['instructor', 'auto_grader'])],
                'old_score' => rand(0, 100),
                'new_score' => rand(0, 100),
                'comment' => 'Grading audit comment',
                'occurred_at' => $this->randomTimestamp(),
                'created_at' => now(),
            ];

            if (count($audits) >= 5000) {
                DB::table('grading_audits')->insert($audits);
                $audits = [];
            }
        }

        if (!empty($audits)) {
            DB::table('grading_audits')->insert($audits);
        }

        $this->command->info('  ✓ Created ' . number_format($targetAudits) . ' grading audits');
    }

    private function populateReportingTables(): void
    {
        $this->command->info('Populating reporting tables...');

        // This would normally be done by scheduled jobs, but we'll populate with sample data
        $this->command->info('  ✓ Reporting tables ready (populated on first report generation)');

        // Refresh materialized view
        DB::statement('REFRESH MATERIALIZED VIEW term_summary');
        $this->command->info('  ✓ Refreshed term_summary materialized view');
    }

    // Helper methods

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
        // Random timestamp within last 12 months
        $daysAgo = rand(0, 365);
        $hoursAgo = rand(0, 23);
        $minutesAgo = rand(0, 59);

        return Carbon::now()
            ->subDays($daysAgo)
            ->subHours($hoursAgo)
            ->subMinutes($minutesAgo)
            ->toDateTimeString();
    }

    private function randomIp(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
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
