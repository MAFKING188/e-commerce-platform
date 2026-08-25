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
        Schema::create('vendor_bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('bank_details_image')->nullable(); // Screenshot/image of bank details
            $table->string('account_holder')->nullable(); // Account holder name
            $table->string('iban')->nullable(); // IBAN
            $table->string('bank_name')->nullable(); // Bank name
            $table->string('swift_bic')->nullable(); // SWIFT/BIC
            $table->text('additional_info')->nullable(); // Additional instructions
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bank_details');
    }
};
