<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reviews now go both ways (creator -> courier and courier -> creator). One
 * account can switch between the courier and establishment profiles, so each
 * review records which role its target played, and the profile keeps a
 * separate aggregate per role (avg_rating/total_reviews stay the courier's).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Every review that existed before this one was creator -> courier.
            $table->string('target_role', 16)->default('courier')->after('target_id');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->decimal('business_avg_rating', 3, 2)->default(0)->after('total_reviews');
            $table->unsignedInteger('business_total_reviews')->default(0)->after('business_avg_rating');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->timestamp('review_reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('review_reminder_sent_at');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['business_avg_rating', 'business_total_reviews']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('target_role');
        });
    }
};
