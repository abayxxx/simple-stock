<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfileExternalData extends Model
{
    //
    protected $fillable = [
        'company_profile_id',
        'total_receivable_now',
        'unpaid_sales_invoices_count',
        'last_sales_date',
        'giro_received',
        'due_receivables',
        'due_sales_invoices_count',
        'grand_total_sales',
        'grand_total_sales_returns',
        'total_debt_now',
        'unpaid_purchase_invoices_count',
        'last_purchase_date',
        'giro_paid',
        'due_debt',
        'due_purchase_invoices_count',
        'grand_total_purchases',
        'grand_total_purchase_returns',
    ];

    public function companyProfile()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }
}
