<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBranch extends Model
{
    protected $table = 'company_branches';

    protected $fillable = [
        'name',
        'address'
    ];

    // Kode cabang auto generate (CBR-YYYYMMDD-0001)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->code) {
                $prefix = 'CB' . '-';
                $latest = self::where('code', 'like', $prefix . '%')->max('code');
                $number = $latest ? intval(substr($latest, -4)) + 1 : 1;
                $model->code = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
