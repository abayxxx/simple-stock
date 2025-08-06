<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPayment extends Model
{
    //
    protected $fillable = [
        'kode',
        'tanggal',
        'company_profile_id', // customer
        'catatan',
        'user_id'
    ];

    public function customer()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SalesPaymentItem::class);
    }
}
