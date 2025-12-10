<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddSampleEvents extends Command
{
    protected $signature = 'test:add-events {count=1000}';
    protected $description = 'Add sample course events for testing';

    public function handle()
    {
        $count = $this->argument('count');

        $this->info("Adding $count sample events...");

        // Get student, course, and term IDs
        $studentIds = DB::table('students')->pluck('id')->toArray();
        $courseIds = DB::table('courses')->pluck('id')->toArray();
        $termId = DB::table('terms')->value('id');

        if (empty($studentIds) || empty($courseIds) || !$termId) {
            $this->error('Missing dimension data. Run MinimalTestSeeder first.');
            return 1;
        }

        $eventTypes = ['page_view', 'video_watch', 'quiz_attempt', 'assignment_submit', 'forum_post'];

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            DB::table('course_events')->insert([
                'event_type' => $eventTypes[array_rand($eventTypes)],
                'student_id' => $studentIds[array_rand($studentIds)],
                'course_id' => $courseIds[array_rand($courseIds)],
                'term_id' => $termId,
                'event_data' => json_encode(['duration' => rand(30, 3600), 'test' => true]),
                'occurred_at' => now()->subDays(rand(1, 60)),
                'created_at' => now(),
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Added $count events successfully!");

        return 0;
    }
}
