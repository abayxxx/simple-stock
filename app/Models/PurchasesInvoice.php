<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasesInvoice extends Model
{
    protected $fillable = [
        'kode',
        'tanggal',
        'company_profile_id',
        'no_order',
        'term',
        'is_tunai',
        'is_include_ppn',
        'is_received',
        'catatan',
        'diskon_faktur',
        'diskon_ppn',
        'subtotal',
        'grand_total',
        'total_retur',
        'total_bayar',
        'sisa_tagihan',
        'user_id',
        'jatuh_tempo'
    ];

    public function items()
    {
        return $this->hasMany(PurchasesInvoiceItem::class);
    }
    public function supplier()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id'); // Create Supplier model if not exist
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentItems()
    {
    return $this->hasMany(PurchasesPaymentItem::class, 'purchases_invoice_id');
    }

    public function retur()
    {
        return $this->hasMany(PurchasesReturn::class, 'purchases_invoice_id');
    }

    public function latestPayment()
    {
        return $this->hasOneThrough(
        PurchasesPayment::class,
        PurchasesPaymentItem::class,
        'purchases_invoice_id', // FK di purchases_payment_items
        'id',               // PK di purchases_payment
        'id',               // PK di purchases_invoices
        'purchases_payment_id'  // FK di purchases_payment_items
        )->latest('purchases_payments.created_at');
    }
}
