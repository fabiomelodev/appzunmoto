<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marketing channels: `slug` is the value of ?utm_source=… in the links/QR codes.
        Schema::create('acquisition_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('acquisition_channels')->insert(array_map(
            fn ($c) => $c + ['active' => true, 'created_at' => $now, 'updated_at' => $now],
            [
                ['name' => 'TV', 'slug' => 'tv'],
                ['name' => 'Panfleto', 'slug' => 'panfleto'],
                ['name' => 'Instagram', 'slug' => 'instagram'],
                ['name' => 'Google Ads', 'slug' => 'google-ads'],
                ['name' => 'YouTube Ads', 'slug' => 'youtube-ads'],
            ],
        ));

        Schema::table('users', function (Blueprint $table) {
            // First-touch attribution captured on the visit that preceded the signup.
            $table->foreignId('acquisition_channel_id')->nullable()->after('is_admin')
                ->constrained('acquisition_channels')->nullOnDelete();
            // The raw (sanitised) utm_source, kept even when it matches no registered channel.
            $table->string('utm_source', 64)->nullable()->after('acquisition_channel_id');
            // Self-reported answer to "Como você conheceu o ZunMoto?" (onboarding, optional).
            $table->string('heard_from', 32)->nullable()->after('utm_source');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acquisition_channel_id');
            $table->dropColumn(['utm_source', 'heard_from']);
        });

        Schema::dropIfExists('acquisition_channels');
    }
};
