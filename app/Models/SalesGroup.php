<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesGroup extends Model
{
    //
    protected $fillable = [
        'kode',
        'nama',
        'catatan',
    ];

    // Otomatis generate kode grup penjualan (contoh: GRP-YYYYMMDD-0001)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->kode) {
                $prefix = 'SG' . '-';
                $latest = self::where('kode', 'like', $prefix . '%')->max('kode');
                $number = $latest ? intval(substr($latest, -4)) + 1 : 1;
                $model->kode = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function pegawai()
    {
        return $this->belongsToMany(EmployeProfile::class, 'sales_group_employe_profile');
    }
}
