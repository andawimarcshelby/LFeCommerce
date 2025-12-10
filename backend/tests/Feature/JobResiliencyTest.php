<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ReportJob;
use App\Jobs\GenerateReportExportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class JobResiliencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with proper password
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function job_saves_checkpoint_data(): void
    {
        $reportJob = ReportJob::create([
            'user_id' => $this->user->id,
            'report_type' => 'detail',
            'format' => 'pdf',
            'filters' => ['term_id' => 1],
            'status' => 'queued',
        ]);

        $checkpointData = [
            'processed_rows' => 5000,
            'current_section' => 'section_2',
            'timestamp' => now()->toDateTimeString(),
        ];

        $reportJob->saveCheckpoint($checkpointData);
        $reportJob->refresh();

        $this->assertEquals($checkpointData, $reportJob->checkpoint_data);
        $this->assertEquals(1, $reportJob->retry_count);
    }

    /** @test */
    public function job_can_check_resume_capability(): void
    {
        // Job with checkpoint and running status
        $jobWithCheckpoint = ReportJob::create([
            'user_id' => $this->user->id,
            'report_type' => 'summary',
            'format' => 'xlsx',
            'filters' => [],
            'status' => 'running',
            'checkpoint_data' => ['processed' => 1000],
            'retry_count' => 2,
        ]);

        $this->assertTrue($jobWithCheckpoint->canResume());

        // Job without checkpoint
        $jobWithoutCheckpoint = ReportJob::create([
            'user_id' => $this->user->id,
            'report_type' => 'top_n',
            'format' => 'pdf',
            'filters' => [],
            'status' => 'running',
            'checkpoint_data' => null,
        ]);

        $this->assertFalse($jobWithoutCheckpoint->canResume());

        // Job with too many retries
        $jobTooManyRetries = ReportJob::create([
            'user_id' => $this->user->id,
            'report_type' => 'detail',
            'format' => 'pdf',
            'filters' => [],
            'status' => 'running',
            'checkpoint_data' => ['processed' => 500],
            'retry_count' => 5, // Max is 5
        ]);

        $this->assertFalse($jobTooManyRetries->canResume());
    }

    /** @test */
    public function job_clears_checkpoint_on_completion(): void
    {
        $reportJob = ReportJob::create([
            'user_id' => $this->user->id,
            'report_type' => 'per_student',
            'format' => 'xlsx',
            'filters' => [],
            'status' => 'running',
            'checkpoint_data' => ['processed' => 3000],
            'retry_count' => 1,
        ]);

        $reportJob->clearCheckpoint();
        $reportJob->refresh();

        $this->assertNull($reportJob->checkpoint_data);
        $this->assertEquals(1, $reportJob->retry_count); // Retry count persists
    }

    /** @test */
    public function report_job_has_user_relationship(): void
    {
        $reportJob = ReportJob::create([
            'user_id' => $this->user->id,
            'report_type' => 'summary',
            'format' => 'pdf',
            'filters' => [],
            'status' => 'queued',
        ]);

        $this->assertInstanceOf(User::class, $reportJob->user);
        $this->assertEquals($this->user->id, $reportJob->user->id);
    }

    /** @test */
    public function job_retry_logic_is_configured(): void
    {
        Queue::fake();

        $reportJob = ReportJob::create([
            'user_id' => $this->user->id,
            'report_type' => 'detail',
            'format' => 'pdf',
            'filters' => [],
            'status' => 'queued',
        ]);

        GenerateReportExportJob::dispatch($reportJob);

        Queue::assertPushed(GenerateReportExportJob::class, function ($job) {
            // Job should have retry configuration
            return $job->tries === 3 &&
                $job->timeout === 3600 &&
                $job->backoff === [60, 300, 900];
        });
    }
}
