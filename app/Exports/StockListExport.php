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

class StockListExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithEvents,
    WithCustomStartCell,
    WithColumnWidths
{
    public function __construct(
        protected ?string $awal  = null,   // 'YYYY-MM-DD'
        protected ?string $akhir = null    // 'YYYY-MM-DD'
    ) {}

    public function startCell(): string { return 'A6'; }

    /**
     * We compute AKHIR (ending stock) as:
     * SUM(in) - SUM(out) - SUM(destroy) up to <= periode_akhir 23:59:59
     * (Products with no stock rows are shown with 0)
     */
    public function query()
    {
        $cutoff = $this->akhir ? ($this->akhir . ' 23:59:59') : null;

        // Aggregate stock once for all products (memory-friendly)
        $akhirExpr = "COALESCE(SUM(CASE
                        WHEN s.type = 'in'      THEN s.jumlah
                        WHEN s.type IN ('out','destroy') THEN -s.jumlah
                        ELSE 0 END), 0)";

        $q = DB::table('products as p')
            ->leftJoin('stocks as s', 's.product_id', '=', 'p.id')
            ->when($cutoff, fn($qq) => $qq->where('s.created_at', '<=', $cutoff))
            ->groupBy('p.id', 'p.nama', 'p.harga_umum', 'p.satuan_kecil')
            ->orderBy('p.nama')
            ->selectRaw("
                p.nama,
                p.harga_umum,
                UPPER(COALESCE(p.satuan_kecil,'')) as unit_kecil,
                {$akhirExpr} as akhir
            ");

        return $q;
    }

    public function headings(): array
    {
        return ['NAMA', 'HARGA JUAL', 'AKHIR'];
    }

    public function map($r): array
    {
        // Combine qty + unit in one cell like your table: "123 PCS"
        $akhirWithUnit = number_format((float)$r->akhir, 0, ',', '.') . ' ' . ($r->unit_kecil ?: '');
        return [
            $r->nama,
            (float) $r->harga_umum,
            $akhirWithUnit,
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 40, 'B' => 18, 'C' => 14];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title + period
                $sheet->setCellValue('A1', 'DAFTAR STOK');
                $sheet->mergeCells('A1:C1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                $periodeText = ($this->awal && $this->akhir)
                    ? date('d M Y', strtotime($this->awal)) . ' s/d ' . date('d M Y', strtotime($this->akhir))
                    : '-';
                $sheet->setCellValue('A3', 'Periode : ' . $periodeText);
                $sheet->mergeCells('A3:C3');

                // Header row styling
                $sheet->getStyle('A6:C6')->getFont()->setBold(true);
                $sheet->getStyle('A6:C6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Data formatting & borders (only actual rows)
                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 7) {
                    $sheet->getStyle("A7:C{$lastRow}")
                          ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    // Price column format
                    $sheet->getStyle("B7:B{$lastRow}")
                          ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }
            },
        ];
    }
}
