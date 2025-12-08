<?php

namespace App\Services;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class ExcelReportSheet implements FromQuery, WithHeadings, WithChunkReading, ShouldAutoSize, WithStyles, WithTitle
{
    protected Builder $query;
    protected array $headings;
    protected string $title;
    protected bool $freezePane;
    protected bool $autoFilter;

    public function __construct(Builder $query, array $headings, string $title, bool $freezePane = true, bool $autoFilter = true)
    {
        $this->query = $query;
        $this->headings = $headings;
        $this->title = $title;
        $this->freezePane = $freezePane;
        $this->autoFilter = $autoFilter;
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    public function title(): string
    {
        return substr($this->title, 0, 31); // Excel sheet name limit
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('1:1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B82F6'],
            ],
        ]);

        // Freeze pane at row 2 (after headers)
        if ($this->freezePane) {
            $sheet->freezePane('A2');
        }

        // Add auto-filter
        if ($this->autoFilter) {
            $lastColumn = $sheet->getHighestColumn(1);
            $sheet->setAutoFilter("A1:{$lastColumn}1");
        }

        return $sheet;
    }
}
