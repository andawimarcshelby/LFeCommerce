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

class ExceptionsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    protected $data;
    protected $exceptionType;

    public function __construct($data, string $exceptionType = 'failed_orders')
    {
        $this->data = $data;
        $this->exceptionType = $exceptionType;
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
        if ($this->exceptionType === 'refunds') {
            return [
                'Order #',
                'Date',
                'Customer',
                'Refund Date',
                'Reason',
                'Refund Amount',
            ];
        }

        return [
            'Order #',
            'Date',
            'Customer',
            'Status',
            'Payment Method',
            'Amount',
        ];
    }

    /**
     * Map data to columns
     */
    public function map($row): array
    {
        if ($this->exceptionType === 'refunds') {
            return [
                $row->order_number,
                \Carbon\Carbon::parse($row->created_at)->format('Y-m-d'),
                $row->customer_name,
                \Carbon\Carbon::parse($row->refund_date)->format('Y-m-d'),
                $row->reason ?? 'N/A',
                $row->amount,
            ];
        }

        return [
            $row->order_number ?? $row->id,
            \Carbon\Carbon::parse($row->order_date)->format('Y-m-d'),
            $row->customer->name ?? 'N/A',
            ucfirst($row->status),
            ucfirst($row->payment_method ?? 'N/A'),
            $row->total_amount,
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
                $highestColumn = $event->sheet->getHighestColumn();
                $event->sheet->setAutoFilter('A1:' . $highestColumn . '1');

                // Format currency column (last column)
                $highestRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle($highestColumn . '2:' . $highestColumn . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Highlight error rows with red background
                for ($i = 2; $i <= $highestRow; $i++) {
                    $event->sheet->getStyle('A' . $i . ':' . $highestColumn . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFEBEE'],
                        ],
                    ]);
                }
            },
        ];
    }
}
