<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Read up to here" marker per participant (user_a / user_b are stored sorted,
 * so one column per side): a message is unread for someone when it was written
 * by the other person after that moment. Drives the unread counters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->timestamp('user_a_read_at')->nullable();
            $table->timestamp('user_b_read_at')->nullable();
        });

        // Conversations that already exist start fully read — otherwise every
        // past message would show up as "new" on the first deploy.
        DB::table('chats')->update(['user_a_read_at' => now(), 'user_b_read_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn(['user_a_read_at', 'user_b_read_at']);
        });
    }
};
