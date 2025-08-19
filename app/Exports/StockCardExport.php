<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StockCardExport implements
    FromArray,
    WithHeadings,
    WithEvents,
    WithCustomStartCell,
    WithColumnWidths
{
    /** @param array<int, array<string,mixed>> $rows */
    public function __construct(
        protected array $rows,            // data rows already computed (running balance done)
        protected string $produkName,     // e.g. "PRO-0006 - Teh Juss - Tipe a b02…"
        protected ?string $awal = null,   // 'YYYY-MM-DD'
        protected ?string $akhir = null,  // 'YYYY-MM-DD'
        protected string $satuan = '',    // e.g. "BOX"
        protected float $totalMasuk = 0,
        protected float $totalKeluar = 0,
        protected float $saldoAkhir = 0
    ) {}

    public function startCell(): string { return 'A7'; }

    /** Table headings */
    public function headings(): array
    {
        return ['Tanggal','Tipe Stok','No Transaksi','Nama','Masuk','Keluar','Sisa'];
    }

    /** Table data (array of arrays) */
    public function array(): array
    {
        // Ensure plain values (no HTML) for Excel
        return array_map(function ($r) {
            return [
                $r['tanggal'] ?? '',
                strip_tags($r['tipe_stock'] ?? ''), // remove span tags
                $r['catatan'] ?? '',
                $r['nama'] ?? '',
                $r['masuk'] ?? '',
                $r['keluar'] ?? '',
                $r['sisa'] ?? '',
            ];
        }, $this->rows);
    }

    public function columnWidths(): array
    {
        return ['A'=>20,'B'=>16,'C'=>40,'D'=>28,'E'=>12,'F'=>12,'G'=>12];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title
                $sheet->setCellValue('A1', 'KARTU STOK');
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                // Meta line: Produk
                $sheet->setCellValue('A3', 'Produk : '.$this->produkName);
                $sheet->mergeCells('A3:G3');

                // Meta line: Periode
                $periode = ($this->awal && $this->akhir)
                    ? date('d M Y', strtotime($this->awal)).' s/d '.date('d M Y', strtotime($this->akhir))
                    : '-';
                $sheet->setCellValue('A4', 'Periode : '.$periode);
                $sheet->mergeCells('A4:G4');

                // Summary line
                $summary = sprintf(
                    'TOTAL MASUK: %s %s   TOTAL KELUAR: %s %s   SISA: %s %s',
                    number_format($this->totalMasuk, 0, ',', '.'),
                    $this->satuan,
                    number_format($this->totalKeluar, 0, ',', '.'),
                    $this->satuan,
                    number_format($this->saldoAkhir, 0, ',', '.'),
                    $this->satuan
                );
                $sheet->setCellValue('A5', $summary);
                $sheet->mergeCells('A5:G5');

                // Head row styling
                $sheet->getStyle('A7:G7')->getFont()->setBold(true);
                $sheet->getStyle('A7:G7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Borders for data range
                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 8) {
                    $sheet->getStyle("A8:G{$lastRow}")
                          ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
            },
        ];
    }
}
