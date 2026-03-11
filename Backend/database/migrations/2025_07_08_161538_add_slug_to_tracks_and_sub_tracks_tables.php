<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        if (!Schema::hasColumn('tracks', 'slug')) {
            if (Schema::hasTable('tracks')) {
                Schema::table('tracks', function (Blueprint $table) {
                $table->string('slug')->unique();
            });
            }
        }

        if (!Schema::hasColumn('sub_tracks', 'slug')) {
        if (Schema::hasTable('sub_tracks')) {
            Schema::table('sub_tracks', function (Blueprint $table) {
            $table->string('slug')->unique();
        });
        }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tracks')) {
            Schema::table('tracks', function (Blueprint $table) {
            if (Schema::hasColumn('tracks', 'slug')) { if (Schema::hasColumn('sub_tracks', 'slug')) { $table->dropColumn('slug'); } }
        });
        }

        if (Schema::hasTable('sub_tracks')) {
            Schema::table('sub_tracks', function (Blueprint $table) {
            if (Schema::hasColumn('tracks', 'slug')) { if (Schema::hasColumn('sub_tracks', 'slug')) { $table->dropColumn('slug'); } }
        });
        }
    }
};
