<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A rating left by one user (author) about another (target) for a shift.
 * Columns: nota -> rating, comentario -> comment, alvo_id -> target_id.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignUuid('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('target_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1..5 (validated in app)
            $table->text('comment')->nullable(); // MySQL forbids defaults on TEXT; defaulted in the model
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['shift_id', 'author_id', 'target_id']);
            $table->index('target_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
