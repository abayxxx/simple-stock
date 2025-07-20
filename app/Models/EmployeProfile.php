<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeProfile extends Model
{
    protected $table = 'employe_profiles';

    protected $fillable = [
        'nama',
        'no_telepon',
        'email',
        'alamat',
        'catatan',
    ];

    // Generate kode otomatis (misal: EMP-YYYYMMDD-0001)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->code) {
                $prefix = 'EMP' . '-';
                $latest = self::where('code', 'like', $prefix . '%')->max('code');
                $number = $latest ? intval(substr($latest, -4)) + 1 : 1;
                $model->code = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function salesGroups()
    {
        return $this->belongsToMany(SalesGroup::class, 'sales_group_employe_profile');
    }
}
