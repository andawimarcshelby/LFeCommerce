<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DetailReportExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    protected $query;
    protected $filters;
    protected $progressCallback;
    protected $processedRows = 0;

    public function __construct($query, array $filters = [], callable $progressCallback = null)
    {
        $this->query = $query;
        $this->filters = $filters;
        $this->progressCallback = $progressCallback;
    }

    /**
     * Query for data
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'Order #',
            'Date',
            'Customer',
            'Region',
            'Status',
            'Subtotal',
            'Tax',
            'Shipping',
            'Total Amount',
            'Payment Method',
        ];
    }

    /**
     * Map data to columns
     */
    public function map($order): array
    {
        $this->processedRows++;

        // Call progress callback every 100 rows
        if ($this->progressCallback && $this->processedRows % 100 === 0) {
            call_user_func($this->progressCallback, $this->processedRows);
        }

        return [
            $order->order_number ?? $order->id,
            $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : '',
            $order->customer->name ?? 'N/A',
            $order->region->name ?? 'N/A',
            ucfirst($order->status),
            $order->total_amount - $order->tax,
            $order->tax,
            $order->shipping_cost,
            $order->total_amount,
            ucfirst($order->payment_method ?? 'N/A'),
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
                $event->sheet->setAutoFilter('A1:J1');

                // Format currency columns
                $highestRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle('F2:I' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');
            },
        ];
    }
}
