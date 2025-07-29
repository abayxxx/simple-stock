<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasesReturn extends Model
{
    //
    protected $fillable = [
        'kode',
        'tanggal',
        'company_profile_id',
        'purchases_invoice_id',
        'tipe_retur',
        'catatan',
        'diskon_faktur',
        'diskon_ppn',
        'subtotal',
        'grand_total',
        'total_retur',
        'total_bayar',
        'sisa_tagihan',
        'user_id'
    ];

    // Relations
    public function supplier()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function purchasesInvoice()
    {
        return $this->belongsTo(PurchasesInvoice::class, 'purchases_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(PurchasesReturnItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
