<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notifications (custom, not Laravel's default notifications table).
 * type/title/description -> tipo/titulo/descricao, lida -> read.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            // 'turno' | 'mensagem' | 'documento' | 'sistema' | 'vaga'
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable(); // MySQL forbids defaults on TEXT; defaulted in the model
            $table->boolean('read')->default(false);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
