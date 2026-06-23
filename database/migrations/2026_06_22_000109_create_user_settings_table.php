<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user preferences. Keyed by user id.
 * notif_vagas -> notify_shifts, notif_chat -> notify_chat, notif_email -> notify_email.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->boolean('notify_shifts')->default(true);
            $table->boolean('notify_chat')->default(true);
            $table->boolean('notify_email')->default(false);
            // 'dark' | 'light' | 'urbano'
            $table->string('theme')->default('dark');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
