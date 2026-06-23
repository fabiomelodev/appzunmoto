<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery shift / opening (originally "vagas"). A business or courier posts a
 * shift that other couriers can apply to.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->constrained('users')->cascadeOnDelete();

            // 'business' (restaurante) | 'courier' (motoboy)
            $table->string('creator_role');
            $table->string('venue');                       // local
            $table->string('region');                      // regiao
            $table->string('address')->default('');
            $table->text('address_photo_url')->nullable();
            $table->string('postal_code')->nullable();     // cep
            $table->date('date');
            $table->string('start_time');                  // "HH:MM"
            $table->string('end_time');
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('delivery_fee_min', 10, 2)->default(0);
            $table->decimal('delivery_fee_max', 10, 2)->default(0);
            // 'pizzaria' | 'hamburguer' | 'japones' | 'mercado' | 'farmacia' | 'outro'
            $table->string('venue_type')->nullable();
            // 'tranquilo' | 'moderado' | 'pesado'
            $table->string('expected_volume')->nullable();
            $table->json('benefits')->nullable();          // ['lanche','almoco',...]
            $table->json('accepted_vehicles')->nullable(); // ['moto','bike',...]
            $table->boolean('requires_own_bag')->default(false);
            $table->text('notes')->nullable();
            // 'available' (disponivel) | 'reserved' (reservada) | 'filled' (preenchida)
            $table->string('status')->default('available');
            $table->foreignUuid('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('partnership_confirmations')->nullable(); // user ids
            $table->unsignedInteger('couriers_needed')->default(1);
            $table->double('lat')->default(0);
            $table->double('lng')->default(0);
            $table->timestamps();

            $table->index('region');
            $table->index('date');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
