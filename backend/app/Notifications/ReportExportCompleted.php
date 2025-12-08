<?php

namespace App\Notifications;

use App\Models\ReportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportExportCompleted extends Notification implements ShouldQueue
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
        $downloadUrl = $this->reportJob->download_url;

        return (new MailMessage)
            ->subject("Report Export Completed: {$reportTitle}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your {$reportTitle} has been generated successfully.")
            ->line("**Report Details:**")
            ->line("• Format: " . strtoupper($this->reportJob->format))
            ->line("• Total Rows: " . number_format($this->reportJob->total_rows))
            ->line("• File Size: " . $this->reportJob->file_size_human)
            ->action('Download Report', $downloadUrl)
            ->line('This link will expire in 24 hours.')
            ->line('Thank you for using our reporting system!');
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
            'file_size' => $this->reportJob->file_size,
            'download_url' => $this->reportJob->download_url,
            'title' => $this->getReportTitle() . ' Completed',
            'message' => 'Your report export has been completed successfully.',
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
