<?php

// app/Models/SalesReturn.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'tanggal',
        'company_profile_id',
        'sales_invoice_id',
        'sales_group_id',
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
    public function customer()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }
    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
    public function salesGroup()
    {
        return $this->belongsTo(SalesGroup::class, 'sales_group_id');
    }
    public function items()
    {
        return $this->hasMany(SalesReturnItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
