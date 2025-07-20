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
        Schema::create('employe_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nama');
            $table->string('no_telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('alamat')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employe_profiles');
    }
};
