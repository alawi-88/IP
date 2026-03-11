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
            Schema::table('tracks', function (Blueprint $table) {
                $table->string('slug')->unique();
            });
        }

        if (!Schema::hasColumn('sub_tracks', 'slug')) {
        Schema::table('sub_tracks', function (Blueprint $table) {
            $table->string('slug')->unique();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('sub_tracks', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
