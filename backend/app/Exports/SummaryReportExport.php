<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SummaryReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    protected $data;
    protected $groupBy;

    public function __construct($data, string $groupBy = 'date')
    {
        $this->data = $data;
        $this->groupBy = $groupBy;
    }

    /**
     * Collection of data
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            ucfirst($this->groupBy),
            'Total Orders',
            'Total Revenue',
            'Avg Order Value',
            'Total Tax',
            'Total Shipping',
        ];
    }

    /**
     * Map data to columns
     */
    public function map($row): array
    {
        return [
            $row->group_key,
            $row->total_orders,
            $row->total_revenue,
            $row->average_order_value,
            $row->total_tax,
            $row->total_shipping,
        ];
    }

    /**
     * Style the sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '750E21'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Freeze the header row
                $event->sheet->freezePane('A2');

                // Add auto-filter
                $event->sheet->setAutoFilter('A1:F1');

                // Format currency columns
                $highestRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle('C2:F' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Format number column
                $event->sheet->getStyle('B2:B' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Add totals row
                $totalRow = $highestRow + 1;
                $event->sheet->setCellValue('A' . $totalRow, 'TOTAL');
                $event->sheet->setCellValue('B' . $totalRow, '=SUM(B2:B' . $highestRow . ')');
                $event->sheet->setCellValue('C' . $totalRow, '=SUM(C2:C' . $highestRow . ')');
                $event->sheet->setCellValue('D' . $totalRow, '=AVERAGE(D2:D' . $highestRow . ')');
                $event->sheet->setCellValue('E' . $totalRow, '=SUM(E2:E' . $highestRow . ')');
                $event->sheet->setCellValue('F' . $totalRow, '=SUM(F2:F' . $highestRow . ')');

                // Style totals row
                $event->sheet->getStyle('A' . $totalRow . ':F' . $totalRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E3E3E3'],
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_DOUBLE,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
