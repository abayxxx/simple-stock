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
        Schema::create('purchases_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('company_profile_id')->nullable();
            $table->string('no_order')->nullable();
            $table->string('term')->nullable();
            $table->boolean('is_tunai')->default(false);
            $table->boolean('is_include_ppn')->default(false);
            $table->boolean('is_received')->default(false);
            $table->text('catatan')->nullable();


            $table->decimal('diskon_faktur', 15, 2)->default(0)->nullable();
            $table->decimal('diskon_ppn', 15, 2)->default(0)->nullable();
            $table->decimal('subtotal', 20, 2)->default(0)->nullable();
            $table->decimal('grand_total', 20, 2)->default(0)->nullable();


            $table->decimal('total_retur', 20, 2)->default(0)->nullable();
            $table->decimal('total_bayar', 20, 2)->default(0)->nullable();
            $table->decimal('sisa_tagihan', 20, 2)->default(0)->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('jatuh_tempo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases_invoices');
    }
};
