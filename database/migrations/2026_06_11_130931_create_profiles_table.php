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
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(User::class);
            $table->string('tipo')->default('motoboy'); // No Laravel, validação 'in' geralmente é feita na aplicação (Validation)
            $table->text('foto_url')->nullable();
            $table->string('cidade')->nullable();
            $table->text('bio')->nullable();
            $table->string('telefone')->nullable();
            $table->string('cpf')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('endereco_rua')->nullable();
            $table->string('endereco_numero')->nullable();
            $table->string('endereco_bairro')->nullable();
            $table->boolean('possui_bag')->default(false);
            $table->string('veiculo')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->timestamps(); // Cria automaticamente created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
