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
        Schema::create('sales_group_employe_profile', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_group_id');
            $table->unsignedBigInteger('employe_profile_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('sales_group_employe_profile');
    }
};
