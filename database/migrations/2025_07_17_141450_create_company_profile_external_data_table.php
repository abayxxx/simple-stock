<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_profile_external_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_profile_id');
            $table->decimal('total_receivable_now', 20, 2)->default(0);
            $table->integer('unpaid_sales_invoices_count')->default(0);
            $table->date('last_sales_date')->nullable();
            $table->decimal('giro_received', 20, 2)->default(0);
            $table->decimal('due_receivables', 20, 2)->default(0);
            $table->integer('due_sales_invoices_count')->default(0);
            $table->decimal('grand_total_sales', 20, 2)->default(0);
            $table->decimal('grand_total_sales_returns', 20, 2)->default(0);
            $table->decimal('total_debt_now', 20, 2)->default(0);
            $table->integer('unpaid_purchase_invoices_count')->default(0);
            $table->date('last_purchase_date')->nullable();
            $table->decimal('giro_paid', 20, 2)->default(0);
            $table->decimal('due_debt', 20, 2)->default(0);
            $table->integer('due_purchase_invoices_count')->default(0);
            $table->decimal('grand_total_purchases', 20, 2)->default(0);
            $table->decimal('grand_total_purchase_returns', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_profile_external_data');
    }
};
