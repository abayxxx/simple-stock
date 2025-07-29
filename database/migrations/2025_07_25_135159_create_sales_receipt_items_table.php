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
        Schema::create('sales_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_receipt_id');
            $table->unsignedBigInteger('sales_invoice_id')->nullable();
            $table->decimal('total_faktur', 20, 2)->default(0);
            $table->decimal('total_retur', 20, 2)->default(0);
            $table->decimal('sisa_tagihan', 20, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_receipt_items');
    }
};
