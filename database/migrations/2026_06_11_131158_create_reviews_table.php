<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vaga_id');
            $table->foreign('vaga_id')->references('id')->on('vagas')->onDelete('cascade');
            $table->uuid('autor_id');
            $table->foreign('autor_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('alvo_id');
            $table->foreign('alvo_id')->references('id')->on('users')->onDelete('cascade');
            $table->tinyInteger('nota');
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['vaga_id', 'autor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
