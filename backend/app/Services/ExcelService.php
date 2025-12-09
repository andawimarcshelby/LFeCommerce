<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ExcelService
{
    /**
     * Generate Excel report
     */
    public function generateReport(string $exportClass, array $data, string $reportType): string
    {
        $filename = $this->generateFilename($reportType);
        $path = 'exports/' . $filename;

        $export = new $exportClass($data['data'], $data['groupBy'] ?? null, $data['topType'] ?? null, $data['exceptionType'] ?? null);
        Excel::store($export, $path, 'local');

        return $path;
    }

    /**
     * Generate large Excel with streaming
     */
    public function generateLargeExcel(
        string $exportClass,
        $query,
        string $reportType,
        array $filters = [],
        callable $progressCallback = null
    ): string {
        $filename = $this->generateFilename($reportType);
        $path = 'exports/' . $filename;

        $export = new $exportClass($query, $filters, $progressCallback);
        Excel::store($export, $path, 'local');

        return $path;
    }

    /**
     * Generate multi-sheet Excel workbook
     */
    public function generateMultiSheetExcel(array $sheets, string $filename): string
    {
        $path = 'exports/' . $filename;

        Excel::store(new \App\Exports\MultiSheetReportExport($sheets), $path, 'local');

        return $path;
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(string $reportType): string
    {
        return sprintf(
            '%s_report_%s.xlsx',
            $reportType,
            now()->format('Y-m-d_His')
        );
    }

    /**
     * Get file size
     */
    public function getFileSize(string $path): int
    {
        $fullPath = storage_path('app/' . $path);
        return file_exists($fullPath) ? filesize($fullPath) : 0;
    }
}
