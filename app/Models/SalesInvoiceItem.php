<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'lokasi_id',
        'no_seri',
        'tanggal_expired',
        'qty',
        'satuan',
        'harga_satuan',

        'diskon_1_persen',
        'diskon_1_rupiah',
        'diskon_2_persen',
        'diskon_2_rupiah',
        'diskon_3_persen',
        'diskon_3_rupiah',

        'sub_total_sblm_disc',
        'total_diskon_item',
        'sub_total_sebelum_ppn',
        'ppn_persen',
        'sub_total_setelah_disc',
        'catatan',
    ];

    // Relasi ke produk dan lokasi (optional)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(CompanyBranch::class, 'lokasi_id');
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
