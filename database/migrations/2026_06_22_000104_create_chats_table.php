<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A 1:1 conversation between two users tied to a shift.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignUuid('user_a')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('user_b')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['shift_id', 'user_a', 'user_b']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
