<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'kode',
        'no_seri',
        'tanggal_expired',
        'jumlah',
        'harga_net',
        'subtotal',
        'catatan',
        'sisa_stok',
        'type',
        'created_at',
    ];

    // Relasi produk
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Kode transaksi (NO.) otomatis, format 2507.00001 (ddmy.id)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->kode) {
                $prefix = date('ym') . '.'; // yy mm saja, bukan dd mm
                $last = self::where('kode', 'like', $prefix . '%')->max('kode');
                $next = $last ? intval(substr($last, 6)) + 1 : 1; // substr ke-6 karena '2507.' = 5 char + 1
                $model->kode = $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
