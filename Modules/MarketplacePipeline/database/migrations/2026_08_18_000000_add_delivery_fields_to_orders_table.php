<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->after('status');
            $table->string('recipient_phone')->nullable()->after('recipient_name');
            $table->string('shipping_line1')->nullable()->after('recipient_phone');
            $table->string('shipping_line2')->nullable()->after('shipping_line1');
            $table->string('shipping_city')->nullable()->after('shipping_line2');
            $table->string('shipping_state')->nullable()->after('shipping_city');
            $table->string('shipping_zip')->nullable()->after('shipping_state');
            $table->string('shipping_country')->nullable()->after('shipping_zip');
            $table->text('delivery_notes')->nullable()->after('shipping_country');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_name',
                'recipient_phone',
                'shipping_line1',
                'shipping_line2',
                'shipping_city',
                'shipping_state',
                'shipping_zip',
                'shipping_country',
                'delivery_notes',
            ]);
        });
    }
};
