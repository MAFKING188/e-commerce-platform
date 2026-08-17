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
        Schema::rename('vendors', 'partners');
        Schema::rename('vendor_products', 'partner_products');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('partners', 'vendors');
        Schema::rename('partner_products', 'vendor_products');
    }
};
