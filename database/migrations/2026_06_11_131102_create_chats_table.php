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
        Schema::create('chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vaga_id');
            $table->foreign('vaga_id')->references('id')->on('vagas')->onDelete('cascade');
            $table->uuid('user_a');
            $table->foreign('user_a')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('user_b');
            $table->foreign('user_b')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
