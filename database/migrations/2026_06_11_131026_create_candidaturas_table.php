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
        Schema::create('candidaturas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vaga_id');
            $table->foreign('vaga_id')->references('id')->on('vagas')->onDelete('cascade');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('status')->default('interessado');
            $table->timestamp('created_at')->useCurrent(); // Apenas created_at conforme seu script

            $table->unique(['vaga_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidaturas');
    }
};
