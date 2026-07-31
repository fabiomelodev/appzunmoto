<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profile of a user (1:1 with users). Mirrors the Supabase `profiles` table.
 * The primary key is the user id (shared UUID), like the original app.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreign('id')->references('id')->on('users')->cascadeOnDelete();

            // 'courier' (motoboy) | 'business' (restaurante)
            $table->string('role')->default('courier');
            $table->string('name')->default('');
            $table->text('photo_url')->nullable();
            $table->string('city')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->string('cpf')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('district')->nullable();
            $table->boolean('has_bag')->default(false);
            // 'moto' | 'bike-eletrica' | 'bike'
            $table->string('vehicle')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
