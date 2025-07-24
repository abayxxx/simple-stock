<?php

// app/Models/SalesReturnItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_return_id',
        'product_id',
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
        'catatan'
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
