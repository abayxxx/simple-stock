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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('email'); // Add role column with default value 'admin'
            $table->unsignedBigInteger('company_branch_id')->nullable()->after('role'); // Add company_branch_id column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role'); // Remove role column
            $table->dropColumn('company_branch_id'); // Remove company_branch_id column
        });
    }
};
