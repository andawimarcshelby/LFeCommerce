<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledReport;
use App\Jobs\GenerateReportExportJob;
use App\Models\ReportJob;

class ProcessScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:process-scheduled 
                            {--dry-run : Show what would be processed without executing}';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled reports that are due for execution';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find all scheduled reports due for execution
        $scheduledReports = ScheduledReport::active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->get();

        if ($scheduledReports->isEmpty()) {
            $this->info('No scheduled reports due for execution.');
            return self::SUCCESS;
        }

        $this->info("Found {$scheduledReports->count()} scheduled report(s) to process.");
        $this->newLine();

        $processed = 0;
        $failed = 0;

        foreach ($scheduledReports as $schedule) {
            try {
                if ($dryRun) {
                    $this->line("→ Would process: {$schedule->name} (ID: {$schedule->id})");
                    $this->line("  Frequency: {$schedule->frequency_display}");
                    $this->line("  Report: {$schedule->report_type} ({$schedule->format})");
                    continue;
                }

                $this->info("Processing: {$schedule->name}");

                // Create a report job
                $reportJob = ReportJob::create([
                    'user_id' => $schedule->user_id,
                    'report_type' => $schedule->report_type,
                    'format' => $schedule->format,
                    'filters' => $schedule->filters ?? [],
                    'status' => 'queued',
                    'metadata' => [
                        'scheduled_report_id' => $schedule->id,
                        'scheduled_name' => $schedule->name,
                    ],
                ]);

                // Dispatch the export job
                GenerateReportExportJob::dispatch($reportJob);

                // Mark as executed
                $schedule->markExecuted();

                $this->line("✓ Created export job #{$reportJob->id}");
                $this->line("  Next run: {$schedule->next_run_at->format('Y-m-d H:i:s')}");

                $processed++;

            } catch (\Exception $e) {
                $this->error("✗ Failed: {$schedule->name}");
                $this->error("  Error: {$e->getMessage()}");

                if (!$dryRun) {
                    $schedule->markFailed($e->getMessage());
                }

                $failed++;
            }

            $this->newLine();
        }

        // Summary
        if (!$dryRun) {
            $this->info("Summary:");
            $this->line("  Processed: {$processed}");
            if ($failed > 0) {
                $this->warn("  Failed: {$failed}");
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
