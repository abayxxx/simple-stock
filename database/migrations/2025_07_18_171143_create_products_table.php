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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('satuan_kecil');
            $table->integer('isi_satuan_kecil')->default(1);
            $table->string('satuan_sedang')->nullable();
            $table->integer('isi_satuan_sedang')->default(1);
            $table->string('satuan_besar')->nullable();
            $table->integer('isi_satuan_besar')->default(1);
            $table->string('satuan_massa')->nullable();
            $table->integer('isi_satuan_massa')->default(1);
            $table->text('catatan')->nullable();

            // Harga Pokok
            $table->double('hpp_bruto_kecil')->nullable();
            $table->double('hpp_bruto_besar')->nullable();
            $table->double('diskon_hpp_1')->nullable();
            $table->double('diskon_hpp_2')->nullable();
            $table->double('diskon_hpp_3')->nullable();
            $table->double('diskon_hpp_4')->nullable();
            $table->double('diskon_hpp_5')->nullable();

            // Harga Jual
            $table->double('harga_umum')->nullable();
            $table->double('diskon_harga_1')->nullable();
            $table->double('diskon_harga_2')->nullable();
            $table->double('diskon_harga_3')->nullable();
            $table->double('diskon_harga_4')->nullable();
            $table->double('diskon_harga_5')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
