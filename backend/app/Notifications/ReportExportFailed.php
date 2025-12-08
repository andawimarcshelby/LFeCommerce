<?php

namespace App\Notifications;

use App\Models\ReportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportExportFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected ReportJob $reportJob;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReportJob $reportJob)
    {
        $this->reportJob = $reportJob;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reportTitle = $this->getReportTitle();

        return (new MailMessage)
            ->error()
            ->subject("Report Export Failed: {$reportTitle}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Unfortunately, your {$reportTitle} failed to generate.")
            ->line("**Error Details:**")
            ->line($this->reportJob->error_message ?: 'Unknown error occurred')
            ->line("**What you can do:**")
            ->line("• Try generating the report again")
            ->line("• Reduce the date range or apply more filters")
            ->line("• Contact support if the issue persists")
            ->line('We apologize for the inconvenience.');
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_id' => $this->reportJob->id,
            'report_type' => $this->reportJob->report_type,
            'format' => $this->reportJob->format,
            'error_message' => $this->reportJob->error_message,
            'title' => $this->getReportTitle() . ' Failed',
            'message' => 'Your report export has failed: ' . $this->reportJob->error_message,
        ];
    }

    /**
     * Get human-readable report title
     */
    private function getReportTitle(): string
    {
        return match ($this->reportJob->report_type) {
            'detail' => 'Course Events Detail Report',
            'summary' => 'Course Activity Summary Report',
            'top_n' => 'Top Students Report',
            'per_student' => 'Per-Student Activity Report',
            default => 'LMS Activity Report',
        };
    }
}
