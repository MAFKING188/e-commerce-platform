<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
        DB::table('users')->where('two_factor_type', 'totp')->update(['two_factor_type' => 'email']);
    }

    public function down(): void
    {
        //
    }
};