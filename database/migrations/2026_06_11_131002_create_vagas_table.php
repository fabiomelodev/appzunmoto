<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vagas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('criador_id');
            $table->foreignIdFor(User::class);
            $table->string('criador_tipo');
            $table->string('local');
            $table->string('regiao');
            $table->string('endereco')->default('');
            $table->string('cep')->nullable();
            $table->date('data');
            $table->string('hora_inicio');
            $table->string('hora_fim');
            $table->decimal('valor_diaria', 10, 2)->default(0);
            $table->decimal('valor_entrega', 10, 2)->default(0);

            // Como o MySQL não tem suporte nativo a arrays (TEXT[]), o ideal no Laravel é usar JSON
            $table->json('beneficios')->nullable();
            $table->json('veiculos_aceitos')->nullable();

            $table->boolean('exige_bag_propria')->default(false);
            $table->text('observacoes')->nullable();
            $table->string('status')->default('disponivel');

            $table->uuid('reservado_por')->nullable();
            $table->foreign('reservado_por')->references('id')->on('users')->onDelete('set null');

            $table->double('lat')->default(0);
            $table->double('lng')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vagas');
    }
};
