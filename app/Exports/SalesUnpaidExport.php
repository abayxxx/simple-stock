<?php

namespace App\Exports;

use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class SalesUnpaidExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    protected $request;
    protected $data;
    protected $grouped;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->data = collect();
    }

    public function collection()
    {
        $query = SalesInvoice::with(['customer', 'salesGroup'])
            ->where('sisa_tagihan', '>', 0);

        if ($this->request->from) $query->where('tanggal', '>=', $this->request->from);
        if ($this->request->to) $query->where('tanggal', '<=', $this->request->to);
        if ($this->request->customer) {
            $query->whereHas('customer', function($q) {
                $q->where('name', 'like', '%' . $this->request->customer . '%');
            });
        }

        $invoices = $query->orderBy('kode')->get();

        // Build output array grouped by kode
        $rows = [];
        $grand = [
            'qty' => 0, 'grand_total' => 0, 'total_bayar' => 0, 'sisa_tagihan' => 0
        ];

        foreach ($invoices as $inv) {
            $row = [
                $inv->kode,
                tanggal_indo($inv->tanggal),
                $inv->customer->name ?? '',
                tanggal_indo($inv->jatuh_tempo),
                $inv->salesGroup->nama ?? '',
                $inv->grand_total,
                $inv->total_bayar,
                $inv->sisa_tagihan,
            ];
            $rows[] = $row;

            // Grand total
            $grand['grand_total'] += $inv->grand_total;
            $grand['total_bayar'] += $inv->total_bayar;
            $grand['sisa_tagihan'] += $inv->sisa_tagihan;
        }

        // Add grand total as last row
        $rows[] = [
            'GRAND TOTAL', '', '', '', '',
            $grand['grand_total'],
            $grand['total_bayar'],
            $grand['sisa_tagihan'],
        ];

        // Store for styling in AfterSheet
        $this->data = collect($rows);

        return $this->data;
    }

    public function headings(): array
    {
        return [
            'NO.',
            'TANGGAL',
            'NAMA',
            'JATUH TEMPO',
            'NAMA GROUP',
            'GRAND TOTAL',
            'PEMBAYARAN',
            'SISA TAGIHAN',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Header
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();

                // Style header
                $headerRange = 'A1:H1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DDDDDD'],
                    ],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);

                // Style data rows + number format
                for ($r = 2; $r <= $highestRow; $r++) {
                    // Format currency columns (G,H)
                    foreach(['F','G','H'] as $col) {
                        $sheet->getStyle($col.$r)->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }
                    // Borders
                    $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                    ]);
                }

                // Grand total row styling (last row)
                $sheet->getStyle("A{$highestRow}:H{$highestRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F8F8'],
                    ]
                ]);
            }
        ];
    }
}
