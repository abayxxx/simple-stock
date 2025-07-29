<?php

// app/Models/SalesInvoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $fillable = [
        'kode',
        'tanggal',
        'company_profile_id',
        'sales_group_id',
        'term',
        'is_tunai',
        'no_po',
        'catatan',
        'diskon_faktur',
        'diskon_ppn',
        'subtotal',
        'grand_total',
        'total_retur',
        'total_bayar',
        'sisa_tagihan',
        'jatuh_tempo',
        'user_id'
    ];

    // Relasi ke detail barang
    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    // Relasi ke customer
    public function customer()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    // Relasi ke sales group
    public function salesGroup()
    {
        return $this->belongsTo(SalesGroup::class, 'sales_group_id');
    }

    // Relasi ke user (creator)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke tanda terima
    public function receiptItems()
    {
        return $this->hasMany(SalesReceiptItem::class, 'sales_invoice_id');
    }
}
