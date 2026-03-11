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
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'idea_challenge_id')) { $table->unsignedBigInteger('idea_challenge_id')->nullable()->change(); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('idea_challenge_id')->nullable(false)->change();
        });
        }
    }
};
