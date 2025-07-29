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
        Schema::create('sales_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('company_profile_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->decimal('total_faktur', 20, 2)->default(0);
            $table->decimal('total_retur', 20, 2)->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->date('kembali_tagih_tanggal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_receipts');
    }
};
