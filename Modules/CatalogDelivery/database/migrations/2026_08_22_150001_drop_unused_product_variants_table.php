<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Remove the never-used product_variants scaffolding (model + relation already deleted). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_variants');
    }

    public function down(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('sku')->nullable();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });
    }
};
