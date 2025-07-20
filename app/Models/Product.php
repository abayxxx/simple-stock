<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'nama',
        'satuan_kecil',
        'isi_satuan_kecil',
        'satuan_sedang',
        'isi_satuan_sedang',
        'satuan_besar',
        'isi_satuan_besar',
        'satuan_massa',
        'isi_satuan_massa',
        'catatan',
        'hpp_bruto_kecil',
        'hpp_bruto_besar',
        'diskon_hpp_1',
        'diskon_hpp_2',
        'diskon_hpp_3',
        'diskon_hpp_4',
        'diskon_hpp_5',
        'harga_umum',
        'diskon_harga_1',
        'diskon_harga_2',
        'diskon_harga_3',
        'diskon_harga_4',
        'diskon_harga_5',
    ];

    // Otomatis generate kode produk (contoh: PRO-YYYYMMDD-0001)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->kode) {
                $prefix = 'PRO' . '-';
                $latest = self::where('kode', 'like', $prefix . '%')->max('kode');
                $number = $latest ? intval(substr($latest, -4)) + 1 : 1;
                $model->kode = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relasi stok
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
