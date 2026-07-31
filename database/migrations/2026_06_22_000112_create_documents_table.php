<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courier documents (identity / vehicle) with a validation status.
 * Not present in the original Supabase schema (the React app kept them
 * client-side); here they are persisted properly.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            // 'identity' | 'vehicle'
            $table->string('type');
            // 'pending' | 'submitted' | 'review' | 'approved' | 'rejected'
            $table->string('status')->default('pending');
            $table->text('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
