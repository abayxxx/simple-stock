<?php
// app/Exports/DebtExport.php

namespace App\Exports;

use App\Models\PurchasesInvoice;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebtExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithCustomStartCell,
    WithStyles,
    WithColumnFormatting,
    ShouldAutoSize,
    WithEvents
{
    public function __construct(protected string $date) {}

    /** Build raw data (keep numbers numeric) */
    public function collection()
    {
        return PurchasesInvoice::select([
            'company_profile_id',
            DB::raw('SUM(grand_total) as total_debet'),
            DB::raw('SUM(total_bayar + total_retur) as total_kredit'),
            DB::raw('SUM(sisa_tagihan) as total_sisa'),
        ])
            ->with('supplier:id,code,name,relationship')
            ->whereDate('tanggal', '<=', $this->date)
            ->groupBy('company_profile_id')
            ->get();
    }

    /** Column titles shown at A5:F5 */
    public function headings(): array
    {
        return ['KODE', 'NAMA', 'KATEGORI', 'DEBET', 'KREDIT', 'SISA'];
    }

    /** Map one row to the sheet */
    public function map($row): array
    {
        return [
            $row->supplier->code     ?? '-',
            $row->supplier->name     ?? '-',
            $row->supplier->relationship ?? '-',
            (float)$row->total_debet,
            (float)$row->total_kredit,
            (float)$row->total_sisa,
        ];
    }

    /** Start table at A5 (we’ll place title & date above) */
    public function startCell(): string
    {
        return 'A5';
    }

    /** Header/title styles */
    public function styles(Worksheet $sheet)
    {
        // Title + Date
        $sheet->setCellValue('A1', 'Laporan Hutang');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $sheet->setCellValue('A2', 'Sampai Tanggal: ' . date('d/m/Y', strtotime($this->date)));
        $sheet->mergeCells('A2:F2');

        // Header row (A5:F5)
        $sheet->getStyle('A5:F5')->getFont()->setBold(true);
        $sheet->getStyle('A5:F5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFEFEFEF');

        return [];
    }

    /** Number formatting for currency-like columns */
    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Debet
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Kredit
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Sisa
        ];
    }

    /** Add the Total row & borders after data is written */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $rowCount = $event->sheet->getHighestRow(); // includes header/title etc.

                // Data starts at row 6 (since header is at 5)
                $dataStart = 6;
                $dataEnd   = $rowCount;         // last data row
                if ($dataEnd < $dataStart) $dataEnd = $dataStart; // safe guard if empty

                $totalRow  = $dataEnd + 1;

                // Write Total label and merge A:C
                $sheet->mergeCells("A{$totalRow}:C{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'Total');
                $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalRow}:C{$totalRow}")
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                // SUM formulas for D/E/F
                $sheet->setCellValue("D{$totalRow}", "=SUM(D{$dataStart}:D{$dataEnd})");
                $sheet->setCellValue("E{$totalRow}", "=SUM(E{$dataStart}:E{$dataEnd})");
                $sheet->setCellValue("F{$totalRow}", "=SUM(F{$dataStart}:F{$dataEnd})");

                // Bold + light fill for total row
                $sheet->getStyle("A{$totalRow}:F{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalRow}:F{$totalRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF9F9F9');

                // Apply thin borders around the whole table (header + data + total)
                $sheet->getStyle("A5:F{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFBBBBBB'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
