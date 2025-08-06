<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;


class SalesDetailExport implements FromView, WithStyles, ShouldAutoSize
{
    public $grouped;
    public function __construct($grouped) { $this->grouped = $grouped; }
    public function view(): View
    {
        return view('exports.sales_detail', [
            'grouped' => $this->grouped
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // Default font
        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getStyle('A1:L1')->getFont()->setBold(true)->setSize(12);

        // Set header style (row 1)
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DDDDDD']]
        ]);

        // Example: Style subtotal rows
        foreach ($sheet->toArray() as $rowIdx => $row) {
            $cellA = 'A' . ($rowIdx + 1);
            if (isset($row[0]) && strpos($row[0], 'SUBTOTAL') !== false) {
                $sheet->getStyle("A".($rowIdx+1).":L".($rowIdx+1))->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EFEFEF']]
                ]);
            }
            // Group header green
            if (isset($row[0]) && strpos($row[0], 'NO. :') !== false) {
                $sheet->getStyle("A".($rowIdx+1).":L".($rowIdx+1))->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'B7F5BF']]
                ]);
            }
            // Grand total footer
            if (isset($row[0]) && strpos($row[0], 'GRAND TOTAL') !== false) {
                $sheet->getStyle("A".($rowIdx+1).":L".($rowIdx+1))->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F8F8F8']]
                ]);
            }
        }

        // Optional: Set border for all cells
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A1:L$lastRow")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        return [];
    }
}
