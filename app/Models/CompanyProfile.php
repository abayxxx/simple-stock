<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    //
    protected $fillable = [
        'code',
        'name',
        'address',
        'spesific_location',
        'phone',
        'email',
        'website',
        'relationship',
        'npwp',
        'tax_invoice_to',
        'tax_invoice_address',
    ];

    public function externalData()
    {
        return $this->hasOne(CompanyProfileExternalData::class, 'company_profile_id');
    }


    /**
     * Automatically generate a unique code for the company profile.
     * Format: CP-YYYYMMDD-XXXX (where XXXX is a sequential number)
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->code) {
                $prefix = 'CP' . '-';
                $latest = self::where('code', 'like', $prefix . '%')->max('code');
                $number = $latest ? intval(substr($latest, -4)) + 1 : 1;
                $model->code = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
