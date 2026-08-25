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
        Schema::table('vendor_bank_details', function (Blueprint $table) {
            $table->dropColumn(['account_holder', 'iban', 'bank_name', 'swift_bic', 'additional_info']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_bank_details', function (Blueprint $table) {
            $table->string('account_holder')->nullable();
            $table->string('iban')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('swift_bic')->nullable();
            $table->text('additional_info')->nullable();
        });
    }
};
