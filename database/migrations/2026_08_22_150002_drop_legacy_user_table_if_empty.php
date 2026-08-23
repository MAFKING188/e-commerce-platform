<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Drop the pre-modular-refactor legacy `user` table if it exists and is empty. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user')) {
            return;
        }

        if (DB::table('user')->count() === 0) {
            Schema::drop('user');
        }
    }

    public function down(): void
    {
        //
    }
};
