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
        //
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('lokasi_id')->nullable()->after('sales_group_id'); // Add lokasi_id column after sales_group_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('lokasi_id'); // Drop lokasi_id column if it exists
        });
    }
};
