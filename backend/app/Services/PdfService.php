<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfService
{
    private $chunkSize = 1000; // Rows per chunk
    private TocService $tocService;

    public function __construct(TocService $tocService)
    {
        $this->tocService = $tocService;
    }

    /**
     * Generate PDF report
     */
    public function generateReport(array $data, string $reportType, array $filters, array $metadata = []): string
    {
        $html = $this->renderHtml($data, $reportType, $filters, $metadata);
        $filename = $this->generateFilename($reportType);
        $path = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        // Generate PDF with Browsershot
        Browsershot::html($html)
            ->setOption('landscape', false)
            ->margins(10, 10, 10, 10)
            ->format('A4')
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->save($path);

        // Add metadata
        if (!empty($metadata)) {
            $this->addMetadata($path, $metadata);
        }

        return 'exports/' . $filename;
    }

    /**
     * Generate large PDF in chunks with progress tracking
     */
    public function generateLargePdf(
        $dataQuery,
        string $reportType,
        array $filters,
        array $metadata = [],
        callable $progressCallback = null
    ): string {
        $filename = $this->generateFilename($reportType);
        $tempDir = storage_path('app/temp/' . uniqid());
        mkdir($tempDir, 0755, true);

        $totalRows = $dataQuery->count();
        $chunks = (int) ceil($totalRows / $this->chunkSize);
        $pdfParts = [];

        // Generate PDF chunks
        for ($i = 0; $i < $chunks; $i++) {
            $offset = $i * $this->chunkSize;
            $chunkData = $dataQuery->offset($offset)->limit($this->chunkSize)->get();

            $html = $this->renderChunkHtml($chunkData, $reportType, $filters, $metadata, [
                'isFirstChunk' => $i === 0,
                'isLastChunk' => $i === $chunks - 1,
                'chunkNumber' => $i + 1,
                'totalChunks' => $chunks,
            ]);

            $chunkPath = $tempDir . '/chunk_' . $i . '.pdf';

            Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->save($chunkPath);

            $pdfParts[] = $chunkPath;

            if ($progressCallback) {
                $processed = min(($i + 1) * $this->chunkSize, $totalRows);
                $progressCallback($processed, $totalRows, "Processing chunk " . ($i + 1) . " of $chunks");
            }
        }

        // Merge PDFs
        $finalPath = storage_path('app/exports/' . $filename);
        $this->mergePdfs($pdfParts, $finalPath);

        // Add metadata
        if (!empty($metadata)) {
            $this->addMetadata($finalPath, $metadata);
        }

        // Cleanup temp files
        array_map('unlink', $pdfParts);
        rmdir($tempDir);

        return 'exports/' . $filename;
    }

    /**
     * Generate per-entity booklet with TOC
     */
    public function generatePerEntityBooklet(
        array $entities,
        string $reportType,
        array $filters,
        array $metadata = [],
        callable $progressCallback = null
    ): string {
        $filename = $this->generateFilename($reportType);
        $tempDir = storage_path('app/temp/' . uniqid());
        mkdir($tempDir, 0755, true);

        $this->tocService->reset();
        $pdfParts = [];
        $currentPage = 1;

        // Reserve pages for TOC (will calculate later)
        $tocPageOffset = 2; // Estimate

        // Generate entity sections
        $totalEntities = count($entities);
        foreach ($entities as $index => $entity) {
            $this->tocService->setCurrentPage($currentPage + $tocPageOffset);
            $this->tocService->addSection($entity['name'], 1);

            $html = View::make('pdf.per-entity', [
                'entities' => [$entity],
                'reportType' => $reportType,
                'filters' => $filters,
                'title' => 'Per-Entity Report: ' . $entity['name'],
            ])->render();

            $chunkPath = $tempDir . '/entity_' . $index . '.pdf';

            Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->save($chunkPath);

            $pdfParts[] = $chunkPath;

            // Estimate pages for this entity (rough calculation)
            $estimatedPages = max(1, (int) ceil(count($entity['orders'] ?? []) / 20));
            $currentPage += $estimatedPages;

            if ($progressCallback) {
                $progressCallback($index + 1, $totalEntities, "Processing entity: " . $entity['name']);
            }
        }

        // Generate TOC
        $tocHtml = View::make('pdf.toc', [
            'tocHtml' => $this->tocService->generateHtml(),
        ])->render();

        $tocPath = $tempDir . '/toc.pdf';
        Browsershot::html($tocHtml)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->save($tocPath);

        // Merge: TOC first, then entities
        array_unshift($pdfParts, $tocPath);
        $finalPath = storage_path('app/exports/' . $filename);
        $this->mergePdfs($pdfParts, $finalPath);

        // Add metadata
        $metadata['has_toc'] = true;
        $metadata['total_sections'] = count($entities);
        if (!empty($metadata)) {
            $this->addMetadata($finalPath, $metadata);
        }

        // Cleanup
        array_map('unlink', $pdfParts);
        rmdir($tempDir);

        return 'exports/' . $filename;
    }

    /**
     * Render HTML for PDF
     */
    private function renderHtml(array $data, string $reportType, array $filters, array $metadata = []): string
    {
        $template = $this->getTemplateForReportType($reportType);

        return View::make($template, [
            'data' => $data,
            'reportType' => $reportType,
            'filters' => $filters,
            'title' => $metadata['title'] ?? ucfirst($reportType) . ' Report',
            'groupBy' => $filters['group_by'] ?? 'date',
            'topType' => $filters['top_type'] ?? 'customers',
            'limit' => $filters['limit'] ?? 100,
            'exceptionType' => $filters['exception_type'] ?? 'failed_orders',
        ])->render();
    }

    /**
     * Render chunk HTML
     */
    private function renderChunkHtml(
        $chunkData,
        string $reportType,
        array $filters,
        array $metadata,
        array $chunkInfo
    ): string {
        $template = $this->getTemplateForReportType($reportType);

        return View::make($template, [
            'data' => ['data' => $chunkData],
            'reportType' => $reportType,
            'filters' => $filters,
            'title' => $metadata['title'] ?? ucfirst($reportType) . ' Report',
            'chunkInfo' => $chunkInfo,
        ])->render();
    }

    /**
     * Get template name for report type
     */
    private function getTemplateForReportType(string $reportType): string
    {
        return match ($reportType) {
            'detail' => 'pdf.detail',
            'summary' => 'pdf.summary',
            'top-n' => 'pdf.top-n',
            'exceptions' => 'pdf.exceptions',
            'per-entity' => 'pdf.per-entity',
            default => 'pdf.detail',
        };
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(string $reportType): string
    {
        return sprintf(
            '%s_report_%s.pdf',
            $reportType,
            now()->format('Y-m-d_His')
        );
    }

    /**
     * Merge multiple PDFs using FPDI
     */
    private function mergePdfs(array $pdfPaths, string $outputPath): void
    {
        if (empty($pdfPaths)) {
            throw new \InvalidArgumentException('No PDF files to merge');
        }

        if (count($pdfPaths) === 1) {
            copy($pdfPaths[0], $outputPath);
            return;
        }

        $pdf = new Fpdi();

        foreach ($pdfPaths as $file) {
            $pageCount = $pdf->setSourceFile($file);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        $pdf->Output('F', $outputPath);
    }

    /**
     * Add metadata to PDF
     */
    private function addMetadata(string $path, array $metadata): void
    {
        if (!file_exists($path)) {
            return;
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($path);

        // Set metadata
        $pdf->SetCreator($metadata['creator'] ?? 'LF E-commerce Reporting');
        $pdf->SetAuthor($metadata['author'] ?? 'System');
        $pdf->SetTitle($metadata['title'] ?? 'Report');
        $pdf->SetSubject($metadata['subject'] ?? 'E-commerce Report');
        $pdf->SetKeywords($metadata['keywords'] ?? 'report, ecommerce, analytics');

        // Copy all pages
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        $pdf->Output('F', $path);
    }

    /**
     * Get PDF page count
     */
    public function getPageCount(string $path): int
    {
        if (!file_exists($path)) {
            return 0;
        }

        $pdf = new Fpdi();
        return $pdf->setSourceFile($path);
    }
}
