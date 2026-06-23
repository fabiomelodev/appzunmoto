<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A courier's application to a shift (originally "candidaturas").
 * Only a created_at is tracked, matching the source schema.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // 'interested' (interessado) | 'accepted' (aceito)
            $table->string('status')->default('interested');
            $table->boolean('confirmed')->default(false);
            $table->json('confirmations')->nullable(); // user ids who confirmed
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['shift_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
