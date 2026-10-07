<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Courier's working radius (km) around their base location (district/city, geocoded).
            $table->unsignedTinyInteger('radius_km')->default(15)->after('city');
            $table->decimal('base_lat', 10, 7)->nullable()->after('radius_km');
            $table->decimal('base_lng', 10, 7)->nullable()->after('base_lat');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['radius_km', 'base_lat', 'base_lng']);
        });
    }
};
