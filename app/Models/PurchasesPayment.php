<?php

// app/Models/PurchasesPayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasesPayment extends Model
{
    protected $fillable = [
        'kode',
        'tanggal',
        'company_profile_id', // supplier
        'catatan',
        'user_id'
    ];

    public function supplier()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchasesPaymentItem::class);
    }
}
