<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds creator-management columns: `active` (temporarily pause a shift) and
 * `edited_at` (set when the creator edits the shift, shown on the card).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('status');
            $table->timestamp('edited_at')->nullable()->after('updated_at');
            $table->index(['active', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['active', 'status']);
            $table->dropColumn(['active', 'edited_at']);
        });
    }
};
