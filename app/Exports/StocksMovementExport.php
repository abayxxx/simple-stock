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

class StocksMovementExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithEvents,
    WithCustomStartCell,
    WithColumnWidths
{
    public function __construct(
        protected string  $type,                // 'in' | 'out' | 'destroy'
        protected ?string $awal  = null,        // 'YYYY-MM-DD'
        protected ?string $akhir = null         // 'YYYY-MM-DD'
    ) {}

    public function startCell(): string { return 'A6'; }

    /** Use the same filters as your datatable, and exclude invoice-driven rows */
    public function query()
    {
        $q = DB::table('stocks as s')
            ->leftJoin('products as p', 'p.id', '=', 's.product_id')
            ->where('s.type', $this->type)
            ->where('s.catatan', 'NOT LIKE', '%RT.%')
            ->where('s.catatan', 'NOT LIKE', '%HR.%')
            ->where('s.catatan', 'NOT LIKE', '%PI.%')
            ->where('s.catatan', 'NOT LIKE', '%SI.%')
            ->orderByDesc('s.id')
            ->select([
                's.id',
                's.created_at',
                'p.nama as product_name',
                's.no_seri',
                's.tanggal_expired',
                's.jumlah',
                's.harga_net',
                's.subtotal',
                's.catatan',
            ]);

        if ($this->awal && $this->akhir) {
            $q->whereBetween('s.created_at', [$this->awal . ' 00:00:00', $this->akhir . ' 23:59:59']);
        }

        return $q;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Tanggal',
            'Produk',
            'No. Seri',
            'Expired',
            'Qty',
            'Harga Net',
            'Subtotal',
            'Catatan',
        ];
    }

    public function map($r): array
    {
        return [
            $r->id,
            $r->created_at ? date('d M Y', strtotime($r->created_at)) : '-',
            $r->product_name ?? '-',
            $r->no_seri ?? '',
            $r->tanggal_expired ? date('d M Y', strtotime($r->tanggal_expired)) : '',
            (float) $r->jumlah,
            (float) $r->harga_net,
            (float) $r->subtotal,
            $r->catatan ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return ['A'=>10,'B'=>14,'C'=>34,'D'=>16,'E'=>14,'F'=>12,'G'=>16,'H'=>16,'I'=>40];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title + Period
                $title = strtoupper(match ($this->type) {
                    'in'      => 'STOK MASUK',
                    'out'     => 'STOK KELUAR',
                    'destroy' => 'STOK PEMUSNAHAN',
                    default   => 'STOK',
                });

                $sheet->setCellValue('A1', $title);
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                $periode = ($this->awal && $this->akhir)
                    ? date('d M Y', strtotime($this->awal)) . ' s/d ' . date('d M Y', strtotime($this->akhir))
                    : '-';
                $sheet->setCellValue('A3', 'Periode : ' . $periode);
                $sheet->mergeCells('A3:I3');

                // Header row style
                $sheet->getStyle('A6:I6')->getFont()->setBold(true);
                $sheet->getStyle('A6:I6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Data range formatting
                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 7) {
                    $sheet->getStyle("A7:I{$lastRow}")
                          ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    $sheet->getStyle("F7:F{$lastRow}")->getNumberFormat()
                          ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("G7:H{$lastRow}")->getNumberFormat()
                          ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }

                // Totals row
                $totalRow = $lastRow + 1;
                $sheet->setCellValue("E{$totalRow}", 'TOTAL');
                $sheet->getStyle("E{$totalRow}")->getFont()->setBold(true);

                $sheet->setCellValue("F{$totalRow}", "=SUM(F7:F{$lastRow})"); // total qty
                $sheet->setCellValue("H{$totalRow}", "=SUM(H7:H{$lastRow})"); // total nilai

                $sheet->getStyle("F{$totalRow}")->getNumberFormat()
                      ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet->getStyle("H{$totalRow}")->getNumberFormat()
                      ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $sheet->getStyle("E{$totalRow}:H{$totalRow}")
                      ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
