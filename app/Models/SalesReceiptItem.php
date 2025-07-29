<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReceiptItem extends Model
{
    protected $fillable = [
        'sales_receipt_id',
        'sales_invoice_id',
        'sales_return_id',
        'total_faktur',
        'total_retur',
        'sisa_tagihan',
        'keterangan'
    ];

    public function salesReceipt()
    {
        return $this->belongsTo(SalesReceipt::class, 'sales_receipt_id');
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
