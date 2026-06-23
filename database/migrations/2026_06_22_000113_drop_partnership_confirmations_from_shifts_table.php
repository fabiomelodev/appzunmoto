<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the unused `partnership_confirmations` column from shifts — partnership
 * confirmations live on applications.confirmations. (Dead column flagged in review.)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'partnership_confirmations')) {
                $table->dropColumn('partnership_confirmations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->json('partnership_confirmations')->nullable();
        });
    }
};
