<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_center_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->foreignId('sender_user_id')->constrained('users');
            $table->string('sender_role', 20);
            $table->string('recipient_email', 255);
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('subject', 150);
            $table->text('body_markdown');
            $table->string('status', 10)->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('sender_user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_center_logs');
    }
};