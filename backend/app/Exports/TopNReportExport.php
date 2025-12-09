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

class TopNReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    protected $data;
    protected $topType;

    public function __construct($data, string $topType = 'customers')
    {
        $this->data = $data;
        $this->topType = $topType;
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
        $baseHeadings = ['Rank'];

        if ($this->topType === 'customers') {
            $baseHeadings = array_merge($baseHeadings, ['Customer Name', 'Email', 'Account Type']);
        } elseif ($this->topType === 'products') {
            $baseHeadings = array_merge($baseHeadings, ['Product Name', 'SKU', 'Category']);
        } else {
            $baseHeadings = array_merge($baseHeadings, ['Region Name', 'Country']);
        }

        return array_merge($baseHeadings, ['Total Orders', 'Total Revenue', 'Avg Order Value']);
    }

    /**
     * Map data to columns
     */
    public function map($row): array
    {
        static $rank = 0;
        $rank++;

        $baseData = [$rank];

        if ($this->topType === 'customers') {
            $baseData = array_merge($baseData, [$row->name, $row->email, ucfirst($row->account_type)]);
        } elseif ($this->topType === 'products') {
            $baseData = array_merge($baseData, [$row->name, $row->sku, $row->category]);
        } else {
            $baseData = array_merge($baseData, [$row->name, $row->country]);
        }

        return array_merge($baseData, [
            $row->total_orders,
            $row->total_revenue,
            $row->average_order_value,
        ]);
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

                // Format currency columns (last 2 columns)
                $highestRow = $event->sheet->getHighestRow();
                $revenueCol = chr(ord($highestColumn) - 1);
                $avgCol = $highestColumn;

                $event->sheet->getStyle($revenueCol . '2:' . $avgCol . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Format orders column (second to last)
                $ordersCol = chr(ord($highestColumn) - 2);
                $event->sheet->getStyle($ordersCol . '2:' . $ordersCol . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Highlight top 3
                for ($i = 2; $i <= min(4, $highestRow); $i++) {
                    $event->sheet->getStyle('A' . $i . ':' . $highestColumn . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFF4E6'],
                        ],
                    ]);
                }
            },
        ];
    }
}
