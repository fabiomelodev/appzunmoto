<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // City of the shift's address (without the UF suffix); `region` stays the neighbourhood.
            // Nullable: shifts created before this column are filled in by `php artisan shifts:backfill-city`.
            $table->string('city')->nullable()->after('region');
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropColumn('city');
        });
    }
};
