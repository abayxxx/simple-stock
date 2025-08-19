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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalesReceiptsExport implements
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
        protected ?int $collectorId = null
    ) {}

    public function startCell(): string { return 'A6'; }

    public function query()
    {
        // tables/columns follow your controller: employe_profiles + employee_id
        return DB::table('sales_receipts as sr')
            ->leftJoin('company_profiles as cp', 'cp.id', '=', 'sr.company_profile_id')
            ->leftJoin('employe_profiles as ep', 'ep.id', '=', 'sr.employee_id')
            ->leftJoin('sales_receipt_items as sri', 'sri.sales_receipt_id', '=', 'sr.id')
            ->when($this->awal && $this->akhir,
                fn($q) => $q->whereBetween('sr.tanggal', [$this->awal.' 00:00:00', $this->akhir.' 23:59:59']))
            ->when($this->customerId, fn($q) => $q->where('sr.company_profile_id', $this->customerId))
            ->when($this->collectorId, fn($q) => $q->where('sr.employee_id', $this->collectorId))
            ->groupBy('sr.id', 'sr.tanggal', 'sr.kode', 'cp.name', 'ep.nama', 'sr.total_faktur', 'sr.total_retur')
            ->orderBy('sr.tanggal')
            ->selectRaw("
                sr.tanggal,
                sr.kode,
                COALESCE(cp.name,'-')   as customer_name,
                COALESCE(ep.nama,'-')   as collector_name,
                COUNT(sri.id)           as jml_faktur,
                sr.total_faktur as grand_total
            ");
    }

    public function headings(): array
    {
        return ['Tanggal','No. TT','Nama Customer','Kolektor','Jml Faktur','Grand Total'];
    }

    public function map($r): array
    {
        return [
            $r->tanggal ? date('d M Y', strtotime($r->tanggal)) : '-',
            $r->kode,
            $r->customer_name,
            $r->collector_name,
            (int) $r->jml_faktur,
            (float) $r->grand_total,
        ];
    }

    public function columnWidths(): array
    {
        return ['A'=>14,'B'=>22,'C'=>36,'D'=>16,'E'=>12,'F'=>18];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $periodeText = ($this->awal && $this->akhir)
                ? date('d M Y', strtotime($this->awal)) . ' s/d ' . date('d M Y', strtotime($this->akhir))
                : '-';

                // Title + period (A1..F1 and A3..F3)
                $sheet->setCellValue('A1', 'DAFTAR TANDA TERIMA PENJUALAN');
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                // we can’t get request here; show placeholder; controller will pass real period via cell later
                // (we’ll overwrite A3 in controller after download call if needed)
                $sheet->setCellValue('A3', 'Periode: ' . $periodeText);
                $sheet->mergeCells('A3:F3');

                // Head row styling
                $sheet->getStyle('A6:F6')->getFont()->setBold(true);
                $sheet->getStyle('A6:F6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Data range styling
                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 7) {
                    $sheet->getStyle("A7:F{$lastRow}")
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    $sheet->getStyle("A7:A{$lastRow}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
                    $sheet->getStyle("E7:E{$lastRow}")->getNumberFormat()->setFormatCode('0'); // integer
                    $sheet->getStyle("F7:F{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }

                // Total row
                $totalRow = $lastRow + 1;
                $sheet->setCellValue("E{$totalRow}", 'TOTAL PENJUALAN');
                $sheet->getStyle("E{$totalRow}")->getFont()->setBold(true);
                $sheet->setCellValue("F{$totalRow}", "=SUM(F7:F{$lastRow})");
                $sheet->getStyle("F{$totalRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle("E{$totalRow}:F{$totalRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
