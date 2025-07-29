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
        Schema::create('purchases_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchases_return_id');
            $table->unsignedBigInteger('product_id');
            // $table->unsignedBigInteger('lokasi_id')->nullable();
            $table->string('no_seri')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->integer('qty');
            $table->string('satuan')->nullable(); // misal: BOX, PCS
            $table->decimal('harga_satuan', 20, 2);

            // Diskon 1-3: Persen & Rupiah
            $table->decimal('diskon_1_persen', 8, 2)->default(0)->nullable();
            $table->decimal('diskon_1_rupiah', 20, 2)->default(0)->nullable();
            $table->decimal('diskon_2_persen', 8, 2)->default(0)->nullable();
            $table->decimal('diskon_2_rupiah', 20, 2)->default(0)->nullable();
            $table->decimal('diskon_3_persen', 8, 2)->default(0)->nullable();
            $table->decimal('diskon_3_rupiah', 20, 2)->default(0)->nullable();

            // Kolom hasil kalkulasi
            $table->decimal('sub_total_sblm_disc', 20, 2)->default(0)->nullable();       // Sebelum diskon
            $table->decimal('total_diskon_item', 20, 2)->default(0)->nullable();         // Total diskon (semua)
            $table->decimal('sub_total_sebelum_ppn', 20, 2)->default(0)->nullable();     // Setelah diskon, sebelum PPN
            $table->decimal('ppn_persen', 5, 2)->default(0)->nullable();                 // PPN %
            $table->decimal('sub_total_setelah_disc', 20, 2)->default(0)->nullable();    // Setelah diskon + PPN

            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases_return_items');
    }
};
