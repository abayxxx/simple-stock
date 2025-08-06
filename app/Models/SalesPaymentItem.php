<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPaymentItem extends Model
{
    //
    protected $fillable = [
        'sales_payment_id',
        'tipe_nota',
        'sales_invoice_id',
        'sales_return_id',
        'nilai_nota',
        'sisa',
        'tunai',
        'bank',
        'giro',
        'cndn',
        'retur',
        'panjar',
        'lainnya',
        'sub_total',
        'pot_ke_no',
        'catatan',
    ];

    // Relations
    public function payment()
    {
        return $this->belongsTo(SalesPayment::class, 'sales_payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function return()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }
}
