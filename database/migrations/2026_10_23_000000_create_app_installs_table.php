<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where the PWA is installed: one row per user + device (the id is generated and kept by the app on that device).
        Schema::create('app_installs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_key', 64);
            $table->string('platform', 16); // android | ios | desktop
            $table->timestamp('installed_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('last_standalone_at')->nullable(); // last time it ran as the installed app

            $table->unique(['user_id', 'device_key']);
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_installs');
    }
};
