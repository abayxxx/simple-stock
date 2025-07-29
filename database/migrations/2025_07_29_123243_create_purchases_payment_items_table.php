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
        Schema::create('purchases_payment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchases_payment_id');
            $table->enum('tipe_nota', ['FAKTUR', 'RETUR']); // type: invoice or return
            $table->unsignedBigInteger('purchases_invoice_id')->nullable(); // for invoice
            $table->unsignedBigInteger('purchases_return_id')->nullable();  // for return

            $table->decimal('nilai_nota', 20, 2)->default(0);    // total value of note
            $table->decimal('sisa', 20, 2)->default(0);          // outstanding

            // Breakdown fields (editable per row)
            $table->decimal('tunai', 20, 2)->default(0)->nullable();
            $table->decimal('bank', 20, 2)->default(0)->nullable();
            $table->decimal('giro', 20, 2)->default(0)->nullable();
            $table->decimal('cndn', 20, 2)->default(0)->nullable();    // Credit/Debit Note
            $table->decimal('retur', 20, 2)->default(0)->nullable();
            $table->decimal('panjar', 20, 2)->default(0)->nullable();
            $table->decimal('lainnya', 20, 2)->default(0)->nullable();

            $table->decimal('sub_total', 20, 2)->default(0);     // calculated sum (for each row)
            $table->string('pot_ke_no')->nullable();             // for return: potong ke faktur no

            $table->string('catatan')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases_payment_items');
    }
};
