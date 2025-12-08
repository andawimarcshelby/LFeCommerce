<?php

namespace App\Services;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;

class MultiSheetExcelGenerator implements WithMultipleSheets
{
    private string $reportType;
    private $query;
    private array $metadata;

    public function __construct(string $reportType, $query, array $metadata)
    {
        $this->reportType = $reportType;
        $this->query = $query;
        $this->metadata = $metadata;
    }

    /**
     * Generate Excel with multiple sheets
     */
    public function generate(callable $progressCallback = null): string
    {
        $filename = 'report_' . time() . '_' . uniqid() . '.xlsx';
        $outputPath = storage_path('app/exports/' . $filename);

        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        Excel::store($this, 'exports/' . $filename, 'local');

        if ($progressCallback) {
            $progressCallback(100, 100, 'Excel generated');
        }

        return $outputPath;
    }

    /**
     * Return array of sheets
     */
    public function sheets(): array
    {
        $sheets = [];

        // Summary sheet
        $sheets[] = new ExcelReportSheet(
            $this->query->limit(0), // Empty query for summary
            ['Metric', 'Value'],
            'Summary',
            false,
            false
        );

        // Detail sheet with data
        $headings = $this->metadata['columns'] ?? $this->getHeadingsFromQuery();
        $sheets[] = new ExcelReportSheet(
            $this->query,
            $headings,
            'Details',
            true,
            true
        );

        return $sheets;
    }

    private function getHeadingsFromQuery(): array
    {
        $first = $this->query->first();
        if (!$first) {
            return ['No Data'];
        }
        return array_keys(is_array($first) ? $first : $first->toArray());
    }
}
