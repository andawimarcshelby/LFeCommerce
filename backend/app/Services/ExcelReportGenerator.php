<?php

namespace App\Services;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;

class ExcelReportGenerator
{
    /**
     * Generate Excel report from query
     */
    public function generate(Builder $query, array $metadata, callable $progressCallback = null): string
    {
        $filename = 'report_' . time() . '_' . uniqid() . '.xlsx';
        $outputPath = storage_path('app/exports/' . $filename);

        // Ensure exports directory exists
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        // Create Excel export
        Excel::store(
            new class ($query, $metadata, $progressCallback) implements FromQuery, WithHeadings, WithChunkReading, ShouldAutoSize {
            private $query;
            private $metadata;
            private $progressCallback;
            private $processedRows = 0;

            public function __construct($query, $metadata, $progressCallback)
            {
                $this->query = $query;
                $this->metadata = $metadata;
                $this->progressCallback = $progressCallback;
            }

            public function query()
            {
                return $this->query;
            }

            public function headings(): array
            {
                return $this->metadata['columns'] ?? ['ID', 'Event Type', 'Student', 'Course', 'Date'];
            }

            public function chunkSize(): int
            {
                return 1000;
            }
            },
            $outputPath,
            null,
            \Maatwebsite\Excel\Excel::XLSX
        );

        if ($progressCallback) {
            $progressCallback($query->count(), $query->count(), 'Excel generated');
        }

        return $outputPath;
    }
}
