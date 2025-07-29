<?php

// app/Models/PurchasesPaymentItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasesPaymentItem extends Model
{
    protected $fillable = [
        'purchases_payment_id',
        'tipe_nota',
        'purchases_invoice_id',
        'purchases_return_id',
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

    public function payment()
    {
        return $this->belongsTo(PurchasesPayment::class, 'purchases_payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(PurchasesInvoice::class, 'purchases_invoice_id');
    }

    public function return()
    {
        return $this->belongsTo(PurchasesReturn::class, 'purchases_return_id');
    }
}
