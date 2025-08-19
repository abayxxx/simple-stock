<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalesInvoicesExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithEvents,
    WithCustomStartCell,
    WithColumnWidths
{
    public function __construct(
        protected ?string $awal = null,
        protected ?string $akhir = null,
        protected ?int $customerId = null,
        protected ?int $lokasiId = null,
        protected ?int $salesGroupId = null
    ) {}

    public function startCell(): string { return 'A6'; }

    /** Use JOINs instead of loading relations (saves memory) */
    public function query()
    {
        $q = DB::table('sales_invoices as si')
            ->leftJoin('company_profiles as cp', 'cp.id', '=', 'si.company_profile_id')
            ->leftJoin('sales_groups as sg', 'sg.id', '=', 'si.sales_group_id')
            ->select([
                'si.tanggal',
                'si.kode',
                DB::raw('COALESCE(cp.name, "-") as customer_name'),
                DB::raw('COALESCE(sg.nama, "-") as sales_name'),
                'si.jatuh_tempo',
                'si.grand_total',
                'si.total_retur',
                'si.total_bayar',
                'si.sisa_tagihan',
            ])
            ->orderBy('si.tanggal');

        if ($this->awal && $this->akhir) {
            $q->whereBetween('si.tanggal', [$this->awal.' 00:00:00', $this->akhir.' 23:59:59']);
        }
        if ($this->customerId) $q->where('si.company_profile_id', $this->customerId);
        if ($this->lokasiId)   $q->where('si.lokasi_id', $this->lokasiId);
        if ($this->salesGroupId) $q->where('si.sales_group_id', $this->salesGroupId);

        return $q;
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Faktur', 'Nama Customer', 'Sales', 'Jatuh Tempo', 'Grand Total', 'Total Retur', 'Total Bayar', 'Sisa Tagihan'];
    }

    public function map($r): array
    {
        // $r is stdClass from the query() select
        return [
            $r->tanggal ? date('d M Y', strtotime($r->tanggal)) : '-',
            $r->kode,
            $r->customer_name,
            $r->sales_name,
            $r->jatuh_tempo ? date('d M Y', strtotime($r->jatuh_tempo)) : '-',
            (float) $r->grand_total,
            (float) $r->total_retur,
            (float) $r->total_bayar,
            (float) $r->sisa_tagihan,
        ];
    }

    /** Fixed widths (cheaper than autosize) */
    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 22, 'C' => 35, 'D' => 12, 'E' => 14, 'F' => 16, 'G' => 16, 'H' => 16, 'I' => 16];
    }

    public function registerEvents(): array
    {
        return [
            /** turn off pre-calculating formulas to save memory */
            // BeforeExport::class => function (BeforeExport $event) {
            //     $event->writer->getDelegate()->setPreCalculateFormulas(false);
            // },

            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title + period (same as before)
                $sheet->setCellValue('A1', 'DAFTAR FAKTUR PENJUALAN');
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                $periode = ($this->awal && $this->akhir)
                    ? date('d M Y', strtotime($this->awal)) . ' s/d ' . date('d M Y', strtotime($this->akhir))
                    : '-';
                $sheet->setCellValue('A3', 'Periode : ' . $periode);
                $sheet->mergeCells('A3:F3');

                // Headings border/bold
                $sheet->getStyle('A6:I6')->getFont()->setBold(true);
                $sheet->getStyle('A6:I6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Only style the **actual data range**, not whole columns
                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 7) {
                    // Borders around data
                    $sheet->getStyle("A7:I{$lastRow}")
                          ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    // Formats just for the data rows
                    $sheet->getStyle("A7:A{$lastRow}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
                    $sheet->getStyle("E7:E{$lastRow}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
                    $sheet->getStyle("F7:F{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("G7:G{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("H7:H{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("I7:I{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }

                // Total row (no pre-calc needed—SUM is fine even with pre-calc off)
                $totalRow = $lastRow + 1;
                $sheet->setCellValue("H{$totalRow}", 'TOTAL PENJUALAN');
                $sheet->getStyle("H{$totalRow}")->getFont()->setBold(true);
                $sheet->setCellValue("I{$totalRow}", "=SUM(I7:I{$lastRow})");
                $sheet->getStyle("I{$totalRow}")
                      ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle("H{$totalRow}:I{$totalRow}")
                      ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
