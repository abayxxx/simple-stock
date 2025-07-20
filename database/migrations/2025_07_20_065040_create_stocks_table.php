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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('kode')->unique();
            $table->string('no_seri')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->integer('jumlah')->default(0);
            $table->decimal('harga_net', 20, 2)->default(0);
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->integer('sisa_stok')->default(0); // Remaining stock after movement
            $table->enum('type', ['in', 'out', 'destroy'])->default('in'); // Type of stock movement
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
