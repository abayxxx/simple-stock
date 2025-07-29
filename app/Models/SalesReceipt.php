<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReceipt extends Model
{
    protected $fillable = [
        'kode',
        'tanggal',
        'company_profile_id',
        'employee_id', // collector_id
        'status',
        'total_faktur',
        'total_retur',
        'user_id',
        'keterangan',
        'kembali_tagih_tanggal'
    ];

    public function customer()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }
    public function collector()
    {
        return $this->belongsTo(EmployeProfile::class, 'employee_id');
    }
    public function receiptItems()
    {
        return $this->hasMany(SalesReceiptItem::class);
    }
}
