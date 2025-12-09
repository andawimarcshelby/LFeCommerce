<?php

namespace App\Jobs;

use App\Models\ReportJob;
use App\Services\ReportService;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;

    protected int $reportJobId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $reportJobId)
    {
        $this->reportJobId = $reportJobId;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService, PdfService $pdfService): void
    {
        $reportJob = ReportJob::find($this->reportJobId);

        if (!$reportJob) {
            Log::error("Report job not found: {$this->reportJobId}");
            return;
        }

        try {
            // Mark as running
            $reportJob->update([
                'status' => 'running',
                'started_at' => now(),
            ]);

            $filters = $reportJob->filters ?? [];
            $reportType = $reportJob->report_type;
            $format = $reportJob->format;

            // Generate report based on format
            if ($format === 'pdf') {
                $filePath = $this->generatePdf($reportJob, $reportService, $pdfService, $filters, $reportType);
            } elseif ($format === 'xlsx' || $format === 'excel') {
                $excelService = app(\App\Services\ExcelService::class);
                $filePath = $this->generateExcel($reportJob, $reportService, $excelService, $filters, $reportType);
            } else {
                throw new \Exception("Unsupported format: {$format}");
            }

            // Get file size
            $fullPath = storage_path('app/' . $filePath);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

            // Get page count for PDFs
            $pageCount = null;
            if ($format === 'pdf' && file_exists($fullPath)) {
                try {
                    $pageCount = $pdfService->getPageCount($fullPath);
                } catch (\Exception $e) {
                    Log::warning("Could not get page count: " . $e->getMessage());
                }
            }

            // Mark as completed
            $reportJob->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'page_count' => $pageCount,
                'percent' => 100,
                'finished_at' => now(),
            ]);

            Log::info("Report job completed: {$this->reportJobId}");
        } catch (\Exception $e) {
            $this->handleFailure($reportJob, $e);
            throw $e;
        }
    }

    /**
     * Generate PDF report
     */
    private function generatePdf(
        ReportJob $reportJob,
        ReportService $reportService,
        PdfService $pdfService,
        array $filters,
        string $reportType
    ): string {
        $metadata = [
            'title' => ucfirst($reportType) . ' Report',
            'author' => $reportJob->user_id ? 'User #' . $reportJob->user_id : 'System',
            'subject' => 'E-commerce Report',
            'keywords' => 'report, ecommerce, ' . $reportType,
        ];

        // Progress callback
        $progressCallback = function ($processed, $total, $section = '') use ($reportJob) {
            $percent = $total > 0 ? round(($processed / $total) * 100, 2) : 0;
            $reportJob->update([
                'processed_rows' => $processed,
                'percent' => $percent,
                'current_section' => $section,
            ]);
        };

        // Handle different report types
        switch ($reportType) {
            case 'detail':
                return $this->generateDetailPdf($reportService, $pdfService, $filters, $metadata, $progressCallback);

            case 'summary':
                return $this->generateSummaryPdf($reportService, $pdfService, $filters, $metadata);

            case 'top-n':
                return $this->generateTopNPdf($reportService, $pdfService, $filters, $metadata);

            case 'exceptions':
                return $this->generateExceptionsPdf($reportService, $pdfService, $filters, $metadata);

            case 'per-entity':
                return $this->generatePerEntityPdf($reportService, $pdfService, $filters, $metadata, $progressCallback);

            default:
                throw new \InvalidArgumentException("Unsupported report type: {$reportType}");
        }
    }

    /**
     * Generate detail report PDF
     */
    private function generateDetailPdf(
        ReportService $reportService,
        PdfService $pdfService,
        array $filters,
        array $metadata,
        callable $progressCallback
    ): string {
        $query = $reportService->buildOrderQuery($filters);
        $totalRows = $query->count();

        // Use chunked generation for large reports
        if ($totalRows > 1000) {
            return $pdfService->generateLargePdf(
                $query,
                'detail',
                $filters,
                $metadata,
                $progressCallback
            );
        }

        // Small report - generate all at once
        $data = $reportService->detailReport($filters, 1, $totalRows);
        return $pdfService->generateReport($data, 'detail', $filters, $metadata);
    }

    /**
     * Generate summary report PDF
     */
    private function generateSummaryPdf(
        ReportService $reportService,
        PdfService $pdfService,
        array $filters,
        array $metadata
    ): string {
        $groupBy = $filters['group_by'] ?? 'date';
        $data = $reportService->summaryReport($filters, $groupBy);

        return $pdfService->generateReport($data, 'summary', array_merge($filters, ['group_by' => $groupBy]), $metadata);
    }

    /**
     * Generate top-N report PDF
     */
    private function generateTopNPdf(
        ReportService $reportService,
        PdfService $pdfService,
        array $filters,
        array $metadata
    ): string {
        $topType = $filters['top_type'] ?? 'customers';
        $limit = $filters['limit'] ?? 100;
        $data = $reportService->topNReport($filters, $topType, $limit);

        return $pdfService->generateReport(
            $data,
            'top-n',
            array_merge($filters, ['top_type' => $topType, 'limit' => $limit]),
            $metadata
        );
    }

    /**
     * Generate exceptions report PDF
     */
    private function generateExceptionsPdf(
        ReportService $reportService,
        PdfService $pdfService,
        array $filters,
        array $metadata
    ): string {
        $exceptionType = $filters['exception_type'] ?? 'failed_orders';
        $data = $reportService->exceptionsReport($filters, $exceptionType);

        return $pdfService->generateReport(
            $data,
            'exceptions',
            array_merge($filters, ['exception_type' => $exceptionType]),
            $metadata
        );
    }

    /**
     * Generate per-entity booklet PDF with TOC
     */
    private function generatePerEntityPdf(
        ReportService $reportService,
        PdfService $pdfService,
        array $filters,
        array $metadata,
        callable $progressCallback
    ): string {
        // Get entities (customers or regions)
        $entityType = $filters['entity_type'] ?? 'customers';

        if ($entityType === 'customers') {
            $entities = $this->buildCustomerEntities($reportService, $filters);
        } else {
            $entities = $this->buildRegionEntities($reportService, $filters);
        }

        return $pdfService->generatePerEntityBooklet(
            $entities,
            'per-entity',
            $filters,
            $metadata,
            $progressCallback
        );
    }

    /**
     * Build customer entities for per-entity report
     */
    private function buildCustomerEntities(ReportService $reportService, array $filters): array
    {
        $customers = \App\Models\Customer::limit(100)->get(); // Limit for demo
        $entities = [];

        foreach ($customers as $customer) {
            $customerFilters = array_merge($filters, ['customer_id' => $customer->id]);
            $orders = $reportService->buildOrderQuery($customerFilters)->get();

            if ($orders->count() > 0) {
                $entities[] = [
                    'name' => $customer->name,
                    'subtitle' => $customer->email,
                    'details' => [
                        'Account Type' => ucfirst($customer->account_type),
                        'Total Orders' => $orders->count(),
                        'Total Revenue' => '$' . number_format($orders->sum('total_amount'), 2),
                    ],
                    'orders' => $orders,
                ];
            }
        }

        return $entities;
    }

    /**
     * Build region entities for per-entity report
     */
    private function buildRegionEntities(ReportService $reportService, array $filters): array
    {
        $regions = \App\Models\Region::all();
        $entities = [];

        foreach ($regions as $region) {
            $regionFilters = array_merge($filters, ['region_id' => $region->id]);
            $orders = $reportService->buildOrderQuery($regionFilters)->get();

            if ($orders->count() > 0) {
                $entities[] = [
                    'name' => $region->name,
                    'subtitle' => $region->country,
                    'details' => [
                        'Total Orders' => $orders->count(),
                        'Total Revenue' => '$' . number_format($orders->sum('total_amount'), 2),
                    ],
                    'orders' => $orders,
                ];
            }
        }

        return $entities;
    }

    /**
     * Handle job failure
     */
    private function handleFailure(ReportJob $reportJob, \Exception $exception): void
    {
        $reportJob->update([
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'finished_at' => now(),
        ]);

        Log::error("Report job failed: {$this->reportJobId}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Generate Excel report
     */
    private function generateExcel(
        ReportJob $reportJob,
        ReportService $reportService,
        \App\Services\ExcelService $excelService,
        array $filters,
        string $reportType
    ): string {
        // Progress callback
        $progressCallback = function ($processed) use ($reportJob) {
            $reportJob->update([
                'processed_rows' => $processed,
                'current_section' => "Processing row $processed",
            ]);
        };

        // Handle different report types
        switch ($reportType) {
            case 'detail':
                return $this->generateDetailExcel($reportService, $excelService, $filters, $progressCallback);

            case 'summary':
                return $this->generateSummaryExcel($reportService, $excelService, $filters);

            case 'top-n':
                return $this->generateTopNExcel($reportService, $excelService, $filters);

            case 'exceptions':
                return $this->generateExceptionsExcel($reportService, $excelService, $filters);

            default:
                throw new \InvalidArgumentException("Unsupported report type: {$reportType}");
        }
    }

    /**
     * Generate detail report Excel
     */
    private function generateDetailExcel(
        ReportService $reportService,
        \App\Services\ExcelService $excelService,
        array $filters,
        callable $progressCallback
    ): string {
        $query = $reportService->buildOrderQuery($filters);

        return $excelService->generateLargeExcel(
            \App\Exports\DetailReportExport::class,
            $query,
            'detail',
            $filters,
            $progressCallback
        );
    }

    /**
     * Generate summary report Excel
     */
    private function generateSummaryExcel(
        ReportService $reportService,
        \App\Services\ExcelService $excelService,
        array $filters
    ): string {
        $groupBy = $filters['group_by'] ?? 'date';
        $data = $reportService->summaryReport($filters, $groupBy);

        return $excelService->generateReport(
            \App\Exports\SummaryReportExport::class,
            ['data' => $data, 'groupBy' => $groupBy],
            'summary'
        );
    }

    /**
     * Generate top-N report Excel
     */
    private function generateTopNExcel(
        ReportService $reportService,
        \App\Services\ExcelService $excelService,
        array $filters
    ): string {
        $topType = $filters['top_type'] ?? 'customers';
        $limit = $filters['limit'] ?? 100;
        $data = $reportService->topNReport($filters, $topType, $limit);

        return $excelService->generateReport(
            \App\Exports\TopNReportExport::class,
            ['data' => $data, 'topType' => $topType],
            'top-n'
        );
    }

    /**
     * Generate exceptions report Excel
     */
    private function generateExceptionsExcel(
        ReportService $reportService,
        \App\Services\ExcelService $excelService,
        array $filters
    ): string {
        $exceptionType = $filters['exception_type'] ?? 'failed_orders';
        $data = $reportService->exceptionsReport($filters, $exceptionType);

        return $excelService->generateReport(
            \App\Exports\ExceptionsReportExport::class,
            ['data' => $data, 'exceptionType' => $exceptionType],
            'exceptions'
        );
    }

    /**
     * Handle job failure (Laravel hook)
     */
    public function failed(\Throwable $exception): void
    {
        $reportJob = ReportJob::find($this->reportJobId);

        if ($reportJob) {
            $this->handleFailure($reportJob, $exception);
        }
    }

}
