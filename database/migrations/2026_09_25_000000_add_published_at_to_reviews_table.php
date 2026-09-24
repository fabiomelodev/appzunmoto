<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A review only becomes public (profile lists and rating averages) some days
 * after it was written, so a low rating can't be traced back to a specific
 * shift/day by the person who received it. NULL = still private.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('created_at');
            $table->index('published_at');
        });

        // Reviews that already existed were public from the start.
        DB::table('reviews')->update(['published_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropColumn('published_at');
        });
    }
};
