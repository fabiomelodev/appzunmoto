<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved addresses for a user (originally "user_addresses").
 * apelido -> label, rua -> street, numero -> number, bairro -> district,
 * cidade -> city, cep -> postal_code, referencia -> reference.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label');
            $table->string('postal_code')->nullable();
            $table->string('street')->default('');
            $table->string('number')->default('');
            $table->string('district')->default('');
            $table->string('city')->default('');
            $table->string('reference')->nullable();
            $table->text('photo_url')->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
