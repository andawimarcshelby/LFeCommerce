<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ReportJob;
use App\Notifications\ReportExportCompleted;
use App\Notifications\ReportExportFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /** @test */
    public function report_export_completed_sends_notification()
    {
        $user = User::factory()->create();

        $job = ReportJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'file_path' => 'reports/test.pdf',
        ]);

        $user->notify(new ReportExportCompleted($job));

        Notification::assertSentTo($user, ReportExportCompleted::class);
    }

    /** @test */
    public function report_export_failed_sends_notification()
    {
        $user = User::factory()->create();

        $job = ReportJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'failed',
            'error_message' => 'Database connection failed',
        ]);

        $user->notify(new ReportExportFailed($job));

        Notification::assertSentTo($user, ReportExportFailed::class);
    }

    /** @test */
    public function completed_notification_has_correct_mail_content()
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $job = ReportJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'format' => 'pdf',
            'report_type' => 'detail',
        ]);

        $notification = new ReportExportCompleted($job);
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('John Doe', $mail->greeting);
        $this->assertStringContainsString('pdf', $mail->subject);
    }

    /** @test */
    public function failed_notification_includes_error_message()
    {
        $user = User::factory()->create(['name' => 'Jane Smith']);

        $job = ReportJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'failed',
            'error_message' => 'Out of memory',
        ]);

        $notification = new ReportExportFailed($job);
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('Jane Smith', $mail->greeting);
        $this->assertStringContainsString('failed', strtolower($mail->subject));
    }

    /** @test */
    public function notifications_stored_in_database()
    {
        $user = User::factory()->create();

        $job = ReportJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        // Send notification
        Notification::send($user, new ReportExportCompleted($job));

        // Check database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    /** @test */
    public function notification_data_includes_job_details()
    {
        $user = User::factory()->create();

        $job = ReportJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'format' => 'excel',
            'report_type' => 'summary',
        ]);

        $notification = new ReportExportCompleted($job);
        $data = $notification->toArray($user);

        $this->assertArrayHasKey('job_id', $data);
        $this->assertArrayHasKey('format', $data);
        $this->assertArrayHasKey('report_type', $data);
        $this->assertEquals($job->id, $data['job_id']);
        $this->assertEquals('excel', $data['format']);
    }
}
