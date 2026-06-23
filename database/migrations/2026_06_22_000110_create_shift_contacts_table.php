<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional contact info for a shift, visible only to the creator and the
 * accepted courier (originally "vaga_contatos").
 * nome_responsavel -> contact_name, telefone_whatsapp -> whatsapp_phone.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('shift_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_id')->unique()->constrained('shifts')->cascadeOnDelete();
            $table->string('contact_name', 100)->nullable();
            $table->string('whatsapp_phone', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_contacts');
    }
};
