<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Term;
use App\Models\Course;
use App\Models\Student;
use App\Models\CourseEvent;
use Carbon\Carbon;

class MinimalTestSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Creating minimal test data...');

        // Create 1 term
        $term = Term::create([
            'term_code' => '2024-FALL',
            'name' => 'Fall 2024',
            'start_date' => Carbon::parse('2024-09-01'),
            'end_date' => Carbon::parse('2024-12-15'),
            'is_active' => true,
        ]);

        // Create 5 courses
        $courses = [];
        for ($i = 1; $i <= 5; $i++) {
            $courses[] = Course::create([
                'course_code' => 'CS' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'course_name' => 'Computer Science ' . $i,
                'term_id' => $term->id,
                'department' => 'Computer Science',
                'credits' => 3,
                'enrollment_count' => 20,
            ]);
        }

        // Create 20 students
        $students = [];
        for ($i = 1; $i <= 20; $i++) {
            $students[] = Student::create([
                'student_number' => 'STU' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'first_name' => 'Student',
                'last_name' => 'Test' . $i,
                'email' => 'student' . $i . '@test.edu',
                'program' => 'Computer Science',
                'year_level' => rand(1, 4),
                'enrollment_status' => 'active',
            ]);
        }

        // Create 1000 course events for testing
        $this->command->info('Creating 1,000 course events...');

        $eventTypes = ['page_view', 'video_watch', 'quiz_attempt', 'assignment_submit', 'forum_post'];

        for ($i = 0; $i < 1000; $i++) {
            CourseEvent::create([
                'occurred_at' => Carbon::now()->subDays(rand(1, 60)),
                'student_id' => $students[array_rand($students)]->id,
                'course_id' => $courses[array_rand($courses)]->id,
                'term_id' => $term->id,
                'event_type' => $eventTypes[array_rand($eventTypes)],
                'event_data' => [
                    'duration_seconds' => rand(30, 3600),
                    'test' => true
                ],
            ]);

            if (($i + 1) % 100 == 0) {
                $this->command->info('Created ' . ($i + 1) . ' events...');
            }
        }

        $this->command->info('✓ Minimal test data created successfully!');
        $this->command->info('  - 1 Term');
        $this->command->info('  - 5 Courses');
        $this->command->info('  - 20 Students');
        $this->command->info('  - 1,000 Events');
    }
}
