<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The framework's cache/sessions tables ship with TEXT columns (64 KB).
 * Serialized storefront caches (catalog:collection ≈ hundreds of KB of
 * eager-loaded products) silently truncate and unserialize into
 * __PHP_Incomplete_Class. Upsize the payload columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
            return; // sqlite test env unaffected
        }

        if (Schema::hasTable('cache')) {
            Schema::table('cache', function (Blueprint $table) {
                $table->mediumText('value')->change();
            });
        }

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->mediumText('payload')->change();
            });
        }
    }

    public function down(): void
    {
        if (! in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasTable('cache')) {
            Schema::table('cache', function (Blueprint $table) {
                $table->text('value')->change();
            });
        }
    }
};
